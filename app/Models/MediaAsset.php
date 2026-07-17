<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model {
    use SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['file_size'=>'integer','width'=>'integer','height'=>'integer','duration_ms'=>'integer','last_used_at'=>'datetime','storage_verified_at'=>'datetime','embed_options_json'=>'array']; }
    public function playlistItems(): HasMany { return $this->hasMany(PlaylistItem::class); }
    public function fallbackMedia(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'fallback_media_asset_id'); }
    public function getInUseAttribute(): bool { return $this->playlistItems()->exists(); }
    public function getIsWebEmbedAttribute(): bool { return $this->media_type === 'web_embed'; }
}
