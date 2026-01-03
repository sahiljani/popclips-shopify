<?php

namespace App\Http\Middleware;

use App\Services\ShopifyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyRequest
{
    public function __construct(protected ShopifyService $shopifyService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shopifyService->verifyRequest($request->query())) {
            return response()->json(['error' => 'Invalid request signature'], 401);
        }

        return $next($request);
    }
}
