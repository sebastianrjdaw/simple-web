<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['details_json' => 'array', 'bytes' => 'integer'];
    }
}
