<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class ForceDisplayReload extends Widget
{
    protected static string $view = 'filament.widgets.force-display-reload';

    protected int|string|array $columnSpan = 'full';

    public function forceReload(): void
    {
        Setting::updateOrCreate(
            ['key' => 'display_reload_token'],
            ['value' => (string) Str::uuid(), 'type' => 'string'],
        );

        Notification::make()
            ->success()
            ->title('Recarga enviada al reproductor')
            ->body('La página del reproductor se recargará en un máximo aproximado de tres segundos.')
            ->send();
    }
}
