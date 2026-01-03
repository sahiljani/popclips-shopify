<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'shopify_charge_id',
        'plan',
        'price',
        'currency',
        'status',
        'activated_at',
        'cancelled_at',
        'trial_ends_at',
        'billing_on',
    ];

    protected $casts = [
        'price' => 'float',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'billing_on' => 'datetime',
    ];

    const PLAN_FREE = 'free';
    const PLAN_PRO = 'pro';

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FROZEN = 'frozen';
    const STATUS_EXPIRED = 'expired';

    const PRO_PRICE = 29.99;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePro($query)
    {
        return $query->where('plan', self::PLAN_PRO);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPro(): bool
    {
        return $this->plan === self::PLAN_PRO && $this->isActive();
    }

    public function isFree(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }

    public function isTrialing(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $this->shop->update(['plan' => self::PLAN_FREE]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        $this->shop->update([
            'plan' => $this->plan,
            'plan_started_at' => now(),
        ]);
    }

    public static function createProSubscription(Shop $shop, string $chargeId): self
    {
        return self::create([
            'shop_id' => $shop->id,
            'shopify_charge_id' => $chargeId,
            'plan' => self::PLAN_PRO,
            'price' => self::PRO_PRICE,
            'currency' => 'USD',
            'status' => self::STATUS_PENDING,
        ]);
    }
}
