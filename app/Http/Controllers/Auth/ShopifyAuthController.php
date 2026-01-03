<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopifyAuthController extends Controller
{
    public function __construct(protected ShopifyService $shopifyService) {}

    public function install(Request $request)
    {
        $shopDomain = $request->query('shop');

        if (! $shopDomain) {
            return response()->json(['error' => 'Shop domain required'], 400);
        }

        $shopDomain = $this->sanitizeShopDomain($shopDomain);

        if (! $this->isValidShopDomain($shopDomain)) {
            return response()->json(['error' => 'Invalid shop domain'], 400);
        }

        $state = Str::random(40);

        $redirectUri = route('shopify.callback');
        $authUrl = $this->shopifyService->getAuthUrl($shopDomain, $redirectUri, $state);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        $shopDomain = $request->query('shop');
        $state = $request->query('state');

        \Log::info('Shopify OAuth Callback', [
            'has_code' => ! empty($code),
            'shop' => $shopDomain,
            'has_state' => ! empty($state),
        ]);

        if (! $code || ! $shopDomain) {
            \Log::error('Missing OAuth parameters');

            return redirect()->route('error')->with('error', 'Missing required parameters');
        }

        // Verify HMAC signature - this is the most reliable method
        if (! $this->shopifyService->verifyRequest($request->query())) {
            \Log::error('HMAC verification failed', ['query' => $request->query()]);

            return redirect()->route('error')->with('error', 'Invalid request signature');
        }

        $accessToken = $this->shopifyService->getAccessToken($shopDomain, $code);

        if (! $accessToken) {
            \Log::error('Failed to get access token');

            return redirect()->route('error')->with('error', 'Failed to get access token');
        }

        $shopInfo = $this->shopifyService->getShopInfo($shopDomain, $accessToken);

        $shop = Shop::updateOrCreate(
            ['shopify_domain' => $shopDomain],
            [
                'name' => $shopInfo['name'] ?? null,
                'email' => $shopInfo['email'] ?? null,
                'access_token' => $accessToken,
                'scopes' => explode(',', config('services.shopify.scopes')),
                'is_active' => true,
            ]
        );

        $this->registerWebhooks($shop);

        \Log::info('Shopify app installed successfully', ['shop' => $shopDomain]);

        return redirect()->route('admin.dashboard', ['shop' => $shopDomain]);
    }

    protected function registerWebhooks(Shop $shop): void
    {
        $webhooks = [
            'app/uninstalled' => route('webhooks.app.uninstalled'),
            'shop/update' => route('webhooks.shop.update'),
            'orders/paid' => route('webhooks.orders.paid'),
        ];

        foreach ($webhooks as $topic => $address) {
            $this->shopifyService->registerWebhook($shop, $topic, $address);
        }
    }

    protected function sanitizeShopDomain(string $shop): string
    {
        $shop = strtolower(trim($shop));

        if (str_contains($shop, 'https://')) {
            $shop = str_replace('https://', '', $shop);
        }

        if (str_contains($shop, 'http://')) {
            $shop = str_replace('http://', '', $shop);
        }

        $shop = rtrim($shop, '/');

        if (! str_ends_with($shop, '.myshopify.com')) {
            $shop = $shop.'.myshopify.com';
        }

        return $shop;
    }

    protected function isValidShopDomain(string $shop): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9\-]*\.myshopify\.com$/', $shop) === 1;
    }
}
