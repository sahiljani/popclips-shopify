<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(protected ShopifyService $shopifyService)
    {
    }

    public function current(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $subscription = $shop->activeSubscription;

        return response()->json([
            'plan' => $shop->plan,
            'subscription' => $subscription,
            'features' => $this->getPlanFeatures($shop->plan),
            'usage' => $this->getUsage($shop),
        ]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        if ($shop->isPro()) {
            return response()->json([
                'error' => 'Already on Pro plan',
            ], 400);
        }

        $returnUrl = route('subscription.callback', [
            'shop' => $shop->shopify_domain,
        ]);

        $charge = $this->shopifyService->createRecurringCharge(
            $shop,
            'Popclips Pro',
            Subscription::PRO_PRICE,
            $returnUrl
        );

        if (!$charge) {
            return response()->json([
                'error' => 'Failed to create subscription charge',
            ], 500);
        }

        Subscription::createProSubscription($shop, $charge['id']);

        return response()->json([
            'confirmation_url' => $charge['confirmation_url'],
        ]);
    }

    public function callback(Request $request): mixed
    {
        $shop = $request->attributes->get('shop');
        $chargeId = $request->query('charge_id');

        if (!$chargeId) {
            return redirect()->route('admin.settings', ['shop' => $shop->shopify_domain])
                ->with('error', 'Missing charge ID');
        }

        $charge = $this->shopifyService->getRecurringCharge($shop, $chargeId);

        if (!$charge) {
            return redirect()->route('admin.settings', ['shop' => $shop->shopify_domain])
                ->with('error', 'Failed to verify charge');
        }

        $subscription = Subscription::where('shopify_charge_id', $chargeId)->first();

        if (!$subscription) {
            return redirect()->route('admin.settings', ['shop' => $shop->shopify_domain])
                ->with('error', 'Subscription not found');
        }

        if ($charge['status'] === 'active') {
            $subscription->activate();

            return redirect()->route('admin.settings', ['shop' => $shop->shopify_domain])
                ->with('success', 'Successfully upgraded to Pro!');
        }

        $subscription->update(['status' => $charge['status']]);

        return redirect()->route('admin.settings', ['shop' => $shop->shopify_domain])
            ->with('error', 'Subscription was not activated');
    }

    public function cancel(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $subscription = $shop->activeSubscription;

        if (!$subscription || !$subscription->isPro()) {
            return response()->json([
                'error' => 'No active Pro subscription to cancel',
            ], 400);
        }

        if ($subscription->shopify_charge_id) {
            $this->shopifyService->cancelRecurringCharge($shop, $subscription->shopify_charge_id);
        }

        $subscription->cancel();

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'plan' => 'free',
        ]);
    }

    protected function getPlanFeatures(string $plan): array
    {
        $features = [
            'free' => [
                'standard_carousel' => true,
                'custom_carousels' => 0,
                'monthly_uploads' => 10,
                'basic_analytics' => true,
                'advanced_analytics' => false,
                'product_hotspots' => true,
                'priority_support' => false,
            ],
            'pro' => [
                'standard_carousel' => true,
                'custom_carousels' => 5,
                'monthly_uploads' => 50,
                'basic_analytics' => true,
                'advanced_analytics' => true,
                'product_hotspots' => true,
                'priority_support' => true,
            ],
        ];

        return $features[$plan] ?? $features['free'];
    }

    protected function getUsage($shop): array
    {
        $monthlyUploads = $shop->clips()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $customCarousels = $shop->carousels()->custom()->count();

        return [
            'monthly_uploads' => $monthlyUploads,
            'custom_carousels' => $customCarousels,
        ];
    }
}
