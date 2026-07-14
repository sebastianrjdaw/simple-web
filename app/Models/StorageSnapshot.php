<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['source_measured_at' => 'datetime', 'details_json' => 'array'];
    }
}
