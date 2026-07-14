<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DisplayStatus extends Model { protected $guarded=[]; protected function casts(): array { return ['last_seen_at'=>'datetime','current_items_json'=>'array']; } }
