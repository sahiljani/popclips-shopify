<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Analytics;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function appUninstalled(Request $request): JsonResponse
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        Log::info('App uninstalled webhook received', ['shop' => $shopDomain]);

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if ($shop) {
            $shop->update([
                'is_active' => false,
                'access_token' => null,
            ]);

            $shop->subscriptions()->active()->each(function ($subscription) {
                $subscription->cancel();
            });
        }

        return response()->json(['success' => true]);
    }

    public function shopUpdate(Request $request): JsonResponse
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $data = $request->all();

        Log::info('Shop update webhook received', ['shop' => $shopDomain]);

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if ($shop) {
            $shop->update([
                'name' => $data['name'] ?? $shop->name,
                'email' => $data['email'] ?? $shop->email,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function ordersPaid(Request $request): JsonResponse
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $data = $request->all();

        Log::info('Order paid webhook received', ['shop' => $shopDomain, 'order_id' => $data['id'] ?? null]);

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (! $shop) {
            return response()->json(['success' => true]);
        }

        $orderNote = $data['note_attributes'] ?? [];
        $popclipsSession = null;
        $clipId = null;
        $hotspotId = null;

        foreach ($orderNote as $attribute) {
            if ($attribute['name'] === 'popclips_session') {
                $popclipsSession = $attribute['value'];
            }
            if ($attribute['name'] === 'popclips_clip_id') {
                $clipId = $attribute['value'];
            }
            if ($attribute['name'] === 'popclips_hotspot_id') {
                $hotspotId = $attribute['value'];
            }
        }

        if ($popclipsSession) {
            $totalPrice = $data['total_price'] ?? 0;
            $currency = $data['currency'] ?? 'USD';

            Analytics::recordPurchase($shop, [
                'clip_id' => $clipId,
                'hotspot_id' => $hotspotId,
                'revenue' => $totalPrice,
                'currency' => $currency,
                'session_id' => $popclipsSession,
                'metadata' => [
                    'order_id' => $data['id'],
                    'order_number' => $data['order_number'] ?? null,
                ],
            ]);

            Log::info('Purchase attributed to Popclips', [
                'shop' => $shopDomain,
                'order_id' => $data['id'],
                'revenue' => $totalPrice,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function customersDataRequest(Request $request): JsonResponse
    {
        Log::info('Customer data request webhook received');

        return response()->json(['success' => true]);
    }

    public function customersRedact(Request $request): JsonResponse
    {
        Log::info('Customer redact webhook received');

        return response()->json(['success' => true]);
    }

    public function shopRedact(Request $request): JsonResponse
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        Log::info('Shop redact webhook received', ['shop' => $shopDomain]);

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if ($shop) {
            $shop->clips()->forceDelete();
            $shop->carousels()->forceDelete();
            $shop->analytics()->delete();
            $shop->subscriptions()->delete();
            $shop->forceDelete();
        }

        return response()->json(['success' => true]);
    }
}
