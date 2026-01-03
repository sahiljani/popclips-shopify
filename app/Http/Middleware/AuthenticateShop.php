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
        // Try to get shop domain from multiple sources
        $shopDomain = $request->query('shop') 
            ?? $request->header('X-Shop-Domain')
            ?? $request->session()->get('shop_domain');

        if (! $shopDomain) {
            // Get all active shops to help with debugging
            $activeShops = Shop::where('is_active', true)->pluck('shopify_domain');
            
            return response()->json([
                'error' => 'Shop domain required',
                'message' => 'Please provide a shop parameter in the URL or header',
                'hint' => $activeShops->isNotEmpty() 
                    ? 'Try adding ?shop=' . $activeShops->first() . ' to your URL'
                    : 'No active shops found in database. Please install the app first.'
            ], 401);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)
            ->where('is_active', true)
            ->first();

        if (! $shop) {
            // Try to find any shop with this domain (might be inactive)
            $inactiveShop = Shop::where('shopify_domain', $shopDomain)->first();
            
            if ($inactiveShop && ! $inactiveShop->is_active) {
                return response()->json([
                    'error' => 'Shop is inactive',
                    'message' => 'This shop exists but is currently inactive',
                    'shop_domain' => $shopDomain,
                ], 401);
            }
            
            // Get active shops to help with debugging
            $activeShops = Shop::where('is_active', true)->pluck('shopify_domain');
            
            return response()->json([
                'error' => 'Shop not found',
                'message' => 'No shop found with domain: ' . $shopDomain,
                'provided_domain' => $shopDomain,
                'active_shops' => $activeShops->toArray(),
                'hint' => $activeShops->isNotEmpty()
                    ? 'Did you mean: ?shop=' . $activeShops->first()
                    : 'Please install the app to register your shop'
            ], 404);
        }

        // Store shop domain in session for future requests
        $request->session()->put('shop_domain', $shopDomain);
        
        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
