<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class UploadCapacityService
{
    public function __construct(private StorageMetricsService $metrics, private StoragePolicyService $policy) {}

    public function check(int $fileBytes, bool $refresh = false): array
    {
        $metrics = $this->metrics->current($refresh);
        $required = (int) ceil(max(0, $fileBytes) * max(2.0, (float) config('simpleview.storage.upload_multiplier')));
        $shortfall = max(0, $metrics['reserved_bytes'] + $required - $metrics['filesystem_free_bytes']);
        return ['allowed' => $this->policy->operationAllowed($metrics, $required), 'required_bytes' => $required,
            'shortfall_bytes' => $shortfall, 'metrics' => $metrics];
    }

    public function ensure(int $fileBytes, bool $refresh = false): array
    {
        $result = $this->check($fileBytes, $refresh);
        if (!$result['allowed']) {
            $gb = number_format(max(1, $result['shortfall_bytes']) / 1024 ** 3, 1, ',', '.');
            throw ValidationException::withMessages(['files' => "No hay espacio suficiente para subir este archivo. Libera al menos {$gb} GB o elimina contenidos que ya no se utilicen."]);
        }
        return $result;
    }
}
