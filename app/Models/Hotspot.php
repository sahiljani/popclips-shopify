<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotspot extends Model
{
    use HasFactory;

    protected $fillable = [
        'clip_id',
        'shopify_product_id',
        'product_title',
        'product_handle',
        'product_image',
        'product_price',
        'product_currency',
        'position_x',
        'position_y',
        'start_time',
        'end_time',
        'animation_style',
        'clicks_count',
        'add_to_cart_count',
    ];

    protected $casts = [
        'position_x' => 'float',
        'position_y' => 'float',
        'start_time' => 'float',
        'end_time' => 'float',
        'product_price' => 'float',
        'clicks_count' => 'integer',
        'add_to_cart_count' => 'integer',
    ];

    public function clip(): BelongsTo
    {
        return $this->belongsTo(Clip::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }

    public function scopeForClip($query, $clipId)
    {
        return $query->where('clip_id', $clipId);
    }

    public function scopeActiveAt($query, float $time)
    {
        return $query->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time);
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks_count');
    }

    public function incrementAddToCarts(): void
    {
        $this->increment('add_to_cart_count');
    }

    public function isActiveAt(float $time): bool
    {
        return $this->start_time <= $time && $this->end_time >= $time;
    }

    public function getDurationAttribute(): float
    {
        return $this->end_time - $this->start_time;
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        return sprintf(
            '%s - %s',
            $this->formatTime($this->start_time),
            $this->formatTime($this->end_time)
        );
    }

    protected function formatTime(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = floor($seconds % 60);

        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->product_currency . ' ' . number_format($this->product_price, 2);
    }

    public function toStorefrontArray(): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->shopify_product_id,
            'title' => $this->product_title,
            'handle' => $this->product_handle,
            'image' => $this->product_image,
            'price' => $this->product_price,
            'currency' => $this->product_currency,
            'x' => $this->position_x,
            'y' => $this->position_y,
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'animation' => $this->animation_style,
        ];
    }
}
