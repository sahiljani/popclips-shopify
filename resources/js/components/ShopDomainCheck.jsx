import React, { useState, useEffect } from 'react';
import { Banner } from '@shopify/polaris';

export default function ShopDomainCheck() {
    const [shopDomain, setShopDomain] = useState(null);
    const [showBanner, setShowBanner] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const urlShop = urlParams.get('shop');
        const storedShop = localStorage.getItem('shop_domain');

        if (!urlShop && storedShop) {
            // We have a stored shop but not in URL, add it
            setShopDomain(storedShop);
            setShowBanner(true);
        } else if (!urlShop && !storedShop) {
            // No shop at all, fetch from backend
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
                    setShopDomain(error.active_shops[0]);
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
