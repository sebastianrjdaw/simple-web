<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model {
    protected $guarded = [];
    protected function casts(): array { return ['file_size'=>'integer','width'=>'integer','height'=>'integer','duration_ms'=>'integer']; }
    public function playlistItems(): HasMany { return $this->hasMany(PlaylistItem::class); }
    public function getInUseAttribute(): bool { return $this->playlistItems()->exists(); }
}
