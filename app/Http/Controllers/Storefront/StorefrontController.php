<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Analytics;
use App\Models\Carousel;
use App\Models\Clip;
use App\Models\Hotspot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function carousel(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        if (! $shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $location = $request->query('location', 'homepage');
        $carouselId = $request->query('carousel_id');

        if ($carouselId) {
            $carousel = Carousel::forShop($shop->id)
                ->active()
                ->find($carouselId);
        } else {
            $carousel = Carousel::forShop($shop->id)
                ->active()
                ->forLocation($location)
                ->first();
        }

        if (! $carousel) {
            $carousel = Carousel::forShop($shop->id)->standard()->active()->first();
        }

        if (! $carousel) {
            return response()->json(['clips' => [], 'settings' => []]);
        }

        $clips = $carousel->getClipsForDisplay();

        $formattedClips = $clips->map(function ($clip) {
            return [
                'id' => $clip->id,
                'title' => $clip->title,
                'videoUrl' => $clip->video_url,
                'thumbnailUrl' => $clip->thumbnail_url,
                'duration' => $clip->duration,
                'views' => $clip->views_count,
                'likes' => $clip->likes_count,
                'hotspots' => $clip->hotspots->map->toStorefrontArray(),
            ];
        });

        return response()->json([
            'clips' => $formattedClips,
            'settings' => [
                'autoplay' => $carousel->autoplay,
                'autoplaySpeed' => $carousel->autoplay_speed,
                'showMetrics' => $carousel->show_metrics,
                'itemsDesktop' => $carousel->items_desktop,
                'itemsTablet' => $carousel->items_tablet,
                'itemsMobile' => $carousel->items_mobile,
                'hotspotColor' => $shop->getSetting('hotspot_color'),
                'buttonStyle' => $shop->getSetting('button_style'),
                'showBranding' => $shop->getSetting('show_branding'),
            ],
        ]);
    }

    public function clip(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        if (! $shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $clip = Clip::forShop($shop->id)
            ->published()
            ->with('hotspots')
            ->find($id);

        if (! $clip) {
            return response()->json(['error' => 'Clip not found'], 404);
        }

        return response()->json([
            'id' => $clip->id,
            'title' => $clip->title,
            'description' => $clip->description,
            'videoUrl' => $clip->video_url,
            'thumbnailUrl' => $clip->thumbnail_url,
            'duration' => $clip->duration,
            'views' => $clip->views_count,
            'likes' => $clip->likes_count,
            'hotspots' => $clip->hotspots->map->toStorefrontArray(),
        ]);
    }

    public function trackEvent(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        if (! $shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $eventType = $request->input('event');
        $clipId = $request->input('clip_id');
        $hotspotId = $request->input('hotspot_id');

        $eventData = [
            'session_id' => $request->input('session_id'),
            'visitor_id' => $request->input('visitor_id'),
            'device_type' => $request->input('device_type'),
            'browser' => $request->input('browser'),
            'country' => $request->input('country'),
        ];

        switch ($eventType) {
            case 'clip_view':
                $clip = Clip::forShop($shop->id)->find($clipId);
                if ($clip) {
                    Analytics::recordClipView($clip, $eventData);
                }
                break;

            case 'hotspot_click':
                $hotspot = Hotspot::find($hotspotId);
                if ($hotspot && $hotspot->clip->shop_id === $shop->id) {
                    Analytics::recordHotspotClick($hotspot, $eventData);
                }
                break;

            case 'add_to_cart':
                $hotspot = Hotspot::find($hotspotId);
                if ($hotspot && $hotspot->clip->shop_id === $shop->id) {
                    Analytics::recordAddToCart($hotspot, $eventData);
                }
                break;

            case 'like':
                $clip = Clip::forShop($shop->id)->find($clipId);
                if ($clip) {
                    Analytics::recordLike($clip, $eventData);
                }
                break;

            case 'share':
                $clip = Clip::forShop($shop->id)->find($clipId);
                if ($clip) {
                    Analytics::recordShare($clip, $eventData);
                }
                break;
        }

        return response()->json(['success' => true]);
    }
}
