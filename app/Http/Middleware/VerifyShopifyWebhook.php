<?php

namespace App\Http\Middleware;

use App\Services\ShopifyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyWebhook
{
    public function __construct(protected ShopifyService $shopifyService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

        if (!$hmacHeader) {
            return response()->json(['error' => 'Missing HMAC header'], 401);
        }

        $data = $request->getContent();

        if (!$this->shopifyService->verifyWebhook($data, $hmacHeader)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        return $next($request);
    }
}
