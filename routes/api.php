<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CarouselController;
use App\Http\Controllers\Api\ClipController;
use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\ShopifyFileController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Storefront\StorefrontController;
use App\Http\Controllers\Webhook\ShopifyWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['shopify.auth', 'shopify.verify'])->group(function () {
        Route::get('/clips', [ClipController::class, 'index']);
        Route::post('/clips', [ClipController::class, 'store']);
        Route::get('/clips/{id}', [ClipController::class, 'show']);
        Route::put('/clips/{id}', [ClipController::class, 'update']);
        Route::delete('/clips/{id}', [ClipController::class, 'destroy']);
        Route::post('/clips/{id}/publish', [ClipController::class, 'publish']);
        Route::post('/clips/{id}/unpublish', [ClipController::class, 'unpublish']);
        Route::get('/clips/{id}/upload-status', [ClipController::class, 'uploadStatus']);

        Route::get('/files', [ShopifyFileController::class, 'index']);
        Route::post('/files/staged-upload', [ShopifyFileController::class, 'stagedUpload']);
        Route::post('/files/complete', [ShopifyFileController::class, 'complete']);

        Route::get('/clips/{clipId}/hotspots', [HotspotController::class, 'index']);
        Route::post('/clips/{clipId}/hotspots', [HotspotController::class, 'store']);
        Route::get('/clips/{clipId}/hotspots/{id}', [HotspotController::class, 'show']);
        Route::put('/clips/{clipId}/hotspots/{id}', [HotspotController::class, 'update']);
        Route::delete('/clips/{clipId}/hotspots/{id}', [HotspotController::class, 'destroy']);

        Route::get('/carousels', [CarouselController::class, 'index']);
        Route::post('/carousels', [CarouselController::class, 'store']);
        Route::get('/carousels/{id}', [CarouselController::class, 'show']);
        Route::put('/carousels/{id}', [CarouselController::class, 'update']);
        Route::delete('/carousels/{id}', [CarouselController::class, 'destroy']);
        Route::post('/carousels/{id}/clips', [CarouselController::class, 'addClips']);
        Route::delete('/carousels/{id}/clips/{clipId}', [CarouselController::class, 'removeClip']);
        Route::put('/carousels/{id}/clips/reorder', [CarouselController::class, 'reorderClips']);

        Route::get('/products/search', [HotspotController::class, 'searchProducts']);

        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('/analytics/views-over-time', [AnalyticsController::class, 'viewsOverTime']);
        Route::get('/analytics/top-clips', [AnalyticsController::class, 'topClips']);
        Route::get('/analytics/top-products', [AnalyticsController::class, 'topProducts']);
        Route::get('/analytics/clips/{clipId}', [AnalyticsController::class, 'clipAnalytics']);
        Route::get('/analytics/export', [AnalyticsController::class, 'export']);

        Route::get('/subscription', [SubscriptionController::class, 'current']);
        Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    });

    Route::middleware('app.proxy')->prefix('storefront')->group(function () {
        Route::get('/carousel', [StorefrontController::class, 'carousel']);
        Route::get('/clips/{id}', [StorefrontController::class, 'clip']);
        Route::post('/track', [StorefrontController::class, 'trackEvent']);
    });
});

Route::prefix('webhooks')->middleware('shopify.webhook')->group(function () {
    Route::post('/app/uninstalled', [ShopifyWebhookController::class, 'appUninstalled'])->name('webhooks.app.uninstalled');
    Route::post('/shop/update', [ShopifyWebhookController::class, 'shopUpdate'])->name('webhooks.shop.update');
    Route::post('/orders/paid', [ShopifyWebhookController::class, 'ordersPaid'])->name('webhooks.orders.paid');

    Route::post('/customers/data_request', [ShopifyWebhookController::class, 'customersDataRequest']);
    Route::post('/customers/redact', [ShopifyWebhookController::class, 'customersRedact']);
    Route::post('/shop/redact', [ShopifyWebhookController::class, 'shopRedact']);
});

Route::post('/cloudinary/webhook', function () {
    return response()->json(['success' => true]);
})->name('cloudinary.webhook');
