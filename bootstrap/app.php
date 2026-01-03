<?php

use App\Http\Middleware\AuthenticateShop;
use App\Http\Middleware\ShopifyFrameHeaders;
use App\Http\Middleware\VerifyAppProxyRequest;
use App\Http\Middleware\VerifyShopifyRequest;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Cloudflare and reverse proxy headers
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );

        // Allow Shopify to embed the app
        $middleware->append(ShopifyFrameHeaders::class);

        $middleware->alias([
            'shopify.auth' => AuthenticateShop::class,
            'shopify.verify' => VerifyShopifyRequest::class,
            'shopify.webhook' => VerifyShopifyWebhook::class,
            'app.proxy' => VerifyAppProxyRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
