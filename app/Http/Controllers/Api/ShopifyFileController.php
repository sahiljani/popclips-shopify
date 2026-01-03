<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyFileController extends Controller
{
    public function __construct(public ShopifyService $shopifyService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $files = $this->shopifyService->listVideoFiles(
            $shop,
            $request->query('q'),
            $request->query('after')
        );

        if (!$files) {
            return response()->json(['error' => 'Unable to fetch files from Shopify'], 422);
        }

        $items = collect($files['edges'] ?? [])->map(function ($edge) {
            $node = $edge['node'];
            $source = $node['sources'][0] ?? null;

            return [
                'id' => $node['id'],
                'cursor' => $edge['cursor'] ?? null,
                'url' => $source['url'] ?? null,
                'thumbnail_url' => $node['preview']['image']['url'] ?? null,
                'duration' => $node['duration'] ?? null,
                'file_status' => $node['fileStatus'] ?? null,
                'original_filename' => $node['originalFilename'] ?? null,
                'mime_type' => $source['mimeType'] ?? null,
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'page_info' => $files['pageInfo'] ?? [],
        ]);
    }

    public function stagedUpload(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
            'mime_type' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1|max:524288000',
        ]);

        $target = $this->shopifyService->createStagedUpload(
            $shop,
            $validated['file_name'],
            $validated['mime_type'],
            (int) $validated['file_size']
        );

        if (!$target) {
            return response()->json(['error' => 'Unable to create staged upload target'], 422);
        }

        return response()->json($target);
    }

    public function complete(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'resource_url' => 'required|url',
            'file_name' => 'required|string|max:255',
            'alt' => 'nullable|string|max:255',
        ]);

        $file = $this->shopifyService->completeFileUpload(
            $shop,
            $validated['resource_url'],
            $validated['file_name'],
            $validated['alt'] ?? null
        );

        if (!$file) {
            return response()->json(['error' => 'Unable to finalize Shopify file'], 422);
        }

        return response()->json($file);
    }
}
