const API_BASE = '/api/v1';

/**
 * Get shop domain from URL, meta tag, Shopify admin path, or localStorage
 */
function getShopDomain() {
  // First try URL parameter (works when app passes ?shop=xxx)
  const urlParams = new URLSearchParams(window.location.search);
  let shopDomain = urlParams.get('shop');
  
  if (shopDomain) {
    // Store in localStorage for future use
    localStorage.setItem('shop_domain', shopDomain);
    return shopDomain;
  }

  // Try meta tag set by backend
  const metaShop = document.querySelector('meta[name="shop-domain"]');
  if (metaShop && metaShop.content) {
    shopDomain = metaShop.content;
    localStorage.setItem('shop_domain', shopDomain);
    return shopDomain;
  }

  // Try to extract from Shopify admin URL path: admin.shopify.com/store/{shop-name}/apps/...
  // or from parent frame URL if embedded
  try {
    const currentUrl = window.location.href;
    const adminMatch = currentUrl.match(/admin\.shopify\.com\/store\/([^/]+)/);
    if (adminMatch && adminMatch[1]) {
      shopDomain = `${adminMatch[1]}.myshopify.com`;
      localStorage.setItem('shop_domain', shopDomain);
      return shopDomain;
    }
  } catch (e) {
    // Ignore errors from cross-origin access
  }

  // Try to get from the embedded iframe's src URL
  try {
    if (window.self !== window.top) {
      // We're in an iframe, check if we can access referrer
      const referrer = document.referrer;
      if (referrer) {
        const referrerMatch = referrer.match(/admin\.shopify\.com\/store\/([^/]+)/);
        if (referrerMatch && referrerMatch[1]) {
          shopDomain = `${referrerMatch[1]}.myshopify.com`;
          localStorage.setItem('shop_domain', shopDomain);
          return shopDomain;
        }
      }
    }
  } catch (e) {
    // Ignore cross-origin errors
  }
  
  // Fallback to localStorage
  shopDomain = localStorage.getItem('shop_domain');
  
  if (!shopDomain) {
    console.error('No shop domain found. Please add ?shop=your-shop.myshopify.com to the URL');
  }
  
  return shopDomain;
}

async function request(endpoint, options = {}) {
  const shopDomain = getShopDomain();

  if (!shopDomain) {
    throw new Error('Shop domain is required. Please add ?shop=your-shop.myshopify.com to the URL');
  }

  const headers = {
    'Content-Type': 'application/json',
    'X-Shop-Domain': shopDomain,
    ...options.headers,
  };

  const url = `${API_BASE}${endpoint}${endpoint.includes('?') ? '&' : '?'}shop=${shopDomain}`;

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    
    // Enhanced error message with debugging info
    let errorMessage = error.message || error.error || 'Request failed';
    
    if (error.hint) {
      errorMessage += '\n\nHint: ' + error.hint;
    }
    
    if (error.active_shops && error.active_shops.length > 0) {
      errorMessage += '\n\nAvailable shops: ' + error.active_shops.join(', ');
    }
    
    if (error.provided_domain) {
      errorMessage += '\n\nYou provided: ' + error.provided_domain;
    }
    
    console.error('API Error:', error);
    throw new Error(errorMessage);
  }

  if (response.status === 204) {
    return null;
  }

  return response.json();
}

export const api = {
  // Clips
  getClips: (page = 1) => request(`/clips?page=${page}`),
  getClip: (id) => request(`/clips/${id}`),
  createClip: (data) => request('/clips', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  updateClip: (id, data) => request(`/clips/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  deleteClip: (id) => request(`/clips/${id}`, { method: 'DELETE' }),
  publishClip: (id) => request(`/clips/${id}/publish`, { method: 'POST' }),
  unpublishClip: (id) => request(`/clips/${id}/unpublish`, { method: 'POST' }),
  getUploadStatus: (id) => request(`/clips/${id}/upload-status`),

  // Hotspots
  getHotspots: (clipId) => request(`/clips/${clipId}/hotspots`),
  createHotspot: (clipId, data) => request(`/clips/${clipId}/hotspots`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  updateHotspot: (clipId, id, data) => request(`/clips/${clipId}/hotspots/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  deleteHotspot: (clipId, id) => request(`/clips/${clipId}/hotspots/${id}`, {
    method: 'DELETE',
  }),

  // Products
  searchProducts: (query) => request(`/products/search?q=${encodeURIComponent(query)}`),

  // Shopify Files
  createShopifyStagedUpload: (payload) => request('/files/staged-upload', {
    method: 'POST',
    body: JSON.stringify(payload),
  }),
  completeShopifyUpload: (payload) => request('/files/complete', {
    method: 'POST',
    body: JSON.stringify(payload),
  }),
  getShopifyFileStatus: (fileId) => request(`/files/${encodeURIComponent(fileId)}/status`),
  listShopifyFiles: (query = '', after = null) => {
    const params = new URLSearchParams();
    if (query) {
      params.set('q', query);
    }
    if (after) {
      params.set('after', after);
    }
    const queryString = params.toString();
    return request(`/files${queryString ? `?${queryString}` : ''}`);
  },

  // Carousels
  getCarousels: () => request('/carousels'),
  getCarousel: (id) => request(`/carousels/${id}`),
  createCarousel: (data) => request('/carousels', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  updateCarousel: (id, data) => request(`/carousels/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  deleteCarousel: (id) => request(`/carousels/${id}`, { method: 'DELETE' }),
  addClipsToCarousel: (id, clipIds) => request(`/carousels/${id}/clips`, {
    method: 'POST',
    body: JSON.stringify({ clip_ids: clipIds }),
  }),
  removeClipFromCarousel: (id, clipId) => request(`/carousels/${id}/clips/${clipId}`, {
    method: 'DELETE',
  }),
  reorderCarouselClips: (id, clipIds) => request(`/carousels/${id}/clips/reorder`, {
    method: 'PUT',
    body: JSON.stringify({ clip_ids: clipIds }),
  }),

  // Analytics
  getOverview: (days = 7) => request(`/analytics/overview?days=${days}`),
  getViewsOverTime: (days = 7) => request(`/analytics/views-over-time?days=${days}`),
  getTopClips: (days = 7, limit = 5) => request(`/analytics/top-clips?days=${days}&limit=${limit}`),
  getTopProducts: (days = 7, limit = 5) => request(`/analytics/top-products?days=${days}&limit=${limit}`),
  getClipAnalytics: (clipId, days = 7) => request(`/analytics/clips/${clipId}?days=${days}`),
  exportAnalytics: (days = 30) => request(`/analytics/export?days=${days}`),

  // Subscription
  getSubscription: () => request('/subscription'),
  upgradeToPro: () => request('/subscription/upgrade', { method: 'POST' }),
  cancelSubscription: () => request('/subscription/cancel', { method: 'POST' }),
};
