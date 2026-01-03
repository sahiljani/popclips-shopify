<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shopify_domain',
        'name',
        'email',
        'access_token',
        'scopes',
        'plan',
        'plan_started_at',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'scopes' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'plan_started_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
    ];

    public function clips(): HasMany
    {
        return $this->hasMany(Clip::class);
    }

    public function carousels(): HasMany
    {
        return $this->hasMany(Carousel::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function isFree(): bool
    {
        return $this->plan === 'free';
    }

    public function getDefaultSettings(): array
    {
        return [
            'autoplay' => true,
            'autoplay_speed' => 5,
            'show_view_count' => true,
            'show_like_button' => true,
            'items_to_show' => 3,
            'mobile_items' => 1,
            'aspect_ratio' => '9:16',
            'hotspot_color' => '#FF6B6B',
            'button_style' => 'rounded',
            'show_branding' => true,
        ];
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = array_merge($this->getDefaultSettings(), $this->settings ?? []);

        return $settings[$key] ?? $default;
    }
}
