<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analytics;
use App\Models\Clip;
use App\Models\Hotspot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $days = $request->query('days', 7);

        $startDate = now()->subDays($days);

        $views = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_CLIP_VIEW)
            ->where('created_at', '>=', $startDate)
            ->count();

        $hotspotClicks = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_HOTSPOT_CLICK)
            ->where('created_at', '>=', $startDate)
            ->count();

        $addToCarts = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_ADD_TO_CART)
            ->where('created_at', '>=', $startDate)
            ->count();

        $revenue = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_PURCHASE)
            ->where('created_at', '>=', $startDate)
            ->sum('revenue');

        $ctr = $views > 0 ? round(($hotspotClicks / $views) * 100, 2) : 0;

        $previousStartDate = $startDate->copy()->subDays($days);

        $previousViews = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_CLIP_VIEW)
            ->whereBetween('created_at', [$previousStartDate, $startDate])
            ->count();

        $previousRevenue = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_PURCHASE)
            ->whereBetween('created_at', [$previousStartDate, $startDate])
            ->sum('revenue');

        return response()->json([
            'total_views' => $views,
            'views_change' => $previousViews > 0 ? round((($views - $previousViews) / $previousViews) * 100, 1) : 0,
            'ctr' => $ctr,
            'add_to_cart' => $addToCarts,
            'revenue' => $revenue,
            'revenue_change' => $previousRevenue > 0 ? round($revenue - $previousRevenue, 2) : 0,
            'clips_count' => Clip::forShop($shop->id)->count(),
        ]);
    }

    public function viewsOverTime(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $days = $request->query('days', 7);

        $startDate = now()->subDays($days);

        $views = Analytics::forShop($shop->id)
            ->ofType(Analytics::EVENT_CLIP_VIEW)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($views);
    }

    public function topClips(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $days = $request->query('days', 7);
        $limit = $request->query('limit', 5);

        $startDate = now()->subDays($days);

        $topClips = Clip::forShop($shop->id)
            ->withCount(['analytics as views_count' => function ($query) use ($startDate) {
                $query->where('event_type', Analytics::EVENT_CLIP_VIEW)
                    ->where('created_at', '>=', $startDate);
            }])
            ->withCount(['analytics as clicks_count' => function ($query) use ($startDate) {
                $query->where('event_type', Analytics::EVENT_HOTSPOT_CLICK)
                    ->where('created_at', '>=', $startDate);
            }])
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($clip) {
                $ctr = $clip->views_count > 0
                    ? round(($clip->clicks_count / $clip->views_count) * 100, 1)
                    : 0;

                return [
                    'id' => $clip->id,
                    'title' => $clip->title,
                    'thumbnail_url' => $clip->thumbnail_url,
                    'views' => $clip->views_count,
                    'ctr' => $ctr,
                ];
            });

        return response()->json($topClips);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $days = $request->query('days', 7);
        $limit = $request->query('limit', 5);

        $startDate = now()->subDays($days);

        $topProducts = Analytics::forShop($shop->id)
            ->whereIn('event_type', [Analytics::EVENT_HOTSPOT_CLICK, Analytics::EVENT_ADD_TO_CART])
            ->whereNotNull('shopify_product_id')
            ->where('created_at', '>=', $startDate)
            ->select(
                'shopify_product_id',
                DB::raw('COUNT(CASE WHEN event_type = "hotspot_click" THEN 1 END) as clicks'),
                DB::raw('COUNT(CASE WHEN event_type = "add_to_cart" THEN 1 END) as add_to_cart')
            )
            ->groupBy('shopify_product_id')
            ->orderBy('clicks', 'desc')
            ->limit($limit)
            ->get();

        $result = $topProducts->map(function ($product) use ($shop) {
            $hotspot = Hotspot::where('shopify_product_id', $product->shopify_product_id)
                ->whereHas('clip', function ($query) use ($shop) {
                    $query->where('shop_id', $shop->id);
                })
                ->first();

            return [
                'product_id' => $product->shopify_product_id,
                'title' => $hotspot?->product_title ?? 'Unknown Product',
                'image' => $hotspot?->product_image,
                'clicks' => $product->clicks,
                'add_to_cart' => $product->add_to_cart,
            ];
        });

        return response()->json($result);
    }

    public function clipAnalytics(Request $request, int $clipId): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($clipId);

        $days = $request->query('days', 7);
        $startDate = now()->subDays($days);

        $views = Analytics::forClip($clip->id)
            ->ofType(Analytics::EVENT_CLIP_VIEW)
            ->where('created_at', '>=', $startDate)
            ->count();

        $completions = Analytics::forClip($clip->id)
            ->ofType(Analytics::EVENT_CLIP_COMPLETE)
            ->where('created_at', '>=', $startDate)
            ->count();

        $hotspotClicks = Analytics::forClip($clip->id)
            ->ofType(Analytics::EVENT_HOTSPOT_CLICK)
            ->where('created_at', '>=', $startDate)
            ->count();

        $addToCarts = Analytics::forClip($clip->id)
            ->ofType(Analytics::EVENT_ADD_TO_CART)
            ->where('created_at', '>=', $startDate)
            ->count();

        $hotspotStats = $clip->hotspots->map(function ($hotspot) use ($startDate) {
            $clicks = Analytics::forHotspot($hotspot->id)
                ->ofType(Analytics::EVENT_HOTSPOT_CLICK)
                ->where('created_at', '>=', $startDate)
                ->count();

            $addToCarts = Analytics::forHotspot($hotspot->id)
                ->ofType(Analytics::EVENT_ADD_TO_CART)
                ->where('created_at', '>=', $startDate)
                ->count();

            return [
                'id' => $hotspot->id,
                'product_title' => $hotspot->product_title,
                'clicks' => $clicks,
                'add_to_cart' => $addToCarts,
            ];
        });

        return response()->json([
            'clip' => [
                'id' => $clip->id,
                'title' => $clip->title,
                'thumbnail_url' => $clip->thumbnail_url,
            ],
            'views' => $views,
            'completions' => $completions,
            'completion_rate' => $views > 0 ? round(($completions / $views) * 100, 1) : 0,
            'hotspot_clicks' => $hotspotClicks,
            'add_to_cart' => $addToCarts,
            'ctr' => $views > 0 ? round(($hotspotClicks / $views) * 100, 1) : 0,
            'hotspots' => $hotspotStats,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $days = $request->query('days', 30);

        if (! $shop->isPro()) {
            return response()->json([
                'error' => 'Analytics export requires a Pro subscription',
            ], 403);
        }

        $startDate = now()->subDays($days);

        $data = Analytics::forShop($shop->id)
            ->with(['clip:id,title', 'hotspot:id,product_title'])
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'date' => $event->created_at->toDateTimeString(),
                    'event_type' => $event->event_type,
                    'clip' => $event->clip?->title,
                    'product' => $event->hotspot?->product_title,
                    'revenue' => $event->revenue,
                    'device' => $event->device_type,
                    'country' => $event->country,
                ];
            });

        return response()->json($data);
    }
}
