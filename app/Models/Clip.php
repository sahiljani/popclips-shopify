<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'cloudinary_public_id',
        'shopify_file_id',
        'duration',
        'aspect_ratio',
        'file_size',
        'status',
        'is_published',
        'published_at',
        'views_count',
        'likes_count',
        'shares_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'duration' => 'integer',
        'file_size' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class)->orderBy('start_time');
    }

    public function carousels(): BelongsToMany
    {
        return $this->belongsToMany(Carousel::class, 'carousel_clips')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('carousel_clips.position');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('status', 'ready');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
        ]);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    public function incrementShares(): void
    {
        $this->increment('shares_count');
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '0:00';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '0 KB';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
