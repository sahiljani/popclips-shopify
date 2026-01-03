<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carousel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'name',
        'type',
        'display_location',
        'is_active',
        'autoplay',
        'autoplay_speed',
        'show_metrics',
        'items_desktop',
        'items_tablet',
        'items_mobile',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'autoplay' => 'boolean',
        'show_metrics' => 'boolean',
        'settings' => 'array',
        'autoplay_speed' => 'integer',
        'items_desktop' => 'integer',
        'items_tablet' => 'integer',
        'items_mobile' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function clips(): BelongsToMany
    {
        return $this->belongsToMany(Clip::class, 'carousel_clips')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('carousel_clips.position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStandard($query)
    {
        return $query->where('type', 'standard');
    }

    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForLocation($query, $location)
    {
        return $query->whereIn('display_location', [$location, 'all']);
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard';
    }

    public function isCustom(): bool
    {
        return $this->type === 'custom';
    }

    public function getClipsForDisplay()
    {
        if ($this->isStandard()) {
            return $this->shop
                ->clips()
                ->published()
                ->orderBy('published_at', 'desc')
                ->get();
        }

        return $this->clips()->published()->get();
    }

    public function addClip(Clip $clip, ?int $position = null): void
    {
        if ($position === null) {
            $position = $this->clips()->count();
        }

        $this->clips()->attach($clip->id, ['position' => $position]);
    }

    public function removeClip(Clip $clip): void
    {
        $this->clips()->detach($clip->id);
        $this->reorderClips();
    }

    public function reorderClips(): void
    {
        $this->clips()
            ->orderBy('carousel_clips.position')
            ->get()
            ->each(function ($clip, $index) {
                $this->clips()->updateExistingPivot($clip->id, ['position' => $index]);
            });
    }

    public function updateClipPositions(array $clipIds): void
    {
        foreach ($clipIds as $position => $clipId) {
            $this->clips()->updateExistingPivot($clipId, ['position' => $position]);
        }
    }
}
