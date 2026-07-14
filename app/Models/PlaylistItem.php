<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistItem extends Model {
    protected $guarded = [];
    public function zone(): BelongsTo { return $this->belongsTo(LayoutZone::class, 'layout_zone_id'); }
    public function media(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'media_asset_id'); }
}
