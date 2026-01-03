const API_BASE = '/api/v1';

async function request(endpoint, options = {}) {
  const shopDomain = new URLSearchParams(window.location.search).get('shop');

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
    throw new Error(error.message || error.error || 'Request failed');
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
