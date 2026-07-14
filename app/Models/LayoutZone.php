<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayoutZone extends Model {
    protected $guarded = [];
    public function layout(): BelongsTo { return $this->belongsTo(Layout::class); }
    public function items(): HasMany { return $this->hasMany(PlaylistItem::class)->orderBy('sort_order'); }
}
