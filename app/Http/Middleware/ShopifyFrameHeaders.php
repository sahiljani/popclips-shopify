<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopifyFrameHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Allow Shopify to embed this app in an iframe
        $response->headers->set('Content-Security-Policy', "frame-ancestors https://*.myshopify.com https://admin.shopify.com");
        
        // Remove X-Frame-Options as CSP frame-ancestors takes precedence
        $response->headers->remove('X-Frame-Options');

        return $response;
    }
}
