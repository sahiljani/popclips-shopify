<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analytics extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'analytics';

    protected $fillable = [
        'shop_id',
        'clip_id',
        'hotspot_id',
        'event_type',
        'session_id',
        'visitor_id',
        'shopify_product_id',
        'revenue',
        'currency',
        'device_type',
        'browser',
        'country',
        'metadata',
    ];

    protected $casts = [
        'revenue' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    const EVENT_CLIP_VIEW = 'clip_view';

    const EVENT_CLIP_COMPLETE = 'clip_complete';

    const EVENT_HOTSPOT_CLICK = 'hotspot_click';

    const EVENT_ADD_TO_CART = 'add_to_cart';

    const EVENT_PURCHASE = 'purchase';

    const EVENT_LIKE = 'like';

    const EVENT_SHARE = 'share';

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function clip(): BelongsTo
    {
        return $this->belongsTo(Clip::class);
    }

    public function hotspot(): BelongsTo
    {
        return $this->belongsTo(Hotspot::class);
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForClip($query, $clipId)
    {
        return $query->where('clip_id', $clipId);
    }

    public function scopeForHotspot($query, $hotspotId)
    {
        return $query->where('hotspot_id', $hotspotId);
    }

    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public static function recordClipView(Clip $clip, array $data = []): self
    {
        $clip->incrementViews();

        return self::create([
            'shop_id' => $clip->shop_id,
            'clip_id' => $clip->id,
            'event_type' => self::EVENT_CLIP_VIEW,
            'session_id' => $data['session_id'] ?? null,
            'visitor_id' => $data['visitor_id'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'browser' => $data['browser'] ?? null,
            'country' => $data['country'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function recordHotspotClick(Hotspot $hotspot, array $data = []): self
    {
        $hotspot->incrementClicks();

        return self::create([
            'shop_id' => $hotspot->clip->shop_id,
            'clip_id' => $hotspot->clip_id,
            'hotspot_id' => $hotspot->id,
            'event_type' => self::EVENT_HOTSPOT_CLICK,
            'shopify_product_id' => $hotspot->shopify_product_id,
            'session_id' => $data['session_id'] ?? null,
            'visitor_id' => $data['visitor_id'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'browser' => $data['browser'] ?? null,
            'country' => $data['country'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function recordAddToCart(Hotspot $hotspot, array $data = []): self
    {
        $hotspot->incrementAddToCarts();

        return self::create([
            'shop_id' => $hotspot->clip->shop_id,
            'clip_id' => $hotspot->clip_id,
            'hotspot_id' => $hotspot->id,
            'event_type' => self::EVENT_ADD_TO_CART,
            'shopify_product_id' => $hotspot->shopify_product_id,
            'session_id' => $data['session_id'] ?? null,
            'visitor_id' => $data['visitor_id'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'browser' => $data['browser'] ?? null,
            'country' => $data['country'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function recordPurchase(Shop $shop, array $data = []): self
    {
        return self::create([
            'shop_id' => $shop->id,
            'clip_id' => $data['clip_id'] ?? null,
            'hotspot_id' => $data['hotspot_id'] ?? null,
            'event_type' => self::EVENT_PURCHASE,
            'shopify_product_id' => $data['product_id'] ?? null,
            'revenue' => $data['revenue'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'session_id' => $data['session_id'] ?? null,
            'visitor_id' => $data['visitor_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function recordLike(Clip $clip, array $data = []): self
    {
        $clip->incrementLikes();

        return self::create([
            'shop_id' => $clip->shop_id,
            'clip_id' => $clip->id,
            'event_type' => self::EVENT_LIKE,
            'session_id' => $data['session_id'] ?? null,
            'visitor_id' => $data['visitor_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function recordShare(Clip $clip, array $data = []): self
    {
        $clip->incrementShares();

        return self::create([
            'shop_id' => $clip->shop_id,
            'clip_id' => $clip->id,
            'event_type' => self::EVENT_SHARE,
            'session_id' => $data['session_id'] ?? null,
            'visitor_id' => $data['visitor_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
