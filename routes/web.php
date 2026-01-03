<?php

use App\Http\Controllers\Auth\ShopifyAuthController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    // If shop parameter exists, redirect to install flow
    if ($request->query('shop')) {
        return redirect()->route('shopify.install', ['shop' => $request->query('shop')]);
    }
    return view('admin');
});

Route::get('/install', [ShopifyAuthController::class, 'install'])->name('shopify.install');
Route::get('/auth/callback', [ShopifyAuthController::class, 'callback'])->name('shopify.callback');

Route::get('/admin/{any?}', function (Request $request) {
    // If no shop installed, redirect to OAuth
    $shopDomain = $request->query('shop');
    if ($shopDomain) {
        $shop = \App\Models\Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop || !$shop->access_token) {
            return redirect()->route('shopify.install', ['shop' => $shopDomain]);
        }
    }
    return view('admin');
})->where('any', '.*')->name('admin.dashboard');

Route::get('/subscription/callback', [SubscriptionController::class, 'callback'])
    ->middleware('shopify.auth')
    ->name('subscription.callback');

Route::get('/error', function () {
    return view('error');
})->name('error');
