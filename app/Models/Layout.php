<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layout extends Model {
    protected $guarded = [];
    protected function casts(): array { return ['snapshot_json'=>'array','published_at'=>'datetime']; }
    public function zones(): HasMany { return $this->hasMany(LayoutZone::class)->orderBy('position'); }
}
