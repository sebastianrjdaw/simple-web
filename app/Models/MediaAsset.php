<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model {
    use SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['file_size'=>'integer','width'=>'integer','height'=>'integer','duration_ms'=>'integer','last_used_at'=>'datetime','storage_verified_at'=>'datetime']; }
    public function playlistItems(): HasMany { return $this->hasMany(PlaylistItem::class); }
    public function getInUseAttribute(): bool { return $this->playlistItems()->exists(); }
}
