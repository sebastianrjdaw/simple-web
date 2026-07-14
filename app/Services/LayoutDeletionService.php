<?php

namespace App\Services;

use App\Models\AdminActivityEvent;
use App\Models\Layout;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class LayoutDeletionService
{
    public function classify(Layout $layout): array
    {
        $activeVersion = (int) Setting::valueOf('active_publication_version', 0);
        $isActive = $layout->state === 'published' && (int) $layout->version === $activeVersion;
        $totalLayouts = Layout::count();
        $items = $layout->zones()->withCount('items')->get()->sum('items_count');
        $blocked = [];

        if ($isActive) {
            $blocked[] = 'active_publication';
        }

        if ($totalLayouts <= 1) {
            $blocked[] = 'last_layout';
        }

        return [
            'can_delete' => $blocked === [],
            'blocked_reasons' => $blocked,
            'is_active' => $isActive,
            'zone_count' => $layout->zones()->count(),
            'item_count' => $items,
            'message' => $blocked === []
                ? 'Se eliminará el diseño "'.$layout->name.'". Los archivos multimedia no se eliminarán de la biblioteca.'
                : $this->blockedMessage($blocked),
        ];
    }

    public function delete(Layout $layout, int $userId): array
    {
        $classification = $this->classify($layout);

        if (!$classification['can_delete']) {
            $this->record($userId, $layout, 'blocked', $classification);
            return ['deleted' => false, 'status' => 'blocked'] + $classification;
        }

        try {
            DB::transaction(function () use ($layout) {
                $fresh = Layout::lockForUpdate()->findOrFail($layout->id);
                $current = $this->classify($fresh);
                if (!$current['can_delete']) {
                    throw new \RuntimeException($current['message']);
                }
                $fresh->delete();
            });

            $this->record($userId, $layout, 'deleted', $classification);
            return ['deleted' => true, 'status' => 'deleted'] + $classification;
        } catch (\Throwable $e) {
            $this->record($userId, $layout, 'error', $classification, $e->getMessage());
            return ['deleted' => false, 'status' => 'error', 'error' => $e->getMessage()] + $classification;
        }
    }

    public function bulk(iterable $layouts, int $userId): array
    {
        $results = [];
        foreach ($layouts as $layout) {
            $results[] = ['id' => $layout->id, 'name' => $layout->name] + $this->delete($layout, $userId);
        }

        return [
            'results' => $results,
            'deleted' => collect($results)->where('deleted', true)->count(),
            'blocked' => collect($results)->where('deleted', false)->count(),
        ];
    }

    private function blockedMessage(array $blocked): string
    {
        if (in_array('active_publication', $blocked, true)) {
            return 'No se puede eliminar este diseño porque se está reproduciendo actualmente. Publica otro diseño y vuelve a intentarlo.';
        }

        return 'No se puede eliminar el único diseño existente. Crea otro diseño antes.';
    }

    private function record(int $userId, Layout $layout, string $result, array $details, ?string $error = null): void
    {
        AdminActivityEvent::create([
            'user_id' => $userId ?: null,
            'action' => 'layout.delete',
            'subject_type' => Layout::class,
            'subject_id' => $layout->id,
            'result' => $result,
            'bytes' => 0,
            'details_json' => $details,
            'error_message' => $error,
        ]);
    }
}
