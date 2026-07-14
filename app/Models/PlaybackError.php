<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlaybackError extends Model { protected $guarded=[]; protected function casts(): array { return ['context_json'=>'array','occurred_at'=>'datetime','resolved_at'=>'datetime']; } }
