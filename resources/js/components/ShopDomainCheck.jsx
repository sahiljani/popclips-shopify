import React, { useState, useEffect } from 'react';
import { Banner } from '@shopify/polaris';

/**
 * Extract shop domain from various sources
 */
function extractShopDomain() {
    // 1. Try URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    let shop = urlParams.get('shop');
    if (shop) {
        localStorage.setItem('shop_domain', shop);
        return shop;
    }

    // 2. Try meta tag set by backend
    const metaShop = document.querySelector('meta[name="shop-domain"]');
    if (metaShop && metaShop.content) {
        shop = metaShop.content;
        localStorage.setItem('shop_domain', shop);
        return shop;
    }

    // 3. Try to extract from Shopify admin URL path
    try {
        const currentUrl = window.location.href;
        const adminMatch = currentUrl.match(/admin\.shopify\.com\/store\/([^/]+)/);
        if (adminMatch && adminMatch[1]) {
            shop = `${adminMatch[1]}.myshopify.com`;
            localStorage.setItem('shop_domain', shop);
            return shop;
        }
    } catch (e) {
        // Ignore
    }

    // 4. Try document referrer (for embedded apps)
    try {
        const referrer = document.referrer;
        if (referrer) {
            const referrerMatch = referrer.match(/admin\.shopify\.com\/store\/([^/]+)/);
            if (referrerMatch && referrerMatch[1]) {
                shop = `${referrerMatch[1]}.myshopify.com`;
                localStorage.setItem('shop_domain', shop);
                return shop;
            }
        }
    } catch (e) {
        // Ignore
    }

    // 5. Fallback to localStorage
    return localStorage.getItem('shop_domain');
}

export default function ShopDomainCheck() {
    const [shopDomain, setShopDomain] = useState(null);
    const [showBanner, setShowBanner] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const urlShop = urlParams.get('shop');
        
        // First try to extract from all available sources
        const extractedShop = extractShopDomain();
        
        if (extractedShop && !urlShop) {
            // We found a shop but it's not in URL - silently use it
            setShopDomain(extractedShop);
            // Don't show banner if we successfully extracted the shop
            // Only show banner if user explicitly needs to fix their URL
            setShowBanner(false);
        } else if (!extractedShop) {
            // No shop found anywhere, try to fetch from backend
            fetchActiveShops();
        }
    }, []);

    async function fetchActiveShops() {
        try {
            // Make a test request to get shop info from error
            const response = await fetch('/api/v1/clips?shop=test');
            if (!response.ok) {
                const error = await response.json();
                if (error.active_shops && error.active_shops.length > 0) {
                    const shop = error.active_shops[0];
                    localStorage.setItem('shop_domain', shop);
                    setShopDomain(shop);
                    setShowBanner(true);
                }
            }
        } catch (err) {
            console.error('Failed to fetch active shops:', err);
        }
    }

    function addShopToUrl() {
        if (shopDomain) {
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('shop', shopDomain);
            window.location.href = newUrl.toString();
        }
    }

    if (!showBanner || !shopDomain) {
        return null;
    }

    return (
        <Banner
            title="Shop parameter missing"
            status="warning"
            action={{ content: 'Fix URL', onAction: addShopToUrl }}
            onDismiss={() => setShowBanner(false)}
        >
            <p>
                Your URL is missing the shop parameter. Click "Fix URL" to add
                <strong> ?shop={shopDomain}</strong> to your current URL.
            </p>
        </Banner>
    );
}
