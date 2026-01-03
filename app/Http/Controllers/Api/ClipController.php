<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClipController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clips = Clip::forShop($shop->id)
            ->with('hotspots')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($clips);
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'shopify_file_id' => 'required|string|max:255',
            'video_url' => 'nullable|url',
            'thumbnail_url' => 'nullable|url',
            'duration' => 'nullable|integer|min:0',
            'file_size' => 'nullable|integer|min:0',
            'aspect_ratio' => 'nullable|string|max:20',
            'original_filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $monthlyUploads = Clip::forShop($shop->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $limit = $shop->isPro() ? 50 : 10;

        if ($monthlyUploads >= $limit) {
            return response()->json([
                'error' => 'Monthly upload limit reached',
                'limit' => $limit,
                'current' => $monthlyUploads,
            ], 403);
        }

        $clip = Clip::create([
            'shop_id' => $shop->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'video_url' => $request->input('video_url'),
            'thumbnail_url' => $request->input('thumbnail_url'),
            'shopify_file_id' => $request->input('shopify_file_id'),
            'duration' => $request->input('duration'),
            'file_size' => $request->input('file_size'),
            'aspect_ratio' => $request->input('aspect_ratio', '9:16'),
            'status' => $request->input('video_url') ? 'ready' : 'processing',
        ]);

        return response()->json($clip, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)
            ->with('hotspots')
            ->findOrFail($id);

        return response()->json($clip);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_published' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clip->update($request->only(['title', 'description', 'is_published']));

        if ($request->has('is_published') && $request->boolean('is_published') && ! $clip->published_at) {
            $clip->publish();
        }

        return response()->json($clip->fresh('hotspots'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($id);

        // Video is stored in Shopify, no CDN cleanup needed
        $clip->delete();

        return response()->json(null, 204);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($id);

        if ($clip->status !== 'ready') {
            return response()->json(['error' => 'Clip is not ready for publishing'], 400);
        }

        $clip->publish();

        return response()->json($clip);
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($id);

        $clip->unpublish();

        return response()->json($clip);
    }

    public function uploadStatus(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($id);

        return response()->json([
            'id' => $clip->id,
            'status' => $clip->status,
            'video_url' => $clip->video_url,
            'thumbnail_url' => $clip->thumbnail_url,
            'duration' => $clip->duration,
        ]);
    }
}
