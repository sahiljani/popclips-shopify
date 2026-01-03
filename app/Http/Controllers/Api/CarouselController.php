<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarouselController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousels = Carousel::forShop($shop->id)
            ->withCount('clips')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($carousels);
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:standard,custom',
            'display_location' => 'required|in:homepage,collection,product,all',
            'autoplay' => 'boolean',
            'autoplay_speed' => 'integer|min:1|max:30',
            'show_metrics' => 'boolean',
            'items_desktop' => 'integer|min:1|max:10',
            'items_tablet' => 'integer|min:1|max:6',
            'items_mobile' => 'integer|min:1|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->input('type') === 'custom' && !$shop->isPro()) {
            return response()->json([
                'error' => 'Custom carousels require a Pro subscription',
            ], 403);
        }

        if ($request->input('type') === 'custom') {
            $customCount = Carousel::forShop($shop->id)->custom()->count();
            if ($customCount >= 5) {
                return response()->json([
                    'error' => 'Maximum of 5 custom carousels allowed',
                ], 403);
            }
        }

        $carousel = Carousel::create([
            'shop_id' => $shop->id,
            ...$request->only([
                'name',
                'type',
                'display_location',
                'autoplay',
                'autoplay_speed',
                'show_metrics',
                'items_desktop',
                'items_tablet',
                'items_mobile',
            ]),
        ]);

        return response()->json($carousel, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousel = Carousel::forShop($shop->id)
            ->with(['clips' => function ($query) {
                $query->orderBy('carousel_clips.position');
            }])
            ->findOrFail($id);

        return response()->json($carousel);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousel = Carousel::forShop($shop->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'display_location' => 'sometimes|in:homepage,collection,product,all',
            'is_active' => 'boolean',
            'autoplay' => 'boolean',
            'autoplay_speed' => 'integer|min:1|max:30',
            'show_metrics' => 'boolean',
            'items_desktop' => 'integer|min:1|max:10',
            'items_tablet' => 'integer|min:1|max:6',
            'items_mobile' => 'integer|min:1|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $carousel->update($request->only([
            'name',
            'display_location',
            'is_active',
            'autoplay',
            'autoplay_speed',
            'show_metrics',
            'items_desktop',
            'items_tablet',
            'items_mobile',
        ]));

        return response()->json($carousel->fresh('clips'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousel = Carousel::forShop($shop->id)->findOrFail($id);

        if ($carousel->isStandard()) {
            return response()->json([
                'error' => 'Cannot delete the standard carousel',
            ], 400);
        }

        $carousel->delete();

        return response()->json(null, 204);
    }

    public function addClips(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousel = Carousel::forShop($shop->id)->findOrFail($id);

        if (!$carousel->isCustom()) {
            return response()->json([
                'error' => 'Can only add clips to custom carousels',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'clip_ids' => 'required|array',
            'clip_ids.*' => 'integer|exists:clips,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currentCount = $carousel->clips()->count();

        foreach ($request->input('clip_ids') as $clipId) {
            if (!$carousel->clips()->where('clip_id', $clipId)->exists()) {
                $carousel->clips()->attach($clipId, ['position' => $currentCount++]);
            }
        }

        return response()->json($carousel->fresh('clips'));
    }

    public function removeClip(Request $request, int $id, int $clipId): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousel = Carousel::forShop($shop->id)->findOrFail($id);

        if (!$carousel->isCustom()) {
            return response()->json([
                'error' => 'Can only remove clips from custom carousels',
            ], 400);
        }

        $carousel->clips()->detach($clipId);
        $carousel->reorderClips();

        return response()->json($carousel->fresh('clips'));
    }

    public function reorderClips(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $carousel = Carousel::forShop($shop->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'clip_ids' => 'required|array',
            'clip_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $carousel->updateClipPositions($request->input('clip_ids'));

        return response()->json($carousel->fresh('clips'));
    }
}
