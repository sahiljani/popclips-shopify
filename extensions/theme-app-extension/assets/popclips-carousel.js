/**
 * Popclips Video Carousel - Storefront JavaScript
 * Universal carousel that works on any Shopify theme
 */

(function() {
  'use strict';

  // Configuration
  const API_BASE = '/apps/popclips/api/v1/storefront';
  const SESSION_KEY = 'popclips_session';
  const VISITOR_KEY = 'popclips_visitor';

  // Generate unique IDs
  function generateId() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      const r = Math.random() * 16 | 0;
      const v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  // Get or create session/visitor IDs
  function getSessionId() {
    let id = sessionStorage.getItem(SESSION_KEY);
    if (!id) {
      id = generateId();
      sessionStorage.setItem(SESSION_KEY, id);
    }
    return id;
  }

  function getVisitorId() {
    let id = localStorage.getItem(VISITOR_KEY);
    if (!id) {
      id = generateId();
      localStorage.setItem(VISITOR_KEY, id);
    }
    return id;
  }

  // Device detection
  function getDeviceType() {
    const width = window.innerWidth;
    if (width < 480) return 'mobile';
    if (width < 768) return 'tablet';
    return 'desktop';
  }

  function getBrowser() {
    const ua = navigator.userAgent;
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Safari')) return 'Safari';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Edge')) return 'Edge';
    return 'Other';
  }

  // Format numbers
  function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
  }

  function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  // Popclips Carousel Class
  class PopclipsCarousel {
    constructor(element) {
      this.container = element;
      this.shopDomain = element.dataset.shop;
      this.carouselId = element.dataset.carouselId || '';
      this.location = element.dataset.location || 'homepage';

      this.slider = element.querySelector('[data-popclips-slider]');
      this.prevBtn = element.querySelector('[data-popclips-prev]');
      this.nextBtn = element.querySelector('[data-popclips-next]');
      this.dotsContainer = element.querySelector('[data-popclips-dots]');
      this.modal = element.querySelector('[data-popclips-modal]');
      this.player = element.querySelector('[data-popclips-player]');
      this.productsPanel = element.querySelector('[data-popclips-products]');

      this.clips = [];
      this.settings = {};
      this.currentClipIndex = 0;
      this.currentVideoTime = 0;
      this.videoElement = null;

      this.init();
    }

    async init() {
      try {
        await this.loadCarousel();
        this.render();
        this.bindEvents();
      } catch (error) {
        console.error('Popclips: Failed to initialize carousel', error);
        this.showError();
      }
    }

    async loadCarousel() {
      const params = new URLSearchParams({
        shop: this.shopDomain,
        location: this.location,
      });

      if (this.carouselId) {
        params.append('carousel_id', this.carouselId);
      }

      const response = await fetch(`${API_BASE}/carousel?${params}`);
      if (!response.ok) throw new Error('Failed to load carousel');

      const data = await response.json();
      this.clips = data.clips || [];
      this.settings = data.settings || {};
    }

    render() {
      if (this.clips.length === 0) {
        this.slider.innerHTML = '<div class="popclips-carousel__empty">No videos available</div>';
        return;
      }

      // Render clips
      this.slider.innerHTML = this.clips.map((clip, index) => this.renderClip(clip, index)).join('');

      // Render dots
      if (this.dotsContainer) {
        this.dotsContainer.innerHTML = this.clips.map((_, index) => `
          <button class="popclips-carousel__dot ${index === 0 ? 'active' : ''}" data-index="${index}"></button>
        `).join('');
      }

      // Add branding if enabled
      if (this.settings.showBranding !== false) {
        const branding = document.createElement('div');
        branding.className = 'popclips-branding';
        branding.innerHTML = 'Powered by <a href="https://popclips.app" target="_blank">Popclips</a>';
        this.container.querySelector('.popclips-container').appendChild(branding);
      }
    }

    renderClip(clip, index) {
      return `
        <div class="popclips-clip" data-clip-index="${index}" data-clip-id="${clip.id}">
          <div class="popclips-clip__wrapper">
            <img
              class="popclips-clip__thumbnail"
              src="${clip.thumbnailUrl}"
              alt="${clip.title}"
              loading="lazy"
            >
            <div class="popclips-clip__overlay">
              <span class="popclips-clip__duration">${formatDuration(clip.duration)}</span>
              <div class="popclips-clip__play">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
              </div>
              <div class="popclips-clip__info">
                <h3 class="popclips-clip__title">${clip.title}</h3>
                ${this.settings.showMetrics ? `
                  <div class="popclips-clip__stats">
                    <span class="popclips-clip__stat">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                      </svg>
                      ${formatNumber(clip.views)}
                    </span>
                    <span class="popclips-clip__stat">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                      </svg>
                      ${formatNumber(clip.likes)}
                    </span>
                  </div>
                ` : ''}
              </div>
            </div>
          </div>
        </div>
      `;
    }

    bindEvents() {
      // Clip click
      this.slider.addEventListener('click', (e) => {
        const clipEl = e.target.closest('.popclips-clip');
        if (clipEl) {
          const index = parseInt(clipEl.dataset.clipIndex, 10);
          this.openClip(index);
        }
      });

      // Navigation
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', () => this.scrollTo('prev'));
      }
      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', () => this.scrollTo('next'));
      }

      // Dots
      if (this.dotsContainer) {
        this.dotsContainer.addEventListener('click', (e) => {
          if (e.target.classList.contains('popclips-carousel__dot')) {
            const index = parseInt(e.target.dataset.index, 10);
            this.scrollToIndex(index);
          }
        });
      }

      // Modal close
      this.modal.querySelectorAll('[data-popclips-modal-close]').forEach(el => {
        el.addEventListener('click', () => this.closeModal());
      });

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (!this.modal.hidden) {
          if (e.key === 'Escape') this.closeModal();
          if (e.key === 'ArrowLeft') this.prevClip();
          if (e.key === 'ArrowRight') this.nextClip();
        }
      });

      // Scroll observer for dots
      this.slider.addEventListener('scroll', () => this.updateActiveDot());
    }

    scrollTo(direction) {
      const clipWidth = this.slider.querySelector('.popclips-clip').offsetWidth;
      const gap = parseInt(getComputedStyle(this.slider).gap) || 16;
      const scrollAmount = clipWidth + gap;

      this.slider.scrollBy({
        left: direction === 'next' ? scrollAmount : -scrollAmount,
        behavior: 'smooth'
      });
    }

    scrollToIndex(index) {
      const clips = this.slider.querySelectorAll('.popclips-clip');
      if (clips[index]) {
        clips[index].scrollIntoView({ behavior: 'smooth', inline: 'start' });
      }
    }

    updateActiveDot() {
      const clips = this.slider.querySelectorAll('.popclips-clip');
      const scrollLeft = this.slider.scrollLeft;
      const clipWidth = clips[0]?.offsetWidth || 0;

      if (clipWidth === 0) return;

      const activeIndex = Math.round(scrollLeft / (clipWidth + 16));
      const dots = this.dotsContainer?.querySelectorAll('.popclips-carousel__dot');

      dots?.forEach((dot, index) => {
        dot.classList.toggle('active', index === activeIndex);
      });
    }

    openClip(index) {
      this.currentClipIndex = index;
      const clip = this.clips[index];

      // Track view
      this.trackEvent('clip_view', { clip_id: clip.id });

      // Render player
      this.renderPlayer(clip);
      this.renderProducts(clip);

      // Show modal
      this.modal.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    closeModal() {
      this.modal.hidden = true;
      document.body.style.overflow = '';

      // Stop video
      if (this.videoElement) {
        this.videoElement.pause();
        this.videoElement = null;
      }

      this.player.innerHTML = '';
      this.productsPanel.innerHTML = '';
    }

    prevClip() {
      if (this.currentClipIndex > 0) {
        this.openClip(this.currentClipIndex - 1);
      }
    }

    nextClip() {
      if (this.currentClipIndex < this.clips.length - 1) {
        this.openClip(this.currentClipIndex + 1);
      }
    }

    renderPlayer(clip) {
      this.player.innerHTML = `
        <video
          class="popclips-player__video"
          src="${clip.videoUrl}"
          poster="${clip.thumbnailUrl}"
          playsinline
          autoplay
        ></video>
        <div class="popclips-player__hotspots" data-hotspots></div>
        <div class="popclips-player__controls">
          <div class="popclips-player__progress" data-progress>
            <div class="popclips-player__progress-bar" data-progress-bar style="width: 0%"></div>
          </div>
          <div class="popclips-player__time">
            <span data-current-time>0:00</span> / <span data-duration>${formatDuration(clip.duration)}</span>
          </div>
          <div class="popclips-player__actions">
            <button class="popclips-player__action" data-action="like">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
              <span>${formatNumber(clip.likes)}</span>
            </button>
            <button class="popclips-player__action" data-action="share">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
              </svg>
              <span>Share</span>
            </button>
          </div>
        </div>
      `;

      this.videoElement = this.player.querySelector('video');
      const progressBar = this.player.querySelector('[data-progress-bar]');
      const currentTimeEl = this.player.querySelector('[data-current-time]');
      const progressEl = this.player.querySelector('[data-progress]');
      const hotspotsContainer = this.player.querySelector('[data-hotspots]');

      // Video events
      this.videoElement.addEventListener('timeupdate', () => {
        const progress = (this.videoElement.currentTime / this.videoElement.duration) * 100;
        progressBar.style.width = `${progress}%`;
        currentTimeEl.textContent = formatDuration(this.videoElement.currentTime);
        this.currentVideoTime = this.videoElement.currentTime;
        this.updateHotspots(clip, hotspotsContainer);
      });

      this.videoElement.addEventListener('ended', () => {
        this.trackEvent('clip_complete', { clip_id: clip.id });
        if (this.currentClipIndex < this.clips.length - 1) {
          setTimeout(() => this.nextClip(), 500);
        }
      });

      this.videoElement.addEventListener('click', () => {
        if (this.videoElement.paused) {
          this.videoElement.play();
        } else {
          this.videoElement.pause();
        }
      });

      // Progress click
      progressEl.addEventListener('click', (e) => {
        const rect = progressEl.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.videoElement.currentTime = percent * this.videoElement.duration;
      });

      // Actions
      this.player.querySelector('[data-action="like"]').addEventListener('click', () => {
        this.trackEvent('like', { clip_id: clip.id });
      });

      this.player.querySelector('[data-action="share"]').addEventListener('click', () => {
        this.shareClip(clip);
      });

      // Initial hotspots render
      this.updateHotspots(clip, hotspotsContainer);
    }

    updateHotspots(clip, container) {
      const activeHotspots = clip.hotspots.filter(h =>
        this.currentVideoTime >= h.startTime && this.currentVideoTime <= h.endTime
      );

      container.innerHTML = activeHotspots.map(hotspot => `
        <div
          class="popclips-hotspot popclips-hotspot--${hotspot.animation}"
          style="left: ${hotspot.x}%; top: ${hotspot.y}%"
          data-hotspot-id="${hotspot.id}"
          data-product-id="${hotspot.productId}"
        >
          <div class="popclips-hotspot__dot">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="9" cy="21" r="1"></circle>
              <circle cx="20" cy="21" r="1"></circle>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
          </div>
          <div class="popclips-hotspot__popup">
            <img class="popclips-hotspot__image" src="${hotspot.image}" alt="${hotspot.title}">
            <h4 class="popclips-hotspot__title">${hotspot.title}</h4>
            <p class="popclips-hotspot__price">${hotspot.currency} ${hotspot.price}</p>
            <button class="popclips-hotspot__btn" data-add-to-cart="${hotspot.productId}">Add to Cart</button>
          </div>
        </div>
      `).join('');

      // Bind hotspot events
      container.querySelectorAll('.popclips-hotspot').forEach(el => {
        el.addEventListener('click', (e) => {
          if (e.target.closest('[data-add-to-cart]')) {
            this.addToCart(el.dataset.productId, el.dataset.hotspotId);
          } else {
            this.trackEvent('hotspot_click', {
              clip_id: clip.id,
              hotspot_id: el.dataset.hotspotId
            });
          }
        });
      });
    }

    renderProducts(clip) {
      if (clip.hotspots.length === 0) {
        this.productsPanel.innerHTML = '<p class="popclips-products__empty">No products tagged</p>';
        return;
      }

      const uniqueProducts = [];
      const seen = new Set();
      clip.hotspots.forEach(h => {
        if (!seen.has(h.productId)) {
          seen.add(h.productId);
          uniqueProducts.push(h);
        }
      });

      this.productsPanel.innerHTML = `
        <h3 class="popclips-products__title">Products in this video</h3>
        <div class="popclips-products__list">
          ${uniqueProducts.map(product => `
            <div class="popclips-product-card" data-product-id="${product.productId}" data-hotspot-id="${product.id}">
              <img class="popclips-product-card__image" src="${product.image}" alt="${product.title}">
              <div class="popclips-product-card__body">
                <h4 class="popclips-product-card__title">${product.title}</h4>
                <p class="popclips-product-card__price">${product.currency} ${product.price}</p>
                <button class="popclips-product-card__btn" data-add-to-cart="${product.productId}">Add to Cart</button>
              </div>
            </div>
          `).join('')}
        </div>
      `;

      // Bind events
      this.productsPanel.querySelectorAll('[data-add-to-cart]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const card = e.target.closest('.popclips-product-card');
          this.addToCart(card.dataset.productId, card.dataset.hotspotId);
        });
      });
    }

    async addToCart(productId, hotspotId) {
      try {
        // Track add to cart
        this.trackEvent('add_to_cart', {
          clip_id: this.clips[this.currentClipIndex].id,
          hotspot_id: hotspotId
        });

        // Get first available variant
        const response = await fetch(`/products/${productId}.json`);
        const { product } = await response.json();
        const variantId = product.variants[0].id;

        // Add to cart with tracking attributes
        await fetch('/cart/add.js', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: variantId,
            quantity: 1,
            properties: {
              '_popclips_session': getSessionId(),
              '_popclips_clip_id': this.clips[this.currentClipIndex].id,
              '_popclips_hotspot_id': hotspotId
            }
          })
        });

        // Show success feedback
        this.showNotification('Added to cart!');

        // Update cart count
        this.updateCartCount();

      } catch (error) {
        console.error('Popclips: Failed to add to cart', error);
        this.showNotification('Failed to add to cart', 'error');
      }
    }

    async updateCartCount() {
      try {
        const response = await fetch('/cart.js');
        const cart = await response.json();

        // Update cart count in header (common selectors)
        const countElements = document.querySelectorAll(
          '.cart-count, .cart-item-count, [data-cart-count], .header__cart-count'
        );
        countElements.forEach(el => {
          el.textContent = cart.item_count;
        });
      } catch (e) {
        // Ignore
      }
    }

    showNotification(message, type = 'success') {
      const notification = document.createElement('div');
      notification.className = `popclips-notification popclips-notification--${type}`;
      notification.textContent = message;
      notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 24px;
        background: ${type === 'success' ? '#10B981' : '#EF4444'};
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10000;
        animation: popclips-notification 2.5s ease-in-out forwards;
      `;

      document.body.appendChild(notification);
      setTimeout(() => notification.remove(), 2500);
    }

    shareClip(clip) {
      this.trackEvent('share', { clip_id: clip.id });

      const shareUrl = `${window.location.origin}?popclips_clip=${clip.id}`;

      if (navigator.share) {
        navigator.share({
          title: clip.title,
          url: shareUrl
        });
      } else {
        navigator.clipboard.writeText(shareUrl);
        this.showNotification('Link copied to clipboard!');
      }
    }

    async trackEvent(eventType, data) {
      try {
        await fetch(`${API_BASE}/track`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            event: eventType,
            ...data,
            session_id: getSessionId(),
            visitor_id: getVisitorId(),
            device_type: getDeviceType(),
            browser: getBrowser(),
            shop: this.shopDomain
          })
        });
      } catch (e) {
        // Silent fail for analytics
      }
    }

    showError() {
      this.slider.innerHTML = `
        <div class="popclips-carousel__error">
          Failed to load videos. Please refresh the page.
        </div>
      `;
    }
  }

  // Initialize all carousels
  function init() {
    document.querySelectorAll('[data-popclips-carousel]').forEach(el => {
      new PopclipsCarousel(el);
    });
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Add notification animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes popclips-notification {
      0% { opacity: 0; transform: translateX(-50%) translateY(20px); }
      15% { opacity: 1; transform: translateX(-50%) translateY(0); }
      85% { opacity: 1; transform: translateX(-50%) translateY(0); }
      100% { opacity: 0; transform: translateX(-50%) translateY(-20px); }
    }
  `;
  document.head.appendChild(style);

})();
