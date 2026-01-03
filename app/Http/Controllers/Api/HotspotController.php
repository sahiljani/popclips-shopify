<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clip;
use App\Models\Hotspot;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HotspotController extends Controller
{
    public function __construct(protected ShopifyService $shopifyService)
    {
    }

    public function index(Request $request, int $clipId): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($clipId);

        $hotspots = $clip->hotspots()->orderBy('start_time')->get();

        return response()->json($hotspots);
    }

    public function store(Request $request, int $clipId): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($clipId);

        $validator = Validator::make($request->all(), [
            'shopify_product_id' => 'required|string',
            'position_x' => 'required|numeric|min:0|max:100',
            'position_y' => 'required|numeric|min:0|max:100',
            'start_time' => 'required|numeric|min:0',
            'end_time' => 'required|numeric|gt:start_time',
            'animation_style' => 'in:pulse,static,bounce',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = $this->shopifyService->getProduct($shop, $request->input('shopify_product_id'));

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $hotspot = Hotspot::create([
            'clip_id' => $clip->id,
            'shopify_product_id' => $product['id'],
            'product_title' => $product['title'],
            'product_handle' => $product['handle'],
            'product_image' => $product['image']['src'] ?? ($product['images'][0]['src'] ?? null),
            'product_price' => $product['variants'][0]['price'] ?? null,
            'product_currency' => $shop->getSetting('currency', 'USD'),
            'position_x' => $request->input('position_x'),
            'position_y' => $request->input('position_y'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'animation_style' => $request->input('animation_style', 'pulse'),
        ]);

        return response()->json($hotspot, 201);
    }

    public function show(Request $request, int $clipId, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($clipId);

        $hotspot = Hotspot::where('clip_id', $clip->id)->findOrFail($id);

        return response()->json($hotspot);
    }

    public function update(Request $request, int $clipId, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($clipId);

        $hotspot = Hotspot::where('clip_id', $clip->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'position_x' => 'sometimes|numeric|min:0|max:100',
            'position_y' => 'sometimes|numeric|min:0|max:100',
            'start_time' => 'sometimes|numeric|min:0',
            'end_time' => 'sometimes|numeric',
            'animation_style' => 'in:pulse,static,bounce',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('start_time') || $request->has('end_time')) {
            $startTime = $request->input('start_time', $hotspot->start_time);
            $endTime = $request->input('end_time', $hotspot->end_time);

            if ($endTime <= $startTime) {
                return response()->json([
                    'errors' => ['end_time' => ['End time must be greater than start time']],
                ], 422);
            }
        }

        $hotspot->update($request->only([
            'position_x',
            'position_y',
            'start_time',
            'end_time',
            'animation_style',
        ]));

        return response()->json($hotspot);
    }

    public function destroy(Request $request, int $clipId, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $clip = Clip::forShop($shop->id)->findOrFail($clipId);

        $hotspot = Hotspot::where('clip_id', $clip->id)->findOrFail($id);

        $hotspot->delete();

        return response()->json(null, 204);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $query = $request->query('q', '');

        $products = $this->shopifyService->searchProducts($shop, $query);

        $formattedProducts = array_map(function ($product) {
            return [
                'id' => $product['id'],
                'title' => $product['title'],
                'handle' => $product['handle'],
                'image' => $product['image']['src'] ?? ($product['images'][0]['src'] ?? null),
                'price' => $product['variants'][0]['price'] ?? null,
                'inventory' => array_sum(array_column($product['variants'], 'inventory_quantity')),
            ];
        }, $products);

        return response()->json($formattedProducts);
    }
}
