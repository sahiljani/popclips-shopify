<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateShop
{
    public function handle(Request $request, Closure $next): Response
    {
        $shopDomain = $request->query('shop') ?? $request->header('X-Shop-Domain');

        if (!$shopDomain) {
            return response()->json(['error' => 'Shop domain required'], 401);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)
            ->where('is_active', true)
            ->first();

        if (!$shop) {
            return response()->json(['error' => 'Shop not found or inactive'], 401);
        }

        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
