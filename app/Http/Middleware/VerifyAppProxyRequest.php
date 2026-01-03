<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppProxyRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query();

        if (! isset($query['signature'])) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $signature = $query['signature'];
        unset($query['signature']);

        ksort($query);

        $params = [];
        foreach ($query as $key => $value) {
            $params[] = "{$key}={$value}";
        }
        $queryString = implode('', $params);

        $calculatedSignature = hash_hmac(
            'sha256',
            $queryString,
            config('services.shopify.api_secret')
        );

        if (! hash_equals($calculatedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $shopDomain = $query['shop'] ?? null;
        if ($shopDomain) {
            $shop = Shop::where('shopify_domain', $shopDomain)->first();
            if ($shop) {
                $request->attributes->set('shop', $shop);
            }
        }

        return $next($request);
    }
}
