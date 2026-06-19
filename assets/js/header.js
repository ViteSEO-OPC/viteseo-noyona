(function () {
  const STICKY_AT = 15;

  const $ = (s, scope = document) => scope.querySelector(s);
  const $$ = (s, scope = document) => Array.from(scope.querySelectorAll(s));

  function normalizeHeaderLogos() {
    const themeUri = (window.noyonaHeader && window.noyonaHeader.themeUri)
      ? String(window.noyonaHeader.themeUri).replace(/\/$/, '')
      : '/wp-content/themes/viteseo-noyona';

    const expectedDesktopLogo = themeUri + '/assets/images/noyona-logo.webp';
    const expectedMobileLogo = themeUri + '/assets/images/noyona-mobile-logo.webp';

    const applyLogo = (selector, expectedSrc) => {
      const img = $(selector);
      if (!img) return;

      const currentSrc = (img.getAttribute('src') || '').trim();
      const usesUploadImage = /\/wp-content\/uploads\//i.test(currentSrc);
      if (!currentSrc || usesUploadImage || currentSrc !== expectedSrc) {
        img.setAttribute('src', expectedSrc);
      }

      // Prevent stale DB/media srcset from overriding the forced logo.
      img.removeAttribute('srcset');
    };

    applyLogo('.site-logo-img--desktop', expectedDesktopLogo);
    applyLogo('.site-logo-img--mobile', expectedMobileLogo);
  }

  function updateHeaderOffsets() {
    const root = document.documentElement;
    const headerPart = $('header.wp-block-template-part');
    const adminBar = $('#wpadminbar');

    const headerHeight = headerPart ? Math.ceil(headerPart.getBoundingClientRect().height) : 72;
    const adminBarHeight = adminBar ? Math.ceil(adminBar.getBoundingClientRect().height) : 0;
    const totalOffset = headerHeight + adminBarHeight;

    root.style.setProperty('--noyona-header-height', headerHeight + 'px');
    root.style.setProperty('--noyona-adminbar-height', adminBarHeight + 'px');
    root.style.setProperty('--noyona-header-total-offset', totalOffset + 'px');
  }

  function toggleScrollState() {
    const y = window.scrollY || 0;
    const headerPart = $('header.wp-block-template-part');

    if (y > STICKY_AT) {
      document.body.classList.add('has-scrolled-header');
      if (headerPart) headerPart.classList.add('is-sticky');
    } else {
      document.body.classList.remove('has-scrolled-header');
      if (headerPart) headerPart.classList.remove('is-sticky');
    }

    document.body.classList.remove('is-scrolling-down');

    updateHeaderOffsets();
    // Logo swap on scroll is intentionally disabled for now.
    // updateHeaderLogoVariant(y > STICKY_AT);
  }

  /*
  function updateHeaderLogoVariant(useScrolledLogo) {
    $$('.site-logo-img[data-logo-default][data-logo-scrolled]').forEach((img) => {
      const nextSrc = useScrolledLogo ? img.dataset.logoScrolled : img.dataset.logoDefault;
      if (!nextSrc) return;
      img.classList.toggle('is-scrolled-logo', useScrolledLogo);
      if (img.getAttribute('src') !== nextSrc) {
        img.setAttribute('src', nextSrc);
      }
    });
  }
  */

  function initWishlist() {
    // Wishlist drawer is temporarily disabled; the header heart now links to /my-account/wishlist/.
    /*
    const panel = $('#wishlist-panel');
    if (!panel) return;

    const overlay = panel.querySelector('.wishlist-panel__overlay');
    const closeButtons = $$('.wishlist-panel [data-wishlist-close]');

    const closePanel = () => panel.classList.remove('is-open');
    const openPanel = () => panel.classList.add('is-open');

    document.addEventListener('click', (e) => {
      if (e.target.closest('.header-wishlist-toggle')) openPanel();
    });

    if (overlay) overlay.addEventListener('click', closePanel);
    closeButtons.forEach((btn) => btn.addEventListener('click', closePanel));

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closePanel();
    });
    */
  }

  function initMiniCartCloseAction() {
    document.addEventListener('click', (e) => {
      const continueBtn = e.target.closest('.noyona-mini-cart-continue');
      if (!continueBtn) return;
      const closeBtn = document.querySelector('.wc-block-components-drawer__close');
      if (closeBtn) {
        closeBtn.click();
      }
    });
  }

  /**
   * Shared mini-cart drawer open (ADR-004). Single implementation for PDP,
   * #open-cart, ?open-cart=1, and future product-card adds.
   */
  function getMiniCartButton() {
    return (
      document.querySelector('.header-mini-cart .wc-block-mini-cart__button') ||
      document.querySelector('.wc-block-mini-cart__button')
    );
  }

  function openMiniCartDrawer(options) {
    const settings = options || {};
    const delay = Math.max(0, parseInt(settings.delay, 10) || 0);
    const retries = Math.max(0, parseInt(settings.retries, 10) || 0);
    const retryInterval = Math.max(0, parseInt(settings.retryInterval, 10) || 250);

    const clickMiniCartButton = () => {
      const btn = getMiniCartButton();
      if (!btn) return false;
      btn.click();
      return true;
    };

    const tryOpen = (attemptsLeft) => {
      if (clickMiniCartButton()) return true;
      if (attemptsLeft <= 0) return false;
      setTimeout(() => {
        tryOpen(attemptsLeft - 1);
      }, retryInterval);
      return false;
    };

    const start = () => tryOpen(retries);

    if (delay > 0) {
      setTimeout(start, delay);
      return false;
    }

    return start();
  }

  window.noyonaCartFx = window.noyonaCartFx || {};
  window.noyonaCartFx.openDrawer = function (options) {
    return openMiniCartDrawer(options);
  };

  function getWcAjaxAddToCartUrl() {
    if (
      typeof window.wc_add_to_cart_params !== 'undefined' &&
      window.wc_add_to_cart_params &&
      window.wc_add_to_cart_params.wc_ajax_url
    ) {
      return String(window.wc_add_to_cart_params.wc_ajax_url).replace('%%endpoint%%', 'add_to_cart');
    }
    return '/?wc-ajax=add_to_cart';
  }

  function noyonaAjaxAddToCart(options) {
    const settings = options || {};
    const productId = parseInt(settings.productId, 10) || 0;
    const quantity = Math.max(1, parseInt(settings.quantity, 10) || 1);
    const sourceButton = settings.sourceButton || null;
    const openDrawer = settings.openDrawer !== false;

    if (productId < 1) {
      return Promise.resolve({ ok: false, error: 'This product cannot be added to cart right now.' });
    }

    const payload = new URLSearchParams();
    payload.set('product_id', String(productId));
    payload.set('quantity', String(quantity));

    return fetch(getWcAjaxAddToCartUrl(), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: payload.toString(),
    })
      .then((response) => response.text())
      .then((raw) => {
        let data = null;
        if (raw) {
          try {
            data = JSON.parse(raw);
          } catch (e) {
            data = null;
          }
        }

        if (data && data.error) {
          const message =
            String((data && data.message) || (data && data.data && data.data.message) || '').trim() ||
            'This product cannot be added to cart right now.';
          return { ok: false, error: message };
        }

        if (window.jQuery) {
          window.jQuery(document.body).trigger('added_to_cart', [
            data && data.fragments ? data.fragments : null,
            data && data.cart_hash ? data.cart_hash : '',
            sourceButton,
          ]);
          window.jQuery(document.body).trigger('wc_fragment_refresh');
        }

        document.body.dispatchEvent(
          new CustomEvent('wc-blocks_added_to_cart', {
            bubbles: true,
            detail: { source: 'product-card', preserveCartData: false },
          })
        );
        document.body.dispatchEvent(
          new CustomEvent('noyona_cart_added', {
            bubbles: true,
            detail: { source: 'product-card' },
          })
        );

        if (openDrawer && window.noyonaCartFx && typeof window.noyonaCartFx.openDrawer === 'function') {
          window.noyonaCartFx.openDrawer({ delay: 120 });
        }

        return { ok: true, data: data || {} };
      })
      .catch(() => ({
        ok: false,
        error: 'This product cannot be added to cart right now.',
      }));
  }

  function setProductCardCartBusy(button, busy) {
    if (!button) return;
    if (busy) {
      button.classList.add('is-loading');
      button.setAttribute('aria-busy', 'true');
      button.setAttribute('aria-disabled', 'true');
    } else {
      button.classList.remove('is-loading');
      button.removeAttribute('aria-busy');
      if (button.getAttribute('data-cart-action') !== 'disabled') {
        button.removeAttribute('aria-disabled');
      }
    }
  }

  function bindProductCardCartButton(button, card) {
    if (!button || button.getAttribute('data-noyona-card-cart-bound') === '1') {
      return;
    }

    const action = button.getAttribute('data-cart-action') || '';

    if (action === 'disabled') {
      return;
    }

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (action === 'navigate') {
        const cardLink = card && card.querySelector('.wp-block-post-title a, .wc-block-components-product-image a');
        const url = button.getAttribute('data-product-url') || (cardLink && cardLink.href) || '';
        if (url) {
          window.location.assign(url);
        }
        return;
      }

      if (action === 'buysheet') {
        if (button.disabled || button.getAttribute('aria-disabled') === 'true' || button.classList.contains('is-loading')) {
          return;
        }

        const productId = parseInt(button.getAttribute('data-product-id'), 10) || 0;
        if (productId < 1) {
          return;
        }

        if (!window.noyonaBuySheet || typeof window.noyonaBuySheet.open !== 'function') {
          const fallbackUrl = button.getAttribute('data-product-url') || '';
          if (fallbackUrl) {
            window.location.assign(fallbackUrl);
          }
          return;
        }

        setProductCardCartBusy(button, true);
        window.noyonaBuySheet
          .open(productId, button)
          .catch((result) => {
            const message =
              (result && result.error) ||
              (window.noyonaPdp &&
                window.noyonaPdp.i18n &&
                window.noyonaPdp.i18n.buySheetLoadError) ||
              'Unable to load product options. Please try again.';
            if (window.noyonaCartFx && typeof window.noyonaCartFx.toast === 'function') {
              window.noyonaCartFx.toast(message, 'error');
            }
          })
          .finally(() => {
            setProductCardCartBusy(button, false);
          });
        return;
      }

      if (action !== 'ajax') {
        return;
      }

      if (button.disabled || button.getAttribute('aria-disabled') === 'true' || button.classList.contains('is-loading')) {
        return;
      }

      const productId = parseInt(button.getAttribute('data-product-id'), 10) || 0;
      if (productId < 1) {
        return;
      }

      setProductCardCartBusy(button, true);

      noyonaAjaxAddToCart({
        productId,
        quantity: 1,
        sourceButton: button,
        openDrawer: false,
      })
        .then((result) => {
          if (!result.ok && result.error && window.noyonaCartFx && typeof window.noyonaCartFx.toast === 'function') {
            window.noyonaCartFx.toast(result.error, 'error');
          }
        })
        .finally(() => {
          setProductCardCartBusy(button, false);
        });
    });

    button.setAttribute('data-noyona-card-cart-bound', '1');
  }

  function initProductCardCart() {
    document
      .querySelectorAll('.noyona-shop-products .wc-block-product .noyona-product-card-cart[data-noyona-card-cart="1"]')
      .forEach((button) => {
        const card = button.closest('.wc-block-product');
        if (!card) return;
        bindProductCardCartButton(button, card);
      });
  }

  function initMiniCartDynamicUi() {
    const CART_STORE_KEYS = ['wc/store/cart', 'wc/store/cart-data', 'wc/store'];

    const resolveCheckoutUrl = () => {
      const configuredUrl = (window.noyonaHeader && window.noyonaHeader.cartUrl)
        ? String(window.noyonaHeader.cartUrl)
        : '';
      if (configuredUrl) return configuredUrl;
      return '/cart/';
    };

    const resolveAccountUrl = () => {
      const configuredUrl = (window.noyonaHeader && window.noyonaHeader.accountUrl)
        ? String(window.noyonaHeader.accountUrl)
        : '';
      if (configuredUrl) return configuredUrl;
      return '/my-account/';
    };

    const resolveLoginUrl = () => {
      const configuredUrl = (window.noyonaHeader && window.noyonaHeader.loginUrl)
        ? String(window.noyonaHeader.loginUrl)
        : '';
      if (configuredUrl) return configuredUrl;
      return '/wp-login.php';
    };

    const resolveRegisterUrl = () => {
      const accountUrl = resolveAccountUrl();
      try {
        const registerUrl = new URL(accountUrl, window.location.origin);
        registerUrl.searchParams.set('action', 'register');
        return registerUrl.toString();
      } catch (e) {
        if (String(accountUrl).includes('?')) {
          return String(accountUrl) + '&action=register';
        }
        return String(accountUrl).replace(/\/?$/, '/') + '?action=register';
      }
    };

    const formatPeso = (value) => {
      const amount = Number(value) || 0;
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(amount);
    };

    const parseCurrencyText = (text) => {
      if (!text) return 0;
      const normalized = String(text).replace(/[^0-9.,-]/g, '').replace(/,/g, '');
      const amount = parseFloat(normalized);
      return Number.isFinite(amount) ? amount : 0;
    };

    const parseMinorAmount = (value) => {
      const n = parseInt(value, 10);
      return Number.isFinite(n) ? n : 0;
    };

    const stripToText = (value) => {
      if (value === null || typeof value === 'undefined') return '';
      const probe = document.createElement('div');
      probe.innerHTML = String(value);
      return String(probe.textContent || probe.innerText || '').trim();
    };

    const normalizeAttrKey = (key) =>
      String(key || '')
        .toLowerCase()
        .replace(/^attribute_/, '')
        .replace(/^pa_/, '')
        .replace(/[_-]+/g, ' ')
        .trim();

    const prettifyAttrLabel = (rawKey) => {
      const normalized = normalizeAttrKey(rawKey);
      if (!normalized) return '';

      if (/(^|\s)(color|colour|shade|swatch|tone|tint)(\s|$)/.test(normalized)) {
        return 'Shade';
      }
      if (/(^|\s)pack size(\s|$)/.test(normalized)) {
        return 'Pack Size';
      }
      if (/(^|\s)size(\s|$)/.test(normalized)) {
        return 'Size';
      }

      return normalized
        .split(' ')
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    };

    const addMetaPair = (pairs, key, value) => {
      const label = prettifyAttrLabel(key);
      const finalValue = stripToText(value);
      if (!label || !finalValue) return;

      const normalizedLabel = label.toLowerCase();
      const exists = pairs.some(
        (item) => item.label.toLowerCase() === normalizedLabel && item.value === finalValue
      );
      if (!exists) {
        pairs.push({ label, value: finalValue });
      }
    };

    const extractVariationMetaFromCartItem = (item) => {
      const pairs = [];
      if (!item || typeof item !== 'object') return pairs;

      if (Array.isArray(item.item_data)) {
        item.item_data.forEach((detail) => {
          const key = detail && (detail.key || detail.name || detail.label)
            ? String(detail.key || detail.name || detail.label)
            : '';
          const val = detail && (detail.value || detail.display_value || detail.display || detail.raw)
            ? String(detail.value || detail.display_value || detail.display || detail.raw)
            : '';
          addMetaPair(pairs, key, val);
        });
      }

      if (Array.isArray(item.variation)) {
        item.variation.forEach((detail) => {
          const key = detail && (detail.attribute || detail.name || detail.key || detail.label)
            ? String(detail.attribute || detail.name || detail.key || detail.label)
            : '';
          const val = detail && (detail.value || detail.display_value || detail.display)
            ? String(detail.value || detail.display_value || detail.display)
            : '';
          addMetaPair(pairs, key, val);
        });
      } else if (item.variation && typeof item.variation === 'object') {
        Object.entries(item.variation).forEach(([key, val]) => {
          addMetaPair(pairs, key, val);
        });
      }

      return pairs;
    };

    const extractVariationMetaFromDomDetails = (wrap) => {
      const pairs = [];
      if (!wrap) return pairs;

      const detailRows = wrap.querySelectorAll(
        '.wc-block-components-product-details__item, .wc-block-components-product-details li'
      );
      detailRows.forEach((row) => {
        const nameEl = row.querySelector('.wc-block-components-product-details__name, dt, .variation-label');
        const valueEl = row.querySelector('.wc-block-components-product-details__value, dd, .variation-value');
        const key = stripToText(nameEl ? nameEl.textContent : '');
        const val = stripToText(valueEl ? valueEl.textContent : '');
        addMetaPair(pairs, key, val);
      });

      if (!pairs.length) {
        const names = wrap.querySelectorAll('.wc-block-components-product-details__name');
        names.forEach((nameEl) => {
          const key = stripToText(nameEl.textContent);
          let valueEl = null;
          if (nameEl.parentElement) {
            valueEl = nameEl.parentElement.querySelector('.wc-block-components-product-details__value');
          }
          if (!valueEl) {
            const next = nameEl.nextElementSibling;
            if (next) valueEl = next;
          }
          const val = stripToText(valueEl ? valueEl.textContent : '');
          addMetaPair(pairs, key, val);
        });
      }

      return pairs;
    };

    const syncWooBlocksCartStore = (cart) => {
      if (!cart || !window.wp || !window.wp.data) return;

      CART_STORE_KEYS.forEach((storeKey) => {
        let dispatch = null;
        try {
          dispatch = window.wp.data.dispatch(storeKey);
        } catch (e) {
          dispatch = null;
        }
        if (!dispatch) return;

        if (typeof dispatch.receiveCart === 'function') {
          try {
            dispatch.receiveCart(cart);
          } catch (e) {
            // Keep trying with alternative APIs below.
          }
        }
        if (typeof dispatch.receiveCartData === 'function') {
          try {
            dispatch.receiveCartData(cart);
          } catch (e) {
            // Keep trying with alternative APIs below.
          }
        }
        if (typeof dispatch.receiveCartContents === 'function') {
          try {
            dispatch.receiveCartContents(cart);
          } catch (e) {
            // Keep trying with alternative APIs below.
          }
        }
        if (typeof dispatch.invalidateResolutionForStore === 'function') {
          try {
            dispatch.invalidateResolutionForStore();
          } catch (e) {
            // noop
          }
        }
        if (typeof dispatch.invalidateResolution === 'function') {
          try {
            dispatch.invalidateResolution('getCartData', []);
          } catch (e) {
            // noop
          }
          try {
            dispatch.invalidateResolution('getCart', []);
          } catch (e) {
            // noop
          }
          try {
            dispatch.invalidateResolution('getCartTotals', []);
          } catch (e) {
            // noop
          }
        }
      });
    };

    const getMiniCartRoot = () => document.querySelector('.wc-block-mini-cart__drawer .wp-block-woocommerce-mini-cart-contents');
    let latestMiniCartStockItems = [];

    const getMiniCartItemDisplayPrice = (item, row) => {
      // Prefer rendered Woo price text (already formatted with currency/settings).
      if (row) {
        const renderedValue = row.querySelector(
          '.wc-block-components-product-price__value.is-discounted, .wc-block-components-product-price__value'
        );
        const renderedText = stripToText(renderedValue ? renderedValue.textContent : '');
        if (renderedText) return renderedText;
      }

      // Fallback to Store API price (minor units -> peso formatting).
      if (item && item.prices) {
        const candidateMinor =
          parseMinorAmount(item.prices.sale_price) ||
          parseMinorAmount(item.prices.price);
        if (Number.isFinite(candidateMinor) && candidateMinor >= 0) {
          return formatPeso(candidateMinor / 100);
        }
      }

      return '';
    };

    const isMiniCartItemInStock = (item) => {
      if (!item || typeof item !== 'object') return true;
      const extensions = item.extensions && item.extensions.noyona
        ? item.extensions.noyona
        : null;
      if (extensions && Object.prototype.hasOwnProperty.call(extensions, 'in_stock')) {
        return !!extensions.in_stock;
      }
      return true;
    };

    const syncMiniCartStockBadges = (root, cart, stockItems = []) => {
      if (!root) return;
      const items = cart && Array.isArray(cart.items) ? cart.items : [];
      const rows = root.querySelectorAll('.wc-block-cart-items__row, .wc-block-mini-cart-items__row');

      rows.forEach((row, index) => {
        const stockItem = stockItems[index] || null;
        const item = items[index] || null;
        const inStock = stockItem && Object.prototype.hasOwnProperty.call(stockItem, 'in_stock')
          ? !!stockItem.in_stock
          : isMiniCartItemInStock(item);
        row.classList.toggle('noyona-mini-cart-row--out-of-stock', !inStock);
        row.dataset.noyonaInStock = inStock ? '1' : '0';

        const imageWrap = row.querySelector('.wc-block-cart-item__image, .wc-block-components-product-image');
        if (!imageWrap) return;

        let badge = imageWrap.querySelector('.noyona-sold-out-badge');
        if (inStock) {
          if (badge) badge.remove();
          return;
        }

        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'noyona-sold-out-badge';
          imageWrap.appendChild(badge);
        }
        badge.textContent = 'Sold Out';
      });
    };

    const hasMiniCartOutOfStockItem = (root) => {
      if (!root) return false;
      return !!root.querySelector('[data-noyona-in-stock="0"], .noyona-mini-cart-row--out-of-stock')
        || latestMiniCartStockItems.some((item) => item && item.in_stock === false);
    };

    const getMiniCartOutOfStockNames = () => {
      return latestMiniCartStockItems
        .filter((item) => item && item.in_stock === false)
        .map((item) => String(item && item.name ? item.name : '').trim())
        .filter(Boolean);
    };

    const buildMiniCartStockMessages = (names) => {
      const list = Array.isArray(names) ? names.filter(Boolean) : [];
      if (!list.length) {
        return ['Please remove sold out items before continuing to checkout.'];
      }
      return list.map((name) => `"${name}" is sold out — remove it to continue to checkout.`);
    };

    const showMiniCartErrorNotice = (root, messages) => {
      if (!root) return;

      const noticeAnchor = root.querySelector('.noyona-mini-cart-message')
        || root.querySelector('.wp-block-woocommerce-mini-cart-items-block');

      let notice = root.querySelector('.noyona-mini-cart-stock-notice');
      if (!notice) {
        notice = document.createElement('ul');
        notice.className = 'woocommerce-error noyona-mini-cart-stock-notice';
        notice.setAttribute('role', 'alert');
      }

      if (noticeAnchor && noticeAnchor.parentNode && notice.nextElementSibling !== noticeAnchor) {
        noticeAnchor.parentNode.insertBefore(notice, noticeAnchor);
      } else if (!notice.parentNode) {
        const fallback = root.firstElementChild;
        if (fallback && fallback.parentNode) {
          fallback.parentNode.insertBefore(notice, fallback);
        } else {
          root.prepend(notice);
        }
      }

      const finalMessages = Array.isArray(messages) && messages.length
        ? [messages[0]]
        : [buildMiniCartStockMessages(getMiniCartOutOfStockNames())[0]];
      notice.innerHTML = '';
      finalMessages.forEach((message) => {
        const li = document.createElement('li');
        li.textContent = message;
        notice.appendChild(li);
      });

      notice.hidden = false;
      notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const syncProductTitlePriceRows = (root, cart) => {
      if (!root) return;
      const items = cart && Array.isArray(cart.items) ? cart.items : [];

      const rows = root.querySelectorAll('.wc-block-cart-items__row, .wc-block-mini-cart-items__row');
      rows.forEach((row, index) => {
        const wrap = row.querySelector('.wc-block-cart-item__wrap');
        if (!wrap) return;

        const allNameEls = Array.from(
          wrap.querySelectorAll('a.wc-block-components-product-name, .wc-block-components-product-name')
        );
        const existingRow = wrap.querySelector('.noyona-mini-cart-title-price-row');

        let nameEl = null;
        if (existingRow) {
          nameEl = existingRow.querySelector('a.wc-block-components-product-name, .wc-block-components-product-name');
        }
        if (!nameEl) {
          nameEl = allNameEls.find((el) => el.tagName === 'A') || allNameEls[0] || null;
        }
        if (!nameEl) return;

        const item = items[index] || null;
        const priceText = getMiniCartItemDisplayPrice(item, row);

        if (existingRow) {
          if (!existingRow.contains(nameEl)) existingRow.prepend(nameEl);
          let customPrice = existingRow.querySelector('.noyona-mini-cart-price');
          if (!customPrice) {
            customPrice = document.createElement('span');
            customPrice.className = 'noyona-mini-cart-price';
            existingRow.appendChild(customPrice);
          }
          customPrice.textContent = priceText || '—';

          // Prevent duplicate title/price nodes from stacking on repeated refreshes.
          allNameEls.forEach((candidate) => {
            if (candidate !== nameEl && !existingRow.contains(candidate)) {
              candidate.remove();
            }
          });

          return;
        }

        const titlePriceRow = document.createElement('div');
        titlePriceRow.className = 'noyona-mini-cart-title-price-row';
        nameEl.parentNode.insertBefore(titlePriceRow, nameEl);
        titlePriceRow.appendChild(nameEl);
        const customPrice = document.createElement('span');
        customPrice.className = 'noyona-mini-cart-price';
        customPrice.textContent = priceText || '—';
        titlePriceRow.appendChild(customPrice);

        // Remove any duplicate nodes left by Store API rerenders.
        allNameEls.forEach((candidate) => {
          if (candidate !== nameEl && !titlePriceRow.contains(candidate)) {
            candidate.remove();
          }
        });
      });
    };

    const syncSelectedVariationRows = (root, cart) => {
      if (!root) return;
      const items = cart && Array.isArray(cart.items) ? cart.items : [];
      const rows = root.querySelectorAll('.wc-block-cart-items__row, .wc-block-mini-cart-items__row');

      rows.forEach((row, index) => {
        const wrap = row.querySelector('.wc-block-cart-item__wrap');
        if (!wrap) return;

        const existing = wrap.querySelector('.noyona-mini-cart-variation-meta');
        const fromCart = extractVariationMetaFromCartItem(items[index] || null);
        const fromDom = extractVariationMetaFromDomDetails(wrap);
        const metaPairs = fromCart.length ? fromCart : fromDom;

        if (!metaPairs.length) {
          if (existing) {
            existing.remove();
          }
          return;
        }

        const html = metaPairs
          .map((pair) => `<div class="noyona-mini-cart-variation-meta__row"><span class="noyona-mini-cart-variation-meta__label">${pair.label}:</span> <span class="noyona-mini-cart-variation-meta__value">${pair.value}</span></div>`)
          .join('');

        if (existing) {
          existing.innerHTML = html;
          return;
        }

        const rowEl = document.createElement('div');
        rowEl.className = 'noyona-mini-cart-variation-meta';
        rowEl.innerHTML = html;

        const anchor = wrap.querySelector('.noyona-mini-cart-title-price-row');
        if (anchor && anchor.parentNode) {
          anchor.parentNode.insertBefore(rowEl, anchor.nextSibling);
        } else {
          wrap.appendChild(rowEl);
        }
      });
    };

    const readSubtotalFallback = (root) => {
      if (!root) return 0;
      const subtotalNode = root.querySelector('.wc-block-mini-cart__footer-subtotal');
      if (!subtotalNode) return 0;
      return parseCurrencyText(subtotalNode.textContent);
    };

    // WooCommerce Blocks renders the checkout button in two possible patterns:
    //   A) <div class="wp-block-woocommerce-mini-cart-checkout-button-block">
    //        <a class="wc-block-components-button" href="…">…</a>
    //      </div>
    //   B) <a class="wp-block-woocommerce-mini-cart-checkout-button-block
    //                 wc-block-components-button" href="…">…</a>
    //
    // We also add a fallback class so we can always find it.
    const findCheckoutBtn = (root) => {
      if (!root) return null;
      // Pattern A: descendant
      return root.querySelector('.wp-block-woocommerce-mini-cart-checkout-button-block .wc-block-components-button')
        // Pattern B: both classes on same element
        || root.querySelector('.wp-block-woocommerce-mini-cart-checkout-button-block.wc-block-components-button')
        // Pattern C: WC might also use this class for the footer checkout link
        || root.querySelector('.wc-block-mini-cart__footer-checkout')
        // Pattern D: our own marker from a previous syncCheckoutButton
        || root.querySelector('[data-noyona-checkout-btn]');
    };

    const syncCheckoutButton = (root) => {
      if (!root) return;

      const checkoutBtn = findCheckoutBtn(root);
      if (!checkoutBtn) return;

      // Mark it so we can always find it again even if React changes classes.
      checkoutBtn.setAttribute('data-noyona-checkout-btn', '1');

      // Label the button.
      checkoutBtn.innerHTML = '<span>Checkout</span><span class="noyona-mini-cart-checkout-arrow" aria-hidden="true">→</span>';

      const base = resolveCheckoutUrl();
      if (checkoutBtn.tagName === 'A') checkoutBtn.setAttribute('href', base);
    };

    // Visual-only: toggle .is-disabled on the checkout button based on
    // terms checkbox state.
    const syncTermsGate = (root) => {
      if (!root) return;

      const termsBox = root.querySelector('.noyona-mini-cart-terms-checkbox');
      const checkoutBtn = findCheckoutBtn(root);

      const applyState = () => {
        if (!checkoutBtn) return;
        const termsOk = termsBox ? !!termsBox.checked : true;
        checkoutBtn.classList.toggle('is-disabled', !termsOk);
        checkoutBtn.setAttribute('aria-disabled', !termsOk ? 'true' : 'false');
      };

      if (termsBox && !termsBox.dataset.noyonaBound) {
        termsBox.addEventListener('change', applyState);
        termsBox.dataset.noyonaBound = '1';
      }

      applyState();
    };

    const getMiniCartLoginModal = () => {
      return document.querySelector('[data-mini-cart-login-modal-global]');
    };

    const closeMiniCartLoginModal = () => {
      const modal = getMiniCartLoginModal();
      if (!modal) return;
      modal.hidden = true;
      document.documentElement.classList.remove('noyona-mini-cart-login-open');
    };

    const setMiniCartLoginModalCopy = (titleText, copyText) => {
      const modal = getMiniCartLoginModal();
      if (!modal) return;

      const title = modal.querySelector('.noyona-mini-cart-login-title');
      const copy = modal.querySelector('.noyona-mini-cart-login-copy');
      if (title && titleText) {
        title.textContent = String(titleText);
      }
      if (copy && copyText) {
        copy.textContent = String(copyText);
      }
    };

    const openMiniCartLoginModal = () => {
      const modal = getMiniCartLoginModal();
      if (!modal) return;
      setMiniCartLoginModalCopy(
        'Log In to continue checkout',
        'Please log in to your account before checking out.'
      );
      modal.hidden = false;
      document.documentElement.classList.add('noyona-mini-cart-login-open');
    };

    const bindMiniCartLoginModal = () => {
      const modal = getMiniCartLoginModal();
      if (!modal) return;

      const loginUrl = resolveLoginUrl();
      const cartUrl = resolveCheckoutUrl();
      const loginAction = modal.querySelector('[data-mini-cart-login-action]');
      const registerAction = modal.querySelector('[data-mini-cart-register-action]');
      const loginForm = modal.querySelector('form.noyona-mini-cart-login-form');
      const loginRedirect = modal.querySelector('[data-mini-cart-login-redirect]');
      const googleLoginUrl = loginAction
        ? (loginAction.getAttribute('href') || '')
        : '';

      if (loginForm) {
        loginForm.setAttribute('action', loginUrl);
      }
      if (loginRedirect) {
        loginRedirect.setAttribute('value', cartUrl);
      }
      if (loginAction) {
        // Keep dedicated OAuth URL for the Google button.
        loginAction.setAttribute('href', googleLoginUrl || loginUrl);
      }
      if (registerAction) {
        registerAction.setAttribute('href', resolveRegisterUrl());
      }

      if (modal.dataset.noyonaBound === '1') return;
      modal.dataset.noyonaBound = '1';

      const closeButtons = modal.querySelectorAll('[data-mini-cart-login-close]');
      closeButtons.forEach((button) => {
        button.addEventListener('click', () => closeMiniCartLoginModal());
      });
    };

    // ── Capture-phase checkout click handler ──
    // Bound to `document` ONCE.  Because it uses the capture phase it
    // fires before React's delegated handlers and before the browser
    // follows the <a> href.  It can never be destroyed by React
    // re-rendering the mini-cart button.

    const isCheckoutButton = (el) => {
      if (!el) return false;
      // Match via our own marker
      if (el.hasAttribute('data-noyona-checkout-btn')) return true;
      // Match via WC class (both classes on same element OR as descendant)
      if (el.classList.contains('wc-block-components-button') &&
          el.closest('.wp-block-woocommerce-mini-cart-checkout-button-block')) return true;
      if (el.classList.contains('wp-block-woocommerce-mini-cart-checkout-button-block') &&
          el.classList.contains('wc-block-components-button')) return true;
      // Match via WC footer checkout class
      if (el.classList.contains('wc-block-mini-cart__footer-checkout')) return true;
      return false;
    };

    const findClickedCheckoutBtn = (target) => {
      // Walk up from the click target to find the checkout button.
      let el = target;
      while (el && el !== document.body) {
        if (isCheckoutButton(el)) return el;
        el = el.parentElement;
      }
      return null;
    };

    let checkoutClickBound = false;
    let miniCartLoginEscapeBound = false;
    const bindCheckoutClick = () => {
      if (checkoutClickBound) return;
      checkoutClickBound = true;

      document.addEventListener('click', (event) => {
        // Only care about clicks inside the mini-cart drawer.
        const drawer = event.target.closest('.wc-block-mini-cart__drawer');
        if (!drawer) return;

        const btn = findClickedCheckoutBtn(event.target);
        if (!btn) return;

        const root = getMiniCartRoot();
        if (!root) return;

        bindMiniCartLoginModal();

        if (!document.body.classList.contains('logged-in')) {
          event.preventDefault();
          event.stopPropagation();
          openMiniCartLoginModal();
          return;
        }

        const termsBox = root.querySelector('.noyona-mini-cart-terms-checkbox');
        const messages = [];
        if (hasMiniCartOutOfStockItem(root)) {
          messages.push(buildMiniCartStockMessages(getMiniCartOutOfStockNames())[0]);
        }
        if (termsBox && !termsBox.checked) {
          messages.push('Please agree to the Terms & Conditions and Shipping & Returns before continuing.');
        }

        if (messages.length) {
          event.preventDefault();
          event.stopPropagation();
          showMiniCartErrorNotice(root, messages);
          return;
        }
      }, true); // ← CAPTURE phase: fires before React & browser default

      if (!miniCartLoginEscapeBound) {
        miniCartLoginEscapeBound = true;
        document.addEventListener('keydown', (event) => {
          if (event.key !== 'Escape') return;
          closeMiniCartLoginModal();
        });
      }
    };

    // shipping is the real Store API total_shipping in major units (e.g. PHP),
    // or null when no rate has been computed for the customer's address yet.
    const applyMiniCartSummary = (root, subtotal, shipping) => {
      if (!root) return;

      const shippingBar = root.querySelector('.noyona-mini-cart-shipping');
      const subtotalEl = root.querySelector('.noyona-mini-cart-subtotal');
      const shippingEl = root.querySelector('.noyona-mini-cart-shipping-fee');
      const totalEl = root.querySelector('.noyona-mini-cart-total');

      const safeSubtotal = Math.max(0, Number(subtotal) || 0);
      const hasRealRate = Number.isFinite(shipping) && shipping > 0;

      if (shippingBar) {
        shippingBar.textContent = hasRealRate
          ? 'Shipping: ' + formatPeso(shipping)
          : 'Shipping calculated at checkout';
      }

      if (subtotalEl) subtotalEl.textContent = formatPeso(safeSubtotal);
      if (shippingEl) shippingEl.textContent = hasRealRate ? formatPeso(shipping) : '—';
      if (totalEl) totalEl.textContent = formatPeso(safeSubtotal + (hasRealRate ? shipping : 0));
    };

    const fetchStoreCart = () => fetch('/wp-json/wc/store/cart?_t=' + Date.now(), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    }).then((res) => (res.ok ? res.json() : null)).catch(() => null);

    const fetchMiniCartStockStatuses = () => {
      const ajaxUrl = (window.noyonaHeader && window.noyonaHeader.ajaxUrl)
        ? String(window.noyonaHeader.ajaxUrl)
        : '/wp-admin/admin-ajax.php';
      const url = ajaxUrl + (ajaxUrl.indexOf('?') === -1 ? '?' : '&') + 'action=noyona_cart_stock_status&_t=' + Date.now();

      return fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      })
        .then((res) => (res.ok ? res.json() : null))
        .then((json) => (json && json.success && json.data && Array.isArray(json.data.items) ? json.data.items : []))
        .catch(() => []);
    };

    const readCartItemCount = (cart) => {
      if (!cart || typeof cart !== 'object') return 0;
      if (Number.isFinite(Number(cart.items_count))) {
        return Math.max(0, Number(cart.items_count) || 0);
      }
      if (Array.isArray(cart.items)) {
        return cart.items.reduce((total, item) => {
          const qty = item && Number.isFinite(Number(item.quantity)) ? Number(item.quantity) : 0;
          return total + Math.max(0, qty);
        }, 0);
      }
      return 0;
    };

    // Treat a Store API response as trustworthy only when it is an object
    // that explicitly reports a finite `items_count`. Anything else (null,
    // network failure, malformed body, stale cache without the field) must
    // NOT be allowed to overwrite the server-rendered badge — doing so
    // hides a correct count behind the `--empty` class.
    const isValidStoreCart = (cart) =>
      !!cart &&
      typeof cart === 'object' &&
      Number.isFinite(Number(cart.items_count));

    const ensureHeaderCartBadge = () => {
      // Mount the custom badge as a SIBLING of the WC mini-cart button (or
      // the non-Woo fallback link), never inside the React-managed button.
      // WC Blocks hydration can remove/replace the native badge and any DOM
      // appended inside the button, so the visible count must live outside
      // React's mount point.
      const mounts = document.querySelectorAll(
        '.header-mini-cart, .header-icons .header-cart-fallback'
      );
      mounts.forEach((mount) => {
        let customBadge = mount.querySelector(':scope > .noyona-cart-count-badge');
        if (!customBadge) {
          customBadge = document.createElement('span');
          customBadge.className = 'noyona-cart-count-badge is-hidden';
          customBadge.textContent = '0';
          customBadge.setAttribute('aria-hidden', 'true');
          mount.appendChild(customBadge);
        }
      });
    };

    const syncHeaderCartBadge = (count) => {
      const safeCount = Math.max(0, parseInt(count, 10) || 0);
      ensureHeaderCartBadge();

      // Update ONLY the theme-owned badge. The WC native badge is hidden via
      // CSS and intentionally not touched here — letting WC manage its own
      // DOM/classes prevents fight-with-React races.
      document
        .querySelectorAll('.noyona-cart-count-badge')
        .forEach((badge) => {
          badge.textContent = String(safeCount);
          badge.classList.toggle('is-hidden', safeCount < 1);
          badge.setAttribute('aria-hidden', safeCount < 1 ? 'true' : 'false');
        });
    };

    let miniCartRowsRetryPending = false;
    let miniCartRowsRetryCount = 0;

    const requestMiniCartRowsRetry = () => {
      if (miniCartRowsRetryPending || miniCartRowsRetryCount >= 4) {
        return;
      }
      miniCartRowsRetryPending = true;
      miniCartRowsRetryCount += 1;

      document.body.dispatchEvent(
        new CustomEvent('wc-blocks_added_to_cart', {
          bubbles: true,
          detail: { preserveCartData: false, source: 'header-mini-cart-retry' },
        })
      );

      setTimeout(() => {
        miniCartRowsRetryPending = false;
        refreshMiniCart();
      }, 360);
    };

    const reconcileMiniCartRows = (root, cart) => {
      if (!root) return true;

      const items = cart && Array.isArray(cart.items) ? cart.items : [];
      const rows = Array.from(root.querySelectorAll('.wc-block-cart-items__row, .wc-block-mini-cart-items__row'));

      if (items.length < 1) {
        miniCartRowsRetryCount = 0;
        return true;
      }

      if (rows.length < 1) {
        requestMiniCartRowsRetry();
        return false;
      }

      if (rows.length !== items.length) {
        requestMiniCartRowsRetry();
      } else {
        miniCartRowsRetryCount = 0;
      }

      rows.forEach((row, index) => {
        const item = items[index];
        if (!item) return;
        const quantity = Math.max(0, parseInt(item.quantity, 10) || 0);

        const qtyInput = row.querySelector(
          '.wc-block-components-quantity-selector__input, .wc-block-components-quantity-selector input[type="number"], input.qty'
        );
        if (qtyInput && String(qtyInput.value || '') !== String(quantity)) {
          qtyInput.value = String(quantity);
          qtyInput.setAttribute('value', String(quantity));
        }
      });

      return true;
    };

    const refreshMiniCart = () => {
      Promise.all([fetchStoreCart(), fetchMiniCartStockStatuses()]).then(([cart, stockItems]) => {
        latestMiniCartStockItems = Array.isArray(stockItems) ? stockItems : [];
        if (isValidStoreCart(cart)) {
          syncHeaderCartBadge(Number(cart.items_count));
        } else {
          console.warn('[Noyona Header] Store cart response invalid; preserving existing cart badge.');
        }
        syncWooBlocksCartStore(cart);

        const root = getMiniCartRoot();
        if (!root) return;

        syncMiniCartStockBadges(root, cart, stockItems);
        syncProductTitlePriceRows(root, cart);
        syncSelectedVariationRows(root, cart);
        reconcileMiniCartRows(root, cart);
        syncCheckoutButton(root);
        syncTermsGate(root);
        bindMiniCartLoginModal();

        // Read full totals (subtotal + real shipping) from the Store API response.
        const totals = cart && cart.totals ? cart.totals : null;
        const subtotalMinor = totals
          ? (parseMinorAmount(totals.total_items) || parseMinorAmount(totals.subtotal_price) || parseMinorAmount(totals.total_price))
          : 0;
        const shippingMinor = totals ? parseMinorAmount(totals.total_shipping) : 0;
        const subtotalMajor = subtotalMinor > 0 ? subtotalMinor / 100 : readSubtotalFallback(root);
        const shippingMajor = shippingMinor > 0 ? shippingMinor / 100 : null;

        applyMiniCartSummary(root, subtotalMajor, shippingMajor);
      });
    };

    // Bind the checkout click interceptor once — must happen before any
    // user interaction, not inside refreshMiniCart which runs repeatedly.
    bindCheckoutClick();

    let refreshTimer = null;
    const queueRefresh = (delay = 180) => {
      clearTimeout(refreshTimer);
      refreshTimer = setTimeout(refreshMiniCart, delay);
    };
    const queueRefreshSeries = () => {
      queueRefresh(120);
      setTimeout(() => queueRefresh(420), 420);
      setTimeout(() => queueRefresh(980), 980);
      setTimeout(() => queueRefresh(1700), 1700);
      setTimeout(() => queueRefresh(2600), 2600);
    };

    document.addEventListener('click', (e) => {
      const clickedAddToCart = !!e.target.closest('.add_to_cart_button');
      if (
        e.target.closest('.wc-block-mini-cart__button') ||
        clickedAddToCart ||
        e.target.closest('.wc-block-components-quantity-selector__button') ||
        e.target.closest('.wc-block-cart-item__remove-link')
      ) {
        if (clickedAddToCart) {
          queueRefreshSeries();
        } else {
          queueRefresh(250);
        }
      }
    });

    if (window.jQuery) {
      // Note: `wc_fragments_refreshed` intentionally NOT bound here. It fires
      // automatically during WC's fragment hydration on every page load,
      // which (combined with queueRefreshSeries below) caused 5 redundant
      // /wp-json/wc/store/cart requests during the TBT measurement window.
      // User-driven cart changes still flow through added_to_cart /
      // removed_from_cart / updated_wc_div, plus the block-cart events bound
      // immediately below this block.
      window.jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div', () => {
        queueRefreshSeries();
      });
    }

    document.body.addEventListener('wc-blocks_added_to_cart', () => {
      queueRefreshSeries();
    });
    document.body.addEventListener('noyona_cart_added', () => {
      // Keep cart count based on server truth to avoid badge/cart mismatch.
      queueRefreshSeries();
    });

    queueRefresh(0);
  }

  function initAddToCartSuccessAnimation() {
    const ADD_TO_CART_SELECTOR = [
      '.add_to_cart_button',
      '.ajax_add_to_cart',
      '.single_add_to_cart_button',
      '.ps-btn-cart',
      '.ph-btn-cart',
      '.noyona-pdp-related__cart-btn--ajax',
      '.noyona-product-card-cart[data-cart-action="ajax"]',
      '[name="add-to-cart"]',
    ].join(',');
    const PRODUCT_SCOPE_SELECTOR = [
      '.wc-block-product',
      '.product',
      '.type-product',
      '.noyona-product-slide',
      '.product-slide__card',
      '.ps-card',
      '.product-highlight__card',
      '.ph-card',
      '.noyona-pdp-related__card',
      '.summary',
      'form.cart',
    ].join(',');
    const PRODUCT_IMAGE_SELECTOR = [
      '.ps-card__image',
      '.ph-card__image',
      '.wc-block-components-product-image img',
      '.noyona-pdp-related__image-wrap img',
      '.woocommerce-product-gallery__image img',
      '.wp-post-image',
      '.product img',
      'img',
    ].join(',');

    let lastClickedAddToCart = null;
    let lastClickedAt = 0;
    let lastAnimatedAt = 0;
    let toastTimer = null;

    const normalizeEventSource = (source) => {
      if (!source) return null;
      if (source.nodeType === 1) return source;
      if (source.jquery && source[0] && source[0].nodeType === 1) return source[0];
      if (Array.isArray(source) && source[0] && source[0].nodeType === 1) return source[0];
      return null;
    };

    const isMiniCartContext = (el) => {
      if (!el || typeof el.closest !== 'function') return false;
      return !!el.closest('.wc-block-mini-cart__drawer, .wp-block-woocommerce-mini-cart');
    };

    // Eligible = any add-to-cart action EXCEPT quantity changes inside the
    // mini-cart drawer (those re-use the cart line and must not re-trigger the
    // fly-to-cart animation).
    const isEligibleAddToCartSource = (source) => {
      const el = normalizeEventSource(source);
      if (!el) return false;
      if (isMiniCartContext(el)) return false;
      return true;
    };

    const canAnimateFromEvent = (source) => {
      if (isEligibleAddToCartSource(source)) {
        return true;
      }
      // Some add-to-cart flows emit events without a button reference.
      return !!lastClickedAddToCart
        && Date.now() - lastClickedAt < 1200
        && isEligibleAddToCartSource(lastClickedAddToCart);
    };

    const isVisible = (el) => {
      if (!el || typeof el.getBoundingClientRect !== 'function') return false;
      const rect = el.getBoundingClientRect();
      return rect.width > 0 && rect.height > 0;
    };

    const getProductImage = (source) => {
      if (!source) return null;
      const scope = source.closest(PRODUCT_SCOPE_SELECTOR);
      if (!scope) return null;
      const image = scope.querySelector(PRODUCT_IMAGE_SELECTOR);
      return isVisible(image) ? image : null;
    };

    const getSourceElement = (source) => {
      const explicitSource = normalizeEventSource(source);
      const recentClickSource = Date.now() - lastClickedAt < 4500 ? lastClickedAddToCart : null;
      const sourceEl = explicitSource || recentClickSource;
      return getProductImage(sourceEl) || sourceEl || null;
    };

    const getCartTargetRect = () => {
      const target = document.querySelector(
        '.header-mini-cart .wc-block-mini-cart__button,' +
          '.header-icons .header-cart-fallback,' +
          '.header-mini-cart,' +
          '.wp-block-woocommerce-mini-cart'
      );

      if (isVisible(target)) {
        return target.getBoundingClientRect();
      }

      return {
        left: window.innerWidth - 48,
        top: Math.max(16, parseInt(getComputedStyle(document.documentElement).getPropertyValue('--noyona-adminbar-height'), 10) || 16),
        width: 32,
        height: 32,
      };
    };

    const showSuccessToast = (message, type) => {
      let toast = document.querySelector('.noyona-add-cart-toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.className = 'noyona-add-cart-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        document.body.appendChild(toast);
      }

      const toastType = type === 'error' ? 'error' : 'success';
      const icon = toastType === 'error' ? '!' : '✓';
      const text = message || 'Added to cart';

      toast.classList.toggle('is-error', toastType === 'error');
      toast.classList.toggle('is-success', toastType !== 'error');
      toast.setAttribute('role', toastType === 'error' ? 'alert' : 'status');
      toast.innerHTML = '<span class="noyona-add-cart-toast__icon" aria-hidden="true">' + icon + '</span><span></span>';
      toast.querySelector('span:last-child').textContent = text;

      toast.classList.remove('is-visible');
      window.requestAnimationFrame(() => {
        toast.classList.add('is-visible');
      });

      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => {
        toast.classList.remove('is-visible');
      }, toastType === 'error' ? 2400 : 1800);
    };

    const pulseCartIcon = () => {
      const target = document.querySelector(
        '.header-mini-cart .wc-block-mini-cart__button,' +
          '.header-icons .header-cart-fallback,' +
          '.header-mini-cart'
      );
      if (!target) return;

      target.classList.remove('noyona-cart-success-pulse');
      void target.offsetWidth;
      target.classList.add('noyona-cart-success-pulse');
      setTimeout(() => {
        target.classList.remove('noyona-cart-success-pulse');
      }, 850);
    };

    const createFlyer = () => {
      // Always a compact magenta badge with a check icon. This reads clearly
      // against any background and never depends on a product image that may
      // still be lazy-loading (e.g. wishlist thumbnails with empty currentSrc).
      const flyer = document.createElement('div');
      flyer.className = 'noyona-add-cart-flyer noyona-add-cart-flyer--badge';
      flyer.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
      return flyer;
    };

    const runFlyAnimation = (sourceEl, options) => {
      // The throttle only exists to dedupe the auto event-driven path, where a
      // single add can fire both `added_to_cart` and `noyona_cart_added`.
      // Explicit callers (flyFrom / flyFromRect) pass `force` because each call
      // is a distinct, intentional add and must never be swallowed — otherwise
      // rapid sequential adds (e.g. wishlist on a fast server) stop animating.
      const forced = !!(options && options.force);
      const now = Date.now();
      if (!forced && now - lastAnimatedAt < 450) {
        return;
      }
      lastAnimatedAt = now;

      const explicitRect = options && options.sourceRect ? options.sourceRect : null;
      const sourceRect = explicitRect
        ? explicitRect
        : isVisible(sourceEl)
        ? sourceEl.getBoundingClientRect()
        : {
            left: window.innerWidth / 2 - 22,
            top: window.innerHeight / 2 - 22,
            width: 44,
            height: 44,
          };
      const cartRect = getCartTargetRect();
      const flyer = createFlyer(sourceEl, sourceRect);
      const startSize = Math.min(72, Math.max(38, Math.min(sourceRect.width || 48, sourceRect.height || 48)));
      const startX = sourceRect.left + (sourceRect.width - startSize) / 2;
      const startY = sourceRect.top + (sourceRect.height - startSize) / 2;
      const endX = cartRect.left + cartRect.width / 2 - 12;
      const endY = cartRect.top + cartRect.height / 2 - 12;

      flyer.style.left = startX + 'px';
      flyer.style.top = startY + 'px';
      flyer.style.width = startSize + 'px';
      flyer.style.height = startSize + 'px';
      flyer.style.setProperty('--noyona-add-cart-x', Math.round(endX - startX) + 'px');
      flyer.style.setProperty('--noyona-add-cart-y', Math.round(endY - startY) + 'px');
      document.body.appendChild(flyer);

      // Force the browser to commit the flyer's start state before flipping it
      // to `is-flying`. Without this, an idle page (e.g. the 2nd+ wishlist add)
      // coalesces the insert and the class change into a single style recalc,
      // so the transition never runs and the flight silently skips. A single
      // requestAnimationFrame is not enough on its own — read layout to flush.
      void flyer.getBoundingClientRect();

      window.requestAnimationFrame(() => {
        flyer.classList.add('is-flying');
      });

      setTimeout(() => {
        flyer.remove();
        pulseCartIcon();
      }, 820);

      if (!options || options.toast !== false) {
        showSuccessToast(options && options.message, 'success');
      }
    };

    // On PDP the source element is resolved from the click/event (form image).
    const animateSuccess = (source) => {
      runFlyAnimation(getSourceElement(source));
    };

    // Reusable trigger so non-PDP surfaces (e.g. the account wishlist) can run
    // the exact same fly-to-cart animation, but start it from their own source
    // element (the wishlist row image) rather than a PDP product form.
    const openDrawerFn =
      (window.noyonaCartFx && typeof window.noyonaCartFx.openDrawer === 'function' && window.noyonaCartFx.openDrawer) ||
      function (options) {
        return openMiniCartDrawer(options);
      };

    window.noyonaCartFx = {
      flyFrom: function (sourceEl, options) {
        runFlyAnimation(sourceEl || null, Object.assign({ force: true }, options || {}));
      },
      flyFromRect: function (sourceRect, options) {
        runFlyAnimation(null, Object.assign({ force: true }, options || {}, { sourceRect: sourceRect }));
      },
      toast: function (message, type) {
        showSuccessToast(message, type);
      },
      pulse: pulseCartIcon,
      openDrawer: openDrawerFn,
    };

    // Auto-binding for every add-to-cart surface (PDP form, shop/related cards,
    // carousels, etc.): track the clicked add-to-cart button and animate when
    // WooCommerce reports the line was added. Quantity changes inside the
    // mini-cart drawer are excluded via the eligibility check. Surfaces without
    // a Woo add-to-cart event (e.g. the wishlist) drive the animation
    // explicitly via window.noyonaCartFx.flyFrom().
    document.addEventListener(
      'click',
      (event) => {
        const button = event.target.closest(ADD_TO_CART_SELECTOR);
        if (!button) return;
        if (!isEligibleAddToCartSource(button)) return;
        lastClickedAddToCart = button;
        lastClickedAt = Date.now();
      },
      true
    );

    if (window.jQuery) {
      window.jQuery(document.body).on('added_to_cart', (event, fragments, cartHash, button) => {
        if (!canAnimateFromEvent(button)) return;
        animateSuccess(button);
      });
    }

    document.body.addEventListener('wc-blocks_added_to_cart', (event) => {
      if (event.detail && event.detail.source === 'header-mini-cart-retry') return;
      if (event.detail && event.detail.skipAnimation) return;
      const button = event.detail && event.detail.button ? event.detail.button : null;
      if (!canAnimateFromEvent(button)) return;
      animateSuccess(event.detail && event.detail.button ? event.detail.button : null);
    });

    document.body.addEventListener('noyona_cart_added', (event) => {
      if (event.detail && event.detail.skipAnimation) return;
      const button = event.detail && event.detail.button ? event.detail.button : null;
      if (!canAnimateFromEvent(button)) return;
      animateSuccess(event.detail && event.detail.button ? event.detail.button : null);
    });

    document.body.addEventListener('noyona_pdp_toast', (event) => {
      const detail = event.detail || {};
      showSuccessToast(detail.message || '', detail.type || 'error');
    });
  }

  // Account > Wishlist: add the item to the cart via AJAX (no redirect to the
  // cart page), play the shared fly-to-cart animation from the wishlist row
  // image, open the header mini-cart drawer, then drop the item from the
  // wishlist once it is in the cart.
  function initWishlistAddToCart() {
    const wishlistAddEndpoint = () => {
      if (
        typeof window.wc_add_to_cart_params !== 'undefined' &&
        window.wc_add_to_cart_params &&
        window.wc_add_to_cart_params.wc_ajax_url
      ) {
        return String(window.wc_add_to_cart_params.wc_ajax_url).replace('%%endpoint%%', 'add_to_cart');
      }
      return '/?wc-ajax=add_to_cart';
    };

    const headerData = () => (typeof window.noyonaHeader !== 'undefined' && window.noyonaHeader) || {};

    // Refresh the header cart count / fragments without a button reference, so
    // the global animation listener does not re-fire (we run the fly-to-cart
    // explicitly from the wishlist row) and the mini-cart drawer stays closed.
    const broadcastCartAdded = () => {
      const detail = { source: 'wishlist', button: null, skipAnimation: true };
      if (window.jQuery) {
        window.jQuery(document.body).trigger('wc_fragment_refresh');
      }
      document.body.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart', { bubbles: true, detail }));
      document.body.dispatchEvent(new CustomEvent('noyona_cart_added', { bubbles: true, detail }));
    };

    const removeFromWishlist = (productId, variationId) => {
      const data = headerData();
      const nonce = (data.wishlist && data.wishlist.nonce) || '';
      const ajaxUrl = data.ajaxUrl || '/wp-admin/admin-ajax.php';
      if (!nonce) {
        return Promise.resolve(null);
      }
      const body = new URLSearchParams();
      body.set('action', 'noyona_toggle_product_wishlist');
      body.set('nonce', nonce);
      body.set('product_id', String(productId));
      body.set('variation_id', String(variationId || 0));
      return fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      })
        .then((r) => (r.ok ? r.json() : null))
        .catch(() => null);
    };

    const dropRow = (row) => {
      if (!row) return;
      const tbody = row.parentNode;
      row.style.transition = 'opacity .25s ease';
      row.style.opacity = '0';
      window.setTimeout(() => {
        row.remove();
        // When the last item is gone, reload so the proper empty-state card and
        // pagination render server-side.
        if (tbody && !tbody.querySelector('tr')) {
          window.location.reload();
        }
      }, 260);
    };

    const setBusy = (button, busy) => {
      if (!button) return;
      if (busy) {
        button.classList.add('is-loading');
        button.setAttribute('aria-disabled', 'true');
      } else {
        button.classList.remove('is-loading');
        button.removeAttribute('aria-disabled');
      }
    };

    const showError = () => {
      if (window.noyonaCartFx && typeof window.noyonaCartFx.toast === 'function') {
        window.noyonaCartFx.toast('This product cannot be added to cart right now.', 'error');
      }
    };

    const handleAdd = (button) => {
      if (!button || button.getAttribute('aria-disabled') === 'true' || button.classList.contains('is-loading')) {
        return;
      }
      const addId = parseInt(button.getAttribute('data-add-id'), 10) || 0;
      const productId = parseInt(button.getAttribute('data-product-id'), 10) || 0;
      const variationId = parseInt(button.getAttribute('data-variation-id'), 10) || 0;
      if (addId < 1) {
        return;
      }

      const row = button.closest('tr');
      // Start every wishlist fly-to-cart from the "My Wishlist" heading. Product
      // rows get removed/shift after each add (and bottom rows can sit below the
      // fold), so a fixed, always-visible anchor is far more reliable than the
      // per-row button. Captured on click, before any layout changes.
      const anchor =
        document.querySelector('.noyona-account-wishlist-header h3') ||
        document.querySelector('#noyona-account-wishlist-panel h3') ||
        button;
      const startRect = anchor.getBoundingClientRect();

      setBusy(button, true);

      const payload = new URLSearchParams();
      payload.set('product_id', String(addId));
      if (variationId > 0) {
        payload.set('variation_id', String(variationId));
      }
      payload.set('quantity', '1');

      fetch(wishlistAddEndpoint(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
      })
        .then((res) => res.text())
        .then((raw) => {
          let data = null;
          if (raw) {
            try {
              data = JSON.parse(raw);
            } catch (e) {
              data = null;
            }
          }

          if (data && data.error) {
            setBusy(button, false);
            showError();
            return;
          }

          if (window.noyonaCartFx && typeof window.noyonaCartFx.flyFromRect === 'function') {
            window.noyonaCartFx.flyFromRect(startRect);
          } else if (window.noyonaCartFx && typeof window.noyonaCartFx.flyFrom === 'function') {
            window.noyonaCartFx.flyFrom(button);
          }

          broadcastCartAdded();

          removeFromWishlist(productId, variationId).then((resp) => {
            const removed = !!(resp && resp.success && resp.data && resp.data.saved === false);
            if (removed) {
              dropRow(row);
            } else {
              // Cart add succeeded but wishlist removal did not; keep the row
              // interactive so the shopper can remove it manually.
              setBusy(button, false);
            }
          });
        })
        .catch(() => {
          setBusy(button, false);
          showError();
        });
    };

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-noyona-wishlist-add]');
      if (!button) return;
      event.preventDefault();
      handleAdd(button);
    });
  }

  function initHeaderCartFallback() {
    const iconsRoot = document.querySelector('.header-icons');
    if (!iconsRoot) return;

    const ensureCartIcon = () => {
      const hasMiniCartButton = !!iconsRoot.querySelector('.wc-block-mini-cart__button');
      const existingFallback = iconsRoot.querySelector('.header-cart-fallback');

      if (hasMiniCartButton) {
        if (existingFallback) existingFallback.remove();
        return;
      }

      if (existingFallback) return;

      const fallbackLink = document.createElement('a');
      fallbackLink.className = 'header-icon header-cart-fallback';
      fallbackLink.href = '#open-cart';
      fallbackLink.setAttribute('aria-label', 'Open cart');
      fallbackLink.innerHTML = '<i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>';

      const storeLink = iconsRoot.querySelector('.header-store-link');
      if (storeLink && storeLink.parentNode === iconsRoot) {
        iconsRoot.insertBefore(fallbackLink, storeLink);
      } else {
        iconsRoot.appendChild(fallbackLink);
      }
    };

    ensureCartIcon();
    setTimeout(ensureCartIcon, 150);
    setTimeout(ensureCartIcon, 500);

    const observer = new MutationObserver(() => ensureCartIcon());
    observer.observe(iconsRoot, { childList: true, subtree: true });
  }

  /**
   * Auto-open the mini cart drawer:
   *  – when redirected from /cart via ?open-cart=1
   *  – when any link with href="#open-cart" is clicked
   */
  function initMiniCartAutoOpen() {
    // Auto-open after /cart → /?open-cart=1 redirect.
    const params = new URLSearchParams(window.location.search);
    if (params.has('open-cart')) {
      params.delete('open-cart');
      const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
      window.history.replaceState({}, '', clean);

      // Give WC blocks time to mount, then retry if the mini-cart button is late.
      openMiniCartDrawer({ delay: 400, retries: 12, retryInterval: 250 });
    }

    // Handle #open-cart links anywhere on the page.
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[href="#open-cart"]');
      if (!link) return;
      e.preventDefault();
      openMiniCartDrawer();
    });
  }

  function initAccountDropdown() {
    $$('.header-account').forEach((wrapper) => {
      const toggle = wrapper.querySelector('.header-account-toggle');
      const menu = wrapper.querySelector('.header-account-menu');
      if (!toggle || !menu) return;

      const closeMenu = () => {
        wrapper.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
      };

      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const open = wrapper.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.setAttribute('aria-hidden', open ? 'false' : 'true');
      });

      document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) closeMenu();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
      });
    });
  }

  function initLogoutLinks() {
    const logoutUrl = (window.noyonaHeader && window.noyonaHeader.logoutUrl) ? window.noyonaHeader.logoutUrl : '';
    if (!logoutUrl) return;

    $$('.header-account-logout, .mobile-logout').forEach((a) => {
      a.setAttribute('href', logoutUrl);
    });
  }

  function initMobileMenu() {
    const burger = $('.mobile-menu-toggle');
    const navList = $('.nav-list');
    if (!burger || !navList) return;

    const icon = burger.querySelector('i');

    const closeMenu = () => {
      document.body.classList.remove('is-menu-open');
      burger.setAttribute('aria-expanded', 'false');
      if (icon) {
        icon.classList.add('fa-bars');
        icon.classList.remove('fa-xmark');
      }
    };

    const openMenu = () => {
      document.body.classList.add('is-menu-open');
      burger.setAttribute('aria-expanded', 'true');
      if (icon) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-xmark');
      }
    };

    burger.addEventListener('click', (e) => {
      e.preventDefault();
      document.body.classList.contains('is-menu-open') ? closeMenu() : openMenu();
    });

    document.addEventListener('click', (e) => {
      if (!document.body.classList.contains('is-menu-open')) return;
      if (navList.contains(e.target) || burger.contains(e.target)) return;
      closeMenu();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMenu();
    });
  }

  function initMobileSubmenus() {
    const toggleSubmenu = (navItem, toggle) => {
      const icon = toggle ? toggle.querySelector('i') : null;
      if (!navItem) return;

      const willOpen = !navItem.classList.contains('is-open');
      if (willOpen) {
        $$('.nav-item.has-dropdown.is-open').forEach((openItem) => {
          if (openItem === navItem) return;
          openItem.classList.remove('is-open');

          const openToggle = openItem.querySelector('.submenu-toggle');
          const openIcon = openToggle ? openToggle.querySelector('i') : null;
          const openTrigger = openItem.querySelector('[data-dropdown-trigger]');

          if (openToggle) {
            openToggle.setAttribute('aria-expanded', 'false');
          }
          if (openTrigger) {
            openTrigger.setAttribute('aria-expanded', 'false');
          }
          if (openIcon) {
            openIcon.classList.add('fa-plus');
            openIcon.classList.remove('fa-minus');
          }
        });
      }

      navItem.classList.toggle('is-open', willOpen);
      if (toggle) {
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      }

      const trigger = navItem.querySelector('[data-dropdown-trigger]');
      if (trigger) {
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      }

      if (icon) {
        icon.classList.toggle('fa-plus', !willOpen);
        icon.classList.toggle('fa-minus', willOpen);
      }
    };

    $$('.submenu-toggle').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const navItem = btn.closest('.nav-item');
        toggleSubmenu(navItem, btn);
      });
    });

    $$('[data-dropdown-trigger]').forEach((trigger) => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const navItem = trigger.closest('.nav-item');
        const toggle = navItem ? navItem.querySelector('.submenu-toggle') : null;
        toggleSubmenu(navItem, toggle);
      });
    });
  }

  function initActiveNavLinks() {
    const currentPath = window.location.pathname;
    const navLinks = $$('.nav-link, .dropdown-menu a, .mobile-drawer-links a');

    navLinks.forEach((link) => {
      if (link.hasAttribute('data-dropdown-trigger')) {
        return;
      }

      const linkPath = new URL(link.href, window.location.origin).pathname;

      if (currentPath === linkPath) {
        link.classList.add('is-active');

        // Handle dropdown logic
        const dropdown = link.closest('.dropdown-menu');
        if (dropdown) {
          const navItem = dropdown.closest('.nav-item');
          if (navItem) {
            navItem.classList.add('has-active-child');
            // Also mark parent Link as active to trigger its underline
            const parentLink = navItem.querySelector('.nav-link');
            if (parentLink) parentLink.classList.add('is-active');
          }
        }
      }
    });
  }

  function normalizeShopDropdownLinks() {
    const navItems = Array.from(document.querySelectorAll('.nav-item.has-dropdown'));
    const shopNavItem = navItems.find((item) => {
      const topLink = item.querySelector('.nav-link-wrapper .nav-link');
      return topLink && topLink.textContent && topLink.textContent.trim().toLowerCase() === 'shop';
    });

    if (!shopNavItem) return;

    const labelToPath = {
      face: '/face/',
      lip: '/lips/',
      lips: '/lips/',
      eye: '/eyes/',
      eyes: '/eyes/',
      hair: '/hair/',
      body: '/body/',
    };

    const normalize = (value) => String(value || '').trim().toLowerCase();
    const toPathname = (href) => {
      try {
        return new URL(href, window.location.origin).pathname;
      } catch (e) {
        return '';
      }
    };

    const shopTopLink = shopNavItem.querySelector('.nav-link-wrapper .nav-link');
    if (shopTopLink) {
      const currentPath = toPathname(shopTopLink.getAttribute('href') || shopTopLink.href);
      if (currentPath !== '/shop/') {
        shopTopLink.setAttribute('href', '/shop/');
      }
    }

    const subLinks = Array.from(shopNavItem.querySelectorAll('.dropdown-menu a'));
    subLinks.forEach((link) => {
      const key = normalize(link.textContent);
      const expectedPath = labelToPath[key];
      if (!expectedPath) return;

      const currentPath = toPathname(link.getAttribute('href') || link.href);
      if (currentPath !== expectedPath) {
        link.setAttribute('href', expectedPath);
      }
    });
  }

  function initBrandStripFallback() {
    let strip = document.querySelector('.header-brand-strip');
    if (!strip) {
      const siteHeader = document.querySelector('.site-header');
      if (!siteHeader || !siteHeader.parentNode) return;

      strip = document.createElement('div');
      strip.className = 'wp-block-group header-brand-strip';
      siteHeader.parentNode.insertBefore(strip, siteHeader);
    }

    const hasCarousel = strip.querySelector(
      '.wp-block-noyona-brand-carousel, .brand-carousel, .brand-carousel__track'
    );
    if (hasCarousel) return;

    const textContent = String(strip.textContent || '').trim();
    if (textContent.length > 0) return;

    const fallback = document.createElement('div');
    fallback.className = 'noyona-brand-strip-fallback';
    fallback.setAttribute('aria-label', 'Beauty rooted in nature');

    const track = document.createElement('div');
    track.className = 'noyona-brand-strip-fallback__track';

    const baseItems = [
      'Beauty rooted in nature',
      'Beauty rooted in nature',
      'Beauty rooted in nature',
      'Beauty rooted in nature',
      'Beauty rooted in nature',
      'Beauty rooted in nature',
    ];

    const items = baseItems.concat(baseItems);
    items.forEach((label) => {
      const item = document.createElement('span');
      item.className = 'noyona-brand-strip-fallback__item';
      item.textContent = label;
      track.appendChild(item);
    });

    fallback.appendChild(track);
    strip.appendChild(fallback);
  }

  function initShopArchiveViewToggle() {
    const toggle = document.querySelector('.noyona-shop-view-toggle');
    const productCollection = document.querySelector('.noyona-shop-products');
    if (!toggle || !productCollection) return;

    const singleButton = toggle.querySelector('button[data-shop-view-toggle]');
    const buttons = Array.from(toggle.querySelectorAll('button[data-shop-view]'));
    if (!singleButton && !buttons.length) return;
    const storageKey = 'noyonaShopViewMode';

    const getStoredView = () => {
      try {
        return localStorage.getItem(storageKey);
      } catch (e) {
        return '';
      }
    };

    const setStoredView = (view) => {
      try {
        localStorage.setItem(storageKey, view);
      } catch (e) {
        // noop
      }
    };

    const applyView = (view) => {
      const isList = view === 'list';
      document.body.classList.toggle('noyona-shop-view-list', isList);
      document.body.classList.toggle('noyona-shop-view-grid', !isList);
      productCollection.setAttribute('data-shop-view', isList ? 'list' : 'grid');
      setStoredView(isList ? 'list' : 'grid');

      if (singleButton) {
        const toList = !isList;
        singleButton.setAttribute(
          'aria-label',
          toList ? 'Switch to list view' : 'Switch to grid view'
        );

        const gridIcon = singleButton.querySelector('.icon-grid');
        const listIcon = singleButton.querySelector('.icon-list');
        if (gridIcon) {
          gridIcon.style.display = isList ? '' : 'none';
        }
        if (listIcon) {
          listIcon.style.display = isList ? 'none' : '';
        }
      } else {
        buttons.forEach((button) => {
          const active = button.dataset.shopView === (isList ? 'list' : 'grid');
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
      }
    };

    const params = new URLSearchParams(window.location.search);
    const fromUrl = params.get('shop_view');
    const fromStorage = getStoredView();
    const initial = (fromUrl === 'list' || fromUrl === 'grid')
      ? fromUrl
      : (fromStorage === 'list' || fromStorage === 'grid')
        ? fromStorage
        : (document.body.classList.contains('noyona-shop-view-list') ? 'list' : 'grid');
    applyView(initial);

    if (singleButton) {
      singleButton.addEventListener('click', () => {
        const currentlyList = document.body.classList.contains('noyona-shop-view-list');
        applyView(currentlyList ? 'grid' : 'list');
      });
    } else {
      buttons.forEach((button) => {
        button.addEventListener('click', () => applyView(button.dataset.shopView || 'grid'));
      });
    }
  }

  function initShopToolbarControlsPlacement() {
    const placeToggle = () => {
      const toolbar = document.querySelector('.noyona-shop-toolbar-right');
      const sortSelect = document.querySelector('.noyona-shop-sort-select');
      const priceDropdown = document.querySelector('.noyona-shop-price-dropdown');
      const wrapper = document.querySelector('.noyona-shop-view-wrapper');
      const filterToggle = document.querySelector('.noyona-shop-filter-toggle');
      if (!toolbar || !sortSelect || !wrapper) return false;

      let dropdownsGroup = toolbar.querySelector('.noyona-shop-toolbar-dropdowns');
      let iconsGroup = toolbar.querySelector('.noyona-shop-toolbar-icons');

      if (!dropdownsGroup) {
        dropdownsGroup = document.createElement('div');
        dropdownsGroup.className = 'noyona-shop-toolbar-dropdowns';
        toolbar.appendChild(dropdownsGroup);
      }

      if (!iconsGroup) {
        iconsGroup = document.createElement('div');
        iconsGroup.className = 'noyona-shop-toolbar-icons';
        toolbar.appendChild(iconsGroup);
      }

      if (priceDropdown && priceDropdown.parentElement !== dropdownsGroup) {
        dropdownsGroup.appendChild(priceDropdown);
      }
      if (sortSelect && sortSelect.parentElement !== dropdownsGroup) {
        dropdownsGroup.appendChild(sortSelect);
      }
      if (priceDropdown && sortSelect && priceDropdown.nextElementSibling !== sortSelect) {
        priceDropdown.after(sortSelect);
      }

      if (filterToggle && filterToggle.parentElement !== iconsGroup) {
        iconsGroup.appendChild(filterToggle);
      }
      if (wrapper.parentElement !== iconsGroup) {
        iconsGroup.appendChild(wrapper);
      }
      if (filterToggle && filterToggle.nextElementSibling !== wrapper) {
        filterToggle.after(wrapper);
      }

      if (toolbar.firstElementChild !== dropdownsGroup) {
        toolbar.prepend(dropdownsGroup);
      }
      if (dropdownsGroup.nextElementSibling !== iconsGroup) {
        dropdownsGroup.after(iconsGroup);
      }

      return true;
    };

    if (placeToggle()) return;

    const root = document.querySelector('.noyona-shop-archive');
    if (!root || typeof MutationObserver === 'undefined') return;

    const observer = new MutationObserver(() => {
      if (placeToggle()) {
        observer.disconnect();
      }
    });

    observer.observe(root, { childList: true, subtree: true });
  }

  function initShopMobileCategoriesPlacement() {
    const categories = document.querySelector('.noyona-shop-categories');
    const panel = document.querySelector('#noyona-shop-filter-panel');
    if (!categories || !panel || !categories.parentNode) return;

    // Keep categories inside the filter panel across all breakpoints.
    if (categories.parentNode !== panel) {
      panel.prepend(categories);
    }

    if (!categories.querySelector('.noyona-shop-filter-section-title')) {
      const title = document.createElement('h5');
      title.className = 'noyona-shop-filter-section-title';
      title.textContent = 'Categories';
      categories.prepend(title);
    }
  }

  function initShopBrandFilters() {
    const panel = document.querySelector('#noyona-shop-filter-panel');
    if (!panel || !window.noyonaHeader || !Array.isArray(window.noyonaHeader.shopProductBrands)) return;

    const brands = window.noyonaHeader.shopProductBrands;
    if (!brands.length || panel.querySelector('.noyona-shop-filter-section-brands')) return;

    const params = new URLSearchParams(window.location.search);
    const getSelectedBrandSlugs = () => {
      const slugs = [];
      params.getAll('product_brand').forEach((value) => slugs.push(value));
      params.getAll('product_brand[]').forEach((value) => slugs.push(value));
      params.forEach((value, key) => {
        if (/^product_brand\[\d+\]$/.test(key)) {
          slugs.push(value);
        }
      });
      const combined = slugs.join(',');
      return new Set(
        combined
          .split(',')
          .map((value) => String(value).trim().toLowerCase())
          .filter(Boolean)
      );
    };

    const selectedBrands = getSelectedBrandSlugs();
    const section = document.createElement('section');
    section.className = 'noyona-shop-filter-section noyona-shop-filter-section-brands';

    const title = document.createElement('h5');
    title.textContent = 'Brands';
    section.appendChild(title);

    const list = document.createElement('div');
    list.className = 'noyona-shop-tag-radio-list';

    const isPaginationKey = (key) =>
      key === 'paged' || key === 'product-page' || key === 'search_page' || /^query-\d+-page$/.test(key);

    const navigate = (checkedSlugs) => {
      const nextParams = new URLSearchParams(window.location.search);
      nextParams.delete('product_brand');
      Array.from(nextParams.keys()).forEach((key) => {
        if (key.startsWith('product_brand[')) {
          nextParams.delete(key);
        }
      });
      if (checkedSlugs.length) {
        nextParams.set('product_brand', checkedSlugs.join(','));
      }
      Array.from(nextParams.keys()).forEach((key) => {
        if (isPaginationKey(key)) {
          nextParams.delete(key);
        }
      });

      const query = nextParams.toString();
      window.location.assign(window.location.pathname + (query ? '?' + query : ''));
    };

    brands.forEach((brand) => {
      if (!brand || !brand.slug || !brand.name) return;

      const slug = String(brand.slug).toLowerCase();
      const isAvailable = Boolean(brand.hasProducts);
      const isSelected = isAvailable && selectedBrands.has(slug);
      const label = document.createElement('label');
      label.className = 'noyona-shop-tag-radio'
        + (isAvailable ? '' : ' is-disabled')
        + (isSelected ? ' is-active' : '');

      const input = document.createElement('input');
      input.type = 'checkbox';
      input.name = 'noyona-product-brand';
      input.value = slug;
      input.checked = isSelected;
      input.disabled = !isAvailable;
      if (!isAvailable) {
        input.setAttribute('aria-disabled', 'true');
      }

      const text = document.createElement('span');
      text.textContent = String(brand.name);

      if (isAvailable) {
        input.addEventListener('change', () => {
          label.classList.toggle('is-active', input.checked);
          const checkedSlugs = Array.from(
            list.querySelectorAll('input[type="checkbox"]:checked')
          ).map((item) => String(item.value).toLowerCase());
          navigate(checkedSlugs);
        });
      }

      label.appendChild(input);
      label.appendChild(text);
      list.appendChild(label);
    });

    section.appendChild(list);

    const categories = panel.querySelector('.noyona-shop-categories');
    if (categories) {
      panel.insertBefore(section, categories);
    } else {
      panel.prepend(section);
    }
  }

  function initShopTagFilters() {
    const panel = document.querySelector('#noyona-shop-filter-panel');
    if (!panel || !window.noyonaHeader || !Array.isArray(window.noyonaHeader.shopProductTags)) return;

    const tags = window.noyonaHeader.shopProductTags;
    if (!tags.length || panel.querySelector('.noyona-shop-filter-section-tags')) return;

    const params = new URLSearchParams(window.location.search);
    const selectedTag = String(params.get('product_tag') || '').toLowerCase();
    const section = document.createElement('section');
    section.className = 'noyona-shop-filter-section noyona-shop-filter-section-tags';

    const title = document.createElement('h5');
    title.textContent = 'Tags';
    section.appendChild(title);

    const list = document.createElement('div');
    list.className = 'noyona-shop-tag-radio-list';

    const isPaginationKey = (key) =>
      key === 'paged' || key === 'product-page' || key === 'search_page' || /^query-\d+-page$/.test(key);

    const sortedTags = tags
      .filter((tag) => tag && tag.slug && tag.name)
      .sort((a, b) => {
        const aAvailable = Boolean(a.hasProducts);
        const bAvailable = Boolean(b.hasProducts);
        if (aAvailable !== bAvailable) {
          return aAvailable ? -1 : 1;
        }
        return String(a.name).localeCompare(String(b.name), undefined, { sensitivity: 'base' });
      });

    sortedTags.forEach((tag) => {
      const slug = String(tag.slug).toLowerCase();
      const isAvailable = Boolean(tag.hasProducts);
      const isSelected = isAvailable && selectedTag === slug;
      const label = document.createElement('label');
      label.className = 'noyona-shop-tag-radio'
        + (isAvailable ? '' : ' is-disabled')
        + (isSelected ? ' is-active' : '');

      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'noyona-product-tag';
      input.value = slug;
      input.checked = isSelected;
      input.disabled = !isAvailable;
      if (!isAvailable) {
        input.setAttribute('aria-disabled', 'true');
      }

      const text = document.createElement('span');
      text.textContent = String(tag.name);

      if (isAvailable) {
        input.addEventListener('change', () => {
          if (!input.checked) return;

          const nextParams = new URLSearchParams(window.location.search);
          nextParams.set('product_tag', slug);
          Array.from(nextParams.keys()).forEach((key) => {
            if (isPaginationKey(key)) {
              nextParams.delete(key);
            }
          });

          const query = nextParams.toString();
          window.location.assign(window.location.pathname + (query ? '?' + query : ''));
        });
      }

      label.appendChild(input);
      label.appendChild(text);
      list.appendChild(label);
    });

    section.appendChild(list);

    const categories = panel.querySelector('.noyona-shop-categories');
    if (categories && categories.nextSibling) {
      panel.insertBefore(section, categories.nextSibling);
    } else if (categories) {
      panel.appendChild(section);
    } else {
      panel.prepend(section);
    }
  }

  function initShopCategoryActiveByPath() {
    const categoryWrap = document.querySelector('.noyona-shop-categories');
    if (!categoryWrap) return;

    const normalizePath = (path) => {
      const cleaned = String(path || '').replace(/\/+$/, '');
      return cleaned || '/';
    };

    const currentPath = normalizePath(window.location.pathname);
    const allProductsLink = categoryWrap.querySelector('.noyona-shop-category-all');
    const categoryLinks = Array.from(
      categoryWrap.querySelectorAll('.wc-block-product-categories-list-item > a')
    );
    const params = new URLSearchParams(window.location.search);

    if (document.querySelector('.noyona-product-search-page')) {
      const currentCategory = String(params.get('product_cat') || '').toLowerCase();
      categoryLinks.forEach((link) => {
        const linkUrl = new URL(link.href, window.location.origin);
        const linkCategory = String(linkUrl.searchParams.get('product_cat') || '').toLowerCase();
        const isActive = currentCategory !== '' && linkCategory === currentCategory;
        link.classList.toggle('is-active', isActive);
        if (isActive) {
          link.setAttribute('aria-current', 'page');
        } else {
          link.removeAttribute('aria-current');
        }
      });

      if (allProductsLink) {
        allProductsLink.classList.toggle('is-active', currentCategory === '');
      }
      return;
    }

    let hasMatchedCategory = false;
    categoryLinks.forEach((link) => {
      const linkPath = normalizePath(new URL(link.href, window.location.origin).pathname);
      const isActive = linkPath === currentPath;
      link.classList.toggle('is-active', isActive);
      if (isActive) hasMatchedCategory = true;
    });

    if (allProductsLink) {
      const isShopRoot = currentPath === '/shop';
      allProductsLink.classList.toggle('is-active', isShopRoot && !hasMatchedCategory);
    }
  }

  function initShopPriceFilter() {
    const root = document.querySelector('.noyona-shop-filter-price');
    if (!root) return;

    const minInput = root.querySelector('input[name="noyona-price-min"], input[name="min_price"]');
    const maxInput = root.querySelector('input[name="noyona-price-max"], input[name="max_price"]');
    const slider = root.querySelector('.noyona-shop-filter-price-slider');
    const minThumb = root.querySelector('.noyona-shop-filter-price-thumb.is-min');
    const maxThumb = root.querySelector('.noyona-shop-filter-price-thumb.is-max');
    const trackFill = root.querySelector('.noyona-shop-filter-price-track-fill');
    const labels = root.querySelector('.noyona-shop-filter-price-labels');

    if (!minInput || !maxInput || !slider || !minThumb || !maxThumb || !trackFill || !labels) {
      return;
    }

    const config = (window.noyonaHeader && window.noyonaHeader.shopPriceFilter) || {};
    const absoluteMin = Math.max(0, parseInt(root.dataset.min || '0', 10) || 0);
    const step = Math.max(1, parseInt(root.dataset.step || config.step || '150', 10) || 150);
    let absoluteMax = parseInt(config.maxPrice || '0', 10) || 0;
    if (absoluteMax <= absoluteMin) {
      absoluteMax = absoluteMin + step;
    }

    const roundToStep = (value) => {
      const safe = Number.isFinite(value) ? value : absoluteMin;
      return Math.round(safe / step) * step;
    };

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const parseFieldValue = (value) => {
      const parsed = parseInt(String(value || '').replace(/[^\d-]/g, ''), 10);
      return Number.isFinite(parsed) ? parsed : absoluteMin;
    };

    const params = new URLSearchParams(window.location.search);
    const hasQueryMin = params.has('min_price');
    const hasQueryMax = params.has('max_price');
    const queryMin = parseFieldValue(params.get('min_price'));
    const queryMax = parseFieldValue(params.get('max_price'));

    let currentMin = clamp(hasQueryMin ? queryMin : absoluteMin, absoluteMin, absoluteMax);
    let currentMax = clamp(hasQueryMax ? queryMax : absoluteMax, absoluteMin, absoluteMax);
    if (currentMax < currentMin) {
      currentMax = currentMin;
    }

    minInput.min = String(absoluteMin);
    minInput.max = String(absoluteMax);
    minInput.step = '1';
    maxInput.min = String(absoluteMin);
    maxInput.max = String(absoluteMax);
    maxInput.step = '1';

    labels.innerHTML = '';
    for (let value = absoluteMin; value <= absoluteMax; value += step) {
      const tick = document.createElement('span');
      tick.textContent = String(value);
      labels.appendChild(tick);
    }

    const setThumbAria = () => {
      minThumb.setAttribute('role', 'slider');
      minThumb.setAttribute('aria-valuemin', String(absoluteMin));
      minThumb.setAttribute('aria-valuemax', String(currentMax));
      minThumb.setAttribute('aria-valuenow', String(currentMin));

      maxThumb.setAttribute('role', 'slider');
      maxThumb.setAttribute('aria-valuemin', String(currentMin));
      maxThumb.setAttribute('aria-valuemax', String(absoluteMax));
      maxThumb.setAttribute('aria-valuenow', String(currentMax));
    };

    const toPercent = (value) => ((value - absoluteMin) / (absoluteMax - absoluteMin)) * 100;

    const render = () => {
      minInput.value = String(currentMin);
      maxInput.value = String(currentMax);

      const minPercent = toPercent(currentMin);
      const maxPercent = toPercent(currentMax);
      minThumb.style.left = `${minPercent}%`;
      maxThumb.style.left = `${maxPercent}%`;
      trackFill.style.left = `${minPercent}%`;
      trackFill.style.width = `${Math.max(0, maxPercent - minPercent)}%`;
      setThumbAria();
    };

    const applyMin = (value, options = {}) => {
      const useSnap = !!options.snap;
      const nextValue = useSnap ? roundToStep(value) : value;
      currentMin = clamp(nextValue, absoluteMin, currentMax);
      if (currentMax < currentMin) currentMax = currentMin;
      render();
    };

    const applyMax = (value, options = {}) => {
      const useSnap = !!options.snap;
      const nextValue = useSnap ? roundToStep(value) : value;
      currentMax = clamp(nextValue, currentMin, absoluteMax);
      if (currentMin > currentMax) currentMin = currentMax;
      render();
    };

    let filterRequestController = null;
    const applyFilterResultsAjax = (nextUrl) => {
      if (filterRequestController) {
        filterRequestController.abort();
      }

      const controller = new AbortController();
      filterRequestController = controller;

      root.classList.add('is-loading');

      fetch(nextUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'text/html' },
        signal: controller.signal,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Failed to load filtered products');
          }
          return response.text();
        })
        .then((html) => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');

          const nextProducts = doc.querySelector('.noyona-shop-products');
          const currentProducts = document.querySelector('.noyona-shop-products');
          if (!nextProducts || !currentProducts) {
            throw new Error('Missing products container in AJAX response');
          }

          currentProducts.replaceWith(nextProducts);

          const nextCount = doc.querySelector('.noyona-shop-count');
          const currentCount = document.querySelector('.noyona-shop-count');
          if (nextCount && currentCount) {
            currentCount.replaceWith(nextCount);
          }

          const nextHeroCount = doc.querySelector('.noyona-search-hero__count');
          const currentHeroCount = document.querySelector('.noyona-search-hero__count');
          if (nextHeroCount && currentHeroCount) {
            currentHeroCount.replaceWith(nextHeroCount);
          }

          window.history.replaceState({}, '', nextUrl);

          const isListView = document.body.classList.contains('noyona-shop-view-list');
          nextProducts.setAttribute('data-shop-view', isListView ? 'list' : 'grid');

          initShopProductActions();
          initProductCardCart();
          initShopArchiveInfiniteScroll();
        })
        .catch((error) => {
          if (error && error.name === 'AbortError') return;
          window.location.assign(nextUrl);
        })
        .finally(() => {
          if (filterRequestController === controller) {
            filterRequestController = null;
          }
          root.classList.remove('is-loading');
        });
    };

    const applyUrlFilters = () => {
      const nextParams = new URLSearchParams(window.location.search);

      if (currentMin > absoluteMin) {
        nextParams.set('min_price', String(currentMin));
      } else {
        nextParams.delete('min_price');
      }

      if (currentMax < absoluteMax) {
        nextParams.set('max_price', String(currentMax));
      } else {
        nextParams.delete('max_price');
      }

      nextParams.delete('filtering');
      const activeViewBtn = document.querySelector('.noyona-shop-view-toggle button.is-active[data-shop-view]');
      const activeView = activeViewBtn ? String(activeViewBtn.dataset.shopView || '').toLowerCase() : '';
      const shouldKeepListView = activeView === 'list' || document.body.classList.contains('noyona-shop-view-list');
      if (shouldKeepListView) {
        nextParams.set('shop_view', 'list');
      } else {
        nextParams.delete('shop_view');
      }

      // Reset pagination whenever price range changes.
      const pageKeysToDelete = [];
      nextParams.forEach((_, key) => {
        if (/^query-\d+-page$/.test(key)) {
          pageKeysToDelete.push(key);
        }
      });
      pageKeysToDelete.forEach((key) => nextParams.delete(key));
      nextParams.delete('paged');
      nextParams.delete('search_page');

      const nextQuery = nextParams.toString();
      const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}`;
      const currentUrl = `${window.location.pathname}${window.location.search}`;
      if (nextUrl !== currentUrl) {
        applyFilterResultsAjax(nextUrl);
      }
    };

    const getValueFromPointer = (clientX) => {
      const rect = slider.getBoundingClientRect();
      if (rect.width <= 0) return currentMin;
      const ratio = clamp((clientX - rect.left) / rect.width, 0, 1);
      const rawValue = absoluteMin + ratio * (absoluteMax - absoluteMin);
      return roundToStep(rawValue);
    };

    const bindThumbDrag = (thumb, applyValue) => {
      let dragging = false;

      const onPointerMove = (event) => {
        if (!dragging) return;
        applyValue(getValueFromPointer(event.clientX), { snap: true });
      };

      const onPointerUp = () => {
        if (!dragging) return;
        dragging = false;
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        applyUrlFilters();
      };

      thumb.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        dragging = true;
        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', onPointerUp);
      });
    };

    bindThumbDrag(minThumb, applyMin);
    bindThumbDrag(maxThumb, applyMax);

    minInput.addEventListener('input', () => applyMin(parseFieldValue(minInput.value), { snap: false }));
    maxInput.addEventListener('input', () => applyMax(parseFieldValue(maxInput.value), { snap: false }));
    minInput.addEventListener('change', applyUrlFilters);
    maxInput.addEventListener('change', applyUrlFilters);

    const bindThumbKeys = (thumb, direction) => {
      thumb.addEventListener('keydown', (event) => {
        const key = event.key;
        if (key !== 'ArrowLeft' && key !== 'ArrowRight' && key !== 'Home' && key !== 'End') {
          return;
        }
        event.preventDefault();

        if (key === 'Home') {
          direction === 'min' ? applyMin(absoluteMin, { snap: true }) : applyMax(currentMin, { snap: true });
        } else if (key === 'End') {
          direction === 'min' ? applyMin(currentMax, { snap: true }) : applyMax(absoluteMax, { snap: true });
        } else if (key === 'ArrowLeft') {
          direction === 'min' ? applyMin(currentMin - step, { snap: true }) : applyMax(currentMax - step, { snap: true });
        } else if (key === 'ArrowRight') {
          direction === 'min' ? applyMin(currentMin + step, { snap: true }) : applyMax(currentMax + step, { snap: true });
        }
        applyUrlFilters();
      });
    };

    bindThumbKeys(minThumb, 'min');
    bindThumbKeys(maxThumb, 'max');
    render();
  }

  function getShopWishlistConfig() {
    const header = window.noyonaHeader || {};
    const wishlist = header.wishlist || {};
    const i18n = header.i18n || {};
    return {
      ajaxUrl: header.ajaxUrl ? String(header.ajaxUrl) : '/wp-admin/admin-ajax.php',
      nonce: wishlist.nonce ? String(wishlist.nonce) : '',
      loginUrl: wishlist.loginUrl ? String(wishlist.loginUrl) : (header.accountUrl ? String(header.accountUrl) : '/my-account/'),
      savedKeys: Array.isArray(wishlist.savedKeys) ? wishlist.savedKeys.map(String) : [],
      i18n,
    };
  }

  function getShopWishlistText(key, fallback) {
    const cfg = getShopWishlistConfig();
    return cfg.i18n && cfg.i18n[key] ? String(cfg.i18n[key]) : fallback;
  }

  function getShopCardProductId(card) {
    if (!card) return 0;

    const datasetId = parseInt(card.getAttribute('data-product-id') || '0', 10);
    if (datasetId > 0) return datasetId;

    const addButton = card.querySelector('[data-product_id], [data-product-id]');
    if (addButton) {
      const buttonId = parseInt(
        addButton.getAttribute('data-product_id') || addButton.getAttribute('data-product-id') || '0',
        10
      );
      if (buttonId > 0) return buttonId;
    }

    const addToCartInput = card.querySelector('[name="add-to-cart"]');
    if (addToCartInput) {
      const inputId = parseInt(addToCartInput.value || '0', 10);
      if (inputId > 0) return inputId;
    }

    const postClass = Array.from(card.classList).find((className) => /^post-\d+$/.test(className));
    if (postClass) {
      return parseInt(postClass.replace('post-', ''), 10) || 0;
    }

    return 0;
  }

  function getShopWishlistItemKey(productId, variationId = 0) {
    return String(productId) + ':' + String(variationId || 0);
  }

  function isShopWishlistItemSaved(productId, variationId = 0) {
    const key = getShopWishlistItemKey(productId, variationId);
    return getShopWishlistConfig().savedKeys.indexOf(key) !== -1;
  }

  function setShopWishlistSavedKeys(keys) {
    const header = window.noyonaHeader || {};
    if (!header.wishlist) {
      header.wishlist = {};
    }
    header.wishlist.savedKeys = keys;
    window.noyonaHeader = header;
  }

  function setShopWishlistButtonState(button, saved) {
    if (!button) return;

    const addLabel = getShopWishlistText('wishlistAdd', 'Add to wishlist');
    const removeLabel = getShopWishlistText('wishlistRemove', 'Remove from wishlist');
    const label = saved ? removeLabel : addLabel;

    button.classList.toggle('is-active', !!saved);
    button.setAttribute('aria-pressed', saved ? 'true' : 'false');
    button.setAttribute('aria-label', label);
  }

  function bindGlobalLoginModalOnce() {
    const modal = document.querySelector('[data-mini-cart-login-modal-global]');
    if (!modal || modal.dataset.noyonaGlobalLoginBound === '1') return;

    modal.dataset.noyonaGlobalLoginBound = '1';
    modal.querySelectorAll('[data-mini-cart-login-close]').forEach((button) => {
      button.addEventListener('click', () => {
        modal.hidden = true;
        document.documentElement.classList.remove('noyona-mini-cart-login-open');
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || modal.hidden) return;
      modal.hidden = true;
      document.documentElement.classList.remove('noyona-mini-cart-login-open');
    });
  }

  function showShopWishlistNotice(message) {
    const text = String(message || '').trim();
    if (!text) return;

    const root =
      document.querySelector('.noyona-product-gatherer') ||
      document.querySelector('main') ||
      document.body;

    if (typeof window.noyonaShowNotice === 'function') {
      window.noyonaShowNotice(text, {
        root,
        key: 'shop-wishlist',
        type: 'error',
      });
      return;
    }

    const existing = root.querySelector('[data-noyona-notice-key="shop-wishlist"]');
    if (existing) existing.remove();

    const notice = document.createElement('p');
    notice.className = 'noyona-notice is-error';
    notice.setAttribute('data-noyona-notice-key', 'shop-wishlist');
    notice.setAttribute('role', 'alert');
    notice.textContent = text;
    root.insertBefore(notice, root.firstChild);
  }

  function openShopWishlistLoginModal() {
    const cfg = getShopWishlistConfig();
    const modal = document.querySelector('[data-mini-cart-login-modal-global]');

    if (!modal) {
      window.location.href = cfg.loginUrl;
      return;
    }

    bindGlobalLoginModalOnce();

    const title = modal.querySelector('.noyona-mini-cart-login-title');
    const copy = modal.querySelector('.noyona-mini-cart-login-copy');
    const redirect = modal.querySelector('[data-mini-cart-login-redirect]');

    if (title) {
      title.textContent = getShopWishlistText('wishlistLoginTitle', 'Log in to save your wishlist');
    }
    if (copy) {
      copy.textContent = getShopWishlistText('wishlistLoginCopy', 'Please log in to save products and view them from My Account.');
    }
    if (redirect) {
      redirect.setAttribute('value', window.location.href);
    }

    modal.hidden = false;
    document.documentElement.classList.add('noyona-mini-cart-login-open');
  }

  function toggleShopWishlistItem(button, productId) {
    const cfg = getShopWishlistConfig();
    const itemKey = getShopWishlistItemKey(productId, 0);
    const payload = new URLSearchParams();

    payload.set('action', 'noyona_toggle_product_wishlist');
    payload.set('nonce', cfg.nonce);
    payload.set('product_id', String(productId));
    payload.set('variation_id', '0');

    button.disabled = true;

    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: payload.toString(),
    })
      .then((response) => response.json().catch(() => null).then((data) => ({ ok: response.ok, data })))
      .then((result) => {
        if (!result || !result.ok || !result.data || !result.data.success) {
          const message = result && result.data && result.data.data ? result.data.data.message : '';
          if (message === 'not_logged_in') {
            openShopWishlistLoginModal();
            return null;
          }
          showShopWishlistNotice(
            getShopWishlistText('wishlistError', 'Wishlist could not be updated. Please try again.')
          );
          return null;
        }

        const responseData = result.data.data || {};
        const saved = !!responseData.saved;
        const keys = cfg.savedKeys.slice();
        const resolvedKey = responseData.item_key ? String(responseData.item_key) : itemKey;
        const keyIndex = keys.indexOf(resolvedKey);

        if (saved && keyIndex === -1) {
          keys.push(resolvedKey);
        } else if (!saved && keyIndex !== -1) {
          keys.splice(keyIndex, 1);
        }

        setShopWishlistSavedKeys(keys);
        setShopWishlistButtonState(button, saved);
        return saved;
      })
      .catch(() => {
        showShopWishlistNotice(
          getShopWishlistText('wishlistError', 'Wishlist could not be updated. Please try again.')
        );
        return null;
      })
      .finally(() => {
        button.disabled = false;
      });
  }

  function bindShopWishlistButton(button, card) {
    if (!button || button.dataset.noyonaWishlistBound === '1') return;

    const productId = getShopCardProductId(card);
    if (productId < 1) return;

    button.dataset.productId = String(productId);
    button.dataset.noyonaWishlistBound = '1';
    setShopWishlistButtonState(button, isShopWishlistItemSaved(productId, 0));

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (!document.body.classList.contains('logged-in')) {
        openShopWishlistLoginModal();
        return;
      }

      if (button.disabled) return;
      toggleShopWishlistItem(button, productId);
    });
  }

  function initShopProductActions() {
    const cards = Array.from(document.querySelectorAll('.noyona-shop-products .wc-block-product'));
    if (!cards.length) return;

    const heartSvg = `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 21s-6.7-4.35-9.3-8.2C.7 9.9 2 6.4 5.1 5.3c2-.7 4.2-.1 5.6 1.5l1.3 1.4 1.3-1.4c1.4-1.6 3.6-2.2 5.6-1.5 3.1 1.1 4.4 4.6 2.4 7.5C18.7 16.65 12 21 12 21z"></path>
      </svg>
    `;

    const cartSvg = `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
      </svg>
    `;

    cards.forEach((card) => {
      if (card.dataset.noyonaActionsReady === '1') return;

      const nativeAdd = card.querySelector('.wp-block-button .wp-block-button__link, .add_to_cart_button');
      const nativeButtonWrap = card.querySelector('.wp-block-button');
      const hasPrimaryFooterCta = !!card.querySelector('.noyona-product-card-footer .noyona-buy-now-btn');

      if (nativeButtonWrap) {
        nativeButtonWrap.classList.add('noyona-shop-native-add-to-cart');
      }

      const actions = document.createElement('div');
      actions.className = 'noyona-shop-product-actions';

      const wishlistButton = document.createElement('button');
      wishlistButton.type = 'button';
      wishlistButton.className = 'noyona-shop-wishlist-btn';
      wishlistButton.setAttribute('aria-label', getShopWishlistText('wishlistAdd', 'Add to wishlist'));
      wishlistButton.setAttribute('aria-pressed', 'false');
      wishlistButton.innerHTML = heartSvg;

      actions.appendChild(wishlistButton);
      bindShopWishlistButton(wishlistButton, card);

      // Keep only wishlist/cart quick actions in product cards.
      if (!hasPrimaryFooterCta) {
        const footerActions = document.createElement('div');
        footerActions.className = 'noyona-shop-card-footer-actions';

        if (nativeAdd) {
          const cartButton = document.createElement('button');
          cartButton.type = 'button';
          cartButton.className = 'noyona-shop-add-cart-icon';
          cartButton.setAttribute('aria-label', 'Add to cart');
          cartButton.innerHTML = cartSvg;
          cartButton.addEventListener('click', (event) => {
            event.preventDefault();
            nativeAdd.click();
          });
          footerActions.appendChild(cartButton);
        }

        actions.appendChild(footerActions);
      }

      card.appendChild(actions);

      const cardLink = card.querySelector('.wp-block-post-title a, .wc-block-components-product-image a');
      if (cardLink && card.dataset.noyonaCardClickBound !== '1') {
        card.addEventListener('click', (event) => {
          const interactiveTarget = event.target.closest('a, button, input, select, textarea, label, summary, details');
          if (interactiveTarget) return;
          window.location.assign(cardLink.href);
        });
        card.dataset.noyonaCardClickBound = '1';
      }

      card.dataset.noyonaActionsReady = '1';
    });
  }

  function initShopArchiveInfiniteScroll() {
    const container = document.querySelector('.noyona-shop-products-infinite');
    if (!container) return;
    if (container.dataset.noyonaInfiniteReady === '1') return;

    const grid = container.querySelector('.wc-block-product-template');
    if (!grid) return;

    // Skip when the grid is showing the empty-state message.
    if (grid.querySelector('.noyona-shop-no-products')) {
      return;
    }

    // Skip when there are no real product cards.
    if (!grid.querySelector('.wc-block-product .wp-block-post-title, .wc-block-product .wc-block-components-product-image')) {
      return;
    }

    // The renderer emits a `.noyona-shop-infinite-meta` element with the
    // canonical "next page" URL. We prefer that over scraping WP's pagination
    // block (which can render inconsistently). The pagination scrape stays as
    // a defensive fallback.
    const findNextUrl = (scope) => {
      const root = scope || container;
      const meta = root.querySelector('.noyona-shop-infinite-meta');
      if (meta && meta.dataset && meta.dataset.nextUrl) {
        return meta.dataset.nextUrl;
      }
      const link = root.querySelector(
        '.wp-block-query-pagination a.wp-block-query-pagination-next, .wp-block-query-pagination a.next.page-numbers'
      );
      return link && link.href ? link.href : null;
    };

    const returnStateKey = 'noyonaShopReturnState:v1';
    const currentListingUrl = window.location.pathname + window.location.search;
    const readReturnState = () => {
      try {
        const raw = window.sessionStorage ? window.sessionStorage.getItem(returnStateKey) : '';
        return raw ? JSON.parse(raw) : null;
      } catch (error) {
        return null;
      }
    };
    const findCardByProductId = (productId) => {
      return Array.from(grid.querySelectorAll('.wc-block-product')).find(
        (card) => card.getAttribute('data-product-id') === String(productId)
      );
    };
    const writeReturnState = (card) => {
      if (!window.sessionStorage || !card) return;

      const productId = card.getAttribute('data-product-id') || '';
      const meta = container.querySelector('.noyona-shop-infinite-meta');
      const pagination = container.querySelector('.wp-block-query-pagination');
      const count = document.querySelector('.noyona-shop-count');

      try {
        window.sessionStorage.setItem(
          returnStateKey,
          JSON.stringify({
            url: currentListingUrl,
            productId: productId,
            scrollY: window.pageYOffset || 0,
            gridHtml: grid.innerHTML,
            metaHtml: meta ? meta.outerHTML : '',
            paginationHtml: pagination ? pagination.outerHTML : '',
            countText: count ? count.textContent : '',
            savedAt: Date.now(),
          })
        );
      } catch (error) {
        // Storage can fail in private mode or when the loaded grid is too large.
      }
    };
    const restoreReturnState = () => {
      const state = readReturnState();
      if (!state || state.url !== currentListingUrl || !state.gridHtml) return null;

      grid.innerHTML = state.gridHtml;

      const oldMeta = container.querySelector('.noyona-shop-infinite-meta');
      if (oldMeta) oldMeta.remove();
      if (state.metaHtml) {
        container.insertAdjacentHTML('beforeend', state.metaHtml);
      }

      const oldPagination = container.querySelector('.wp-block-query-pagination');
      if (oldPagination) oldPagination.remove();
      if (state.paginationHtml) {
        container.insertAdjacentHTML('beforeend', state.paginationHtml);
      }

      const count = document.querySelector('.noyona-shop-count');
      if (count && state.countText) {
        count.textContent = state.countText;
      }

      grid.querySelectorAll('.wc-block-product').forEach((card) => {
        card.removeAttribute('data-noyona-actions-ready');
        card.removeAttribute('data-noyona-card-click-bound');
        card.querySelectorAll('[data-noyona-wishlist-bound]').forEach((button) => {
          button.removeAttribute('data-noyona-wishlist-bound');
        });
        card.querySelectorAll('.noyona-product-card-cart[data-noyona-card-cart-bound="1"]').forEach((button) => {
          button.removeAttribute('data-noyona-card-cart-bound');
        });
        card.querySelectorAll('.noyona-shop-product-actions').forEach((actions) => actions.remove());
      });

      return state;
    };
    const restoredState = restoreReturnState();

    let nextUrl = findNextUrl();

    // If there is no next URL AND no pagination markup at all, we are showing
    // a complete result set on a single page. Skip wiring the loader entirely
    // so we don't render an "end" message under a one-page shop.
    const hasPaginationNav   = !!container.querySelector('.wp-block-query-pagination');
    const hasPaginationMeta  = !!container.querySelector('.noyona-shop-infinite-meta');
    if (!nextUrl && !hasPaginationNav && !hasPaginationMeta) {
      container.classList.add('is-infinite-active');
      container.dataset.noyonaInfiniteReady = '1';
      return;
    }

    // Keep the WC results-count text ("Showing 1-12 of 15 results") in sync
    // with the actual number of cards on screen as infinite scroll streams
    // them in. The total comes from our own meta element (authoritative);
    // the prefix/separator/suffix are parsed from whatever the initial text
    // looked like so we preserve any localized wording. If parsing fails we
    // fall back to a deterministic English rebuild instead of silently
    // doing nothing.
    const resultCountEl = document.querySelector('.noyona-shop-count');

    const getTotalProducts = () => {
      const meta = container.querySelector('.noyona-shop-infinite-meta');
      if (meta && meta.dataset && meta.dataset.total) {
        const fromMeta = parseInt(meta.dataset.total, 10);
        if (!isNaN(fromMeta) && fromMeta > 0) return fromMeta;
      }
      if (resultCountEl) {
        const m = (resultCountEl.textContent || '').match(/of\s+(\d+)/i);
        if (m) {
          const n = parseInt(m[1], 10);
          if (!isNaN(n) && n > 0) return n;
        }
      }
      return 0;
    };

    // Capture the original text once so each update can re-derive the
    // surrounding wording (we'd otherwise be parsing strings we just
    // rewrote).
    const initialCountText = resultCountEl ? (resultCountEl.textContent || '') : '';

    const updateResultCount = () => {
      if (!resultCountEl) return;
      const total = getTotalProducts();
      if (!total) return;
      const visibleCount = grid.querySelectorAll('.wc-block-product').length;
      const clamped      = Math.min(visibleCount, total);

      const match = initialCountText.match(/^(.*?)(\d+)(\s*[\u2013\u2014\-]\s*)(\d+)(\s*of\s*)(\d+)(.*)$/i);
      if (match) {
        const prefix    = match[1];
        const separator = match[3];
        const ofWord    = match[5];
        const suffix    = match[7];
        resultCountEl.textContent =
          prefix + '1' + separator + clamped + ofWord + total + suffix;
      } else {
        // Fallback when the original text didn't include a range (e.g.
        // "Showing 12 results" on a single-page result set, or a
        // localized variant we couldn't parse). Use a clean English
        // rebuild so the count always reflects reality.
        resultCountEl.textContent =
          'Showing 1\u2013' + clamped + ' of ' + total + ' results';
      }
    };

    const getShopStickyOffset = () => {
      const styles = getComputedStyle(document.documentElement);
      const rawOffset = styles.getPropertyValue('--noyona-header-total-offset');
      const offset = parseInt(rawOffset, 10);
      return (Number.isFinite(offset) ? offset : 0) + 16;
    };

    const scrollToNewCardsStart = (firstCard) => {
      if (!firstCard || typeof firstCard.getBoundingClientRect !== 'function') return;

      requestAnimationFrame(() => {
        const targetTop = firstCard.getBoundingClientRect().top + window.pageYOffset - getShopStickyOffset();
        window.scrollTo({ top: Math.max(0, targetTop), behavior: 'auto' });
      });
    };
    const scrollToRestoredProduct = () => {
      if (!restoredState || !restoredState.productId) return;

      requestAnimationFrame(() => {
        const card = findCardByProductId(restoredState.productId);
        const target = card || null;
        if (!target) {
          window.scrollTo({ top: restoredState.scrollY || 0, behavior: 'auto' });
          return;
        }

        const targetTop = target.getBoundingClientRect().top + window.pageYOffset - getShopStickyOffset();
        window.scrollTo({ top: Math.max(0, targetTop), behavior: 'auto' });
      });
    };

    container.classList.add('is-infinite-active');
    container.dataset.noyonaInfiniteReady = '1';

    const loader = document.createElement('div');
    loader.className = 'noyona-shop-infinite-loader';
    loader.setAttribute('aria-live', 'polite');
    loader.setAttribute('role', 'status');
    loader.hidden = true;
    container.appendChild(loader);

    // Separate always-rendered sentinel for the IntersectionObserver to watch.
    // The loader itself toggles `hidden` between fetches, and a hidden element
    // (display:none) has NO bounding box — IO never fires on it. Keeping
    // observation and visible messaging on separate elements avoids that.
    const sentinel = document.createElement('div');
    sentinel.className = 'noyona-shop-infinite-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    container.appendChild(sentinel);

    container.addEventListener(
      'click',
      (event) => {
        const card = event.target.closest('.wc-block-product');
        if (!card || !container.contains(card)) return;

        const productLink = card.querySelector('.wp-block-post-title a, .wc-block-components-product-image a');
        const clickedLink = event.target.closest('a');
        const clickedProductLink = clickedLink && productLink && clickedLink.href === productLink.href;
        const clickedCardSurface = !event.target.closest('button, input, select, textarea, label, summary, details');

        if (clickedProductLink || clickedCardSurface) {
          writeReturnState(card);
        }
      },
      true
    );

    const setLoaderState = (state, message) => {
      loader.dataset.state = state;
      if (state === 'idle' || state === 'hidden') {
        loader.hidden = true;
        loader.innerHTML = '';
        return;
      }
      loader.hidden = false;
      if (state === 'loading') {
        loader.innerHTML = '<span class="noyona-shop-infinite-spinner" aria-hidden="true"></span><span>' + (message || 'Loading more products...') + '</span>';
      } else if (state === 'end') {
        loader.innerHTML = '<span>' + (message || "You've reached the end") + '</span>';
      } else if (state === 'error') {
        loader.innerHTML = '<span>' + (message || 'Could not load more products.') + '</span><button type="button" class="noyona-shop-infinite-retry">Retry</button>';
        const retry = loader.querySelector('.noyona-shop-infinite-retry');
        if (retry) {
          retry.addEventListener('click', () => {
            setLoaderState('loading');
            loadNext();
          });
        }
      }
    };

    setLoaderState(nextUrl ? 'idle' : 'end');

    let isLoading = false;
    let abortController = null;

    const loadNext = () => {
      if (isLoading) return;
      if (!nextUrl) {
        setLoaderState('end');
        teardownObserver();
        return;
      }

      isLoading = true;
      setLoaderState('loading');

      if (abortController) abortController.abort();
      abortController = new AbortController();

      fetch(nextUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'text/html' },
        signal: abortController.signal,
      })
        .then((response) => {
          if (!response.ok) throw new Error('Request failed: ' + response.status);
          return response.text();
        })
        .then((html) => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');

          const nextContainer = doc.querySelector('.noyona-shop-products-infinite');
          const nextGrid = nextContainer && nextContainer.querySelector('.wc-block-product-template');
          if (!nextGrid) {
            throw new Error('Next page is missing the products grid.');
          }

          const newCards = Array.from(nextGrid.children).filter((node) =>
            node.classList && node.classList.contains('wc-block-product')
          );

          if (!newCards.length) {
            nextUrl = null;
            setLoaderState('end');
            teardownObserver();
            return;
          }

          const fragment = document.createDocumentFragment();
          const firstNewCard = newCards[0] || null;
          newCards.forEach((card) => fragment.appendChild(card));
          grid.appendChild(fragment);

          // Decorate newly appended cards with wishlist/cart actions.
          if (typeof initShopProductActions === 'function') {
            initShopProductActions();
          }
          if (typeof initProductCardCart === 'function') {
            initProductCardCart();
          }

          updateResultCount();
          scrollToNewCardsStart(firstNewCard);

          // Swap the in-DOM pagination meta (our authoritative next-URL source)
          // with the next page's so findNextUrl() picks up the following page.
          const oldMeta = container.querySelector('.noyona-shop-infinite-meta');
          const newMeta = nextContainer.querySelector('.noyona-shop-infinite-meta');
          if (oldMeta && newMeta) {
            oldMeta.replaceWith(newMeta);
          } else if (oldMeta && !newMeta) {
            oldMeta.remove();
          }

          // Also swap the WP paginator block, in case anything else relies on
          // it (e.g. no-JS users falling back through a cached page snapshot).
          const oldPagination = container.querySelector('.wp-block-query-pagination');
          const newPagination = nextContainer.querySelector('.wp-block-query-pagination');
          if (oldPagination && newPagination) {
            oldPagination.replaceWith(newPagination);
          } else if (oldPagination && !newPagination) {
            oldPagination.remove();
          }

          nextUrl = findNextUrl();
          if (!nextUrl) {
            setLoaderState('end');
            teardownObserver();
          } else {
            setLoaderState('idle');
          }
        })
        .catch((error) => {
          if (error && error.name === 'AbortError') return;
          setLoaderState('error');
        })
        .finally(() => {
          isLoading = false;
          abortController = null;
        });
    };

    let observer = null;
    let suppressAutoLoadUntil = restoredState ? Date.now() + 800 : 0;
    const teardownObserver = () => {
      if (observer) {
        observer.disconnect();
        observer = null;
      }
    };

    const shouldLoadFromSentinel = () => {
      const rect = sentinel.getBoundingClientRect();
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      const triggerLine = viewportHeight * 0.88;

      return Date.now() >= suppressAutoLoadUntil && rect.bottom >= 0 && rect.top <= triggerLine;
    };

    if ('IntersectionObserver' in window) {
      observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting && shouldLoadFromSentinel()) {
              loadNext();
            }
          });
        },
        { rootMargin: '0px 0px -12% 0px', threshold: 0 }
      );
      observer.observe(sentinel);
    } else {
      // Very old browsers: fallback to a throttled scroll listener.
      let scrollScheduled = false;
      const onScroll = () => {
        if (scrollScheduled) return;
        scrollScheduled = true;
        window.requestAnimationFrame(() => {
          scrollScheduled = false;
          if (shouldLoadFromSentinel()) {
            loadNext();
          }
        });
      };
      window.addEventListener('scroll', onScroll, { passive: true });
    }

    if (restoredState) {
      initShopProductActions();
      initProductCardCart();
      updateResultCount();
      scrollToRestoredProduct();
    }
  }

  function initScrollTopButton() {
    const button = document.querySelector('.noyona-scroll-top');
    if (!button) return;

    const toggleVisibility = () => {
      const shouldShow = (window.scrollY || 0) > 280;
      button.classList.toggle('is-visible', shouldShow);
    };

    button.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', toggleVisibility, { passive: true });
    toggleVisibility();
  }

  function initShopFilterModal() {
    const filterToggle = document.querySelector('.noyona-shop-filter-toggle');
    const filterClose = document.querySelector('.noyona-shop-filter-close');
    const filterOverlay = document.querySelector('.noyona-shop-filter-overlay');
    const filterPanel = document.querySelector('.noyona-shop-filters');
    if (!filterToggle || !filterPanel) return;

    const MOBILE_BREAKPOINT = 1399;

    const openFilter = () => {
      if (window.innerWidth > MOBILE_BREAKPOINT) return;
      document.body.classList.add('noyona-shop-filter-open');
      filterToggle.setAttribute('aria-expanded', 'true');
      filterPanel.setAttribute('aria-hidden', 'false');
    };

    const closeFilter = () => {
      document.body.classList.remove('noyona-shop-filter-open');
      filterToggle.setAttribute('aria-expanded', 'false');
      filterPanel.setAttribute('aria-hidden', 'true');
    };

    filterToggle.addEventListener('click', () => {
      const isOpen = document.body.classList.contains('noyona-shop-filter-open');
      if (isOpen) {
        closeFilter();
      } else {
        openFilter();
      }
    });

    if (filterClose) {
      filterClose.addEventListener('click', closeFilter);
    }

    if (filterOverlay) {
      filterOverlay.addEventListener('click', closeFilter);
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeFilter();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > MOBILE_BREAKPOINT) {
        closeFilter();
      }
    });

    closeFilter();
  }

  function initShopSort() {
    const selects = Array.from(document.querySelectorAll('.noyona-shop-sort-select'));
    if (!selects.length) return;

    const params = new URLSearchParams(window.location.search);
    const allowedValues = new Set(['menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc']);
    const requested = String(params.get('orderby') || 'menu_order').toLowerCase();
    const current = allowedValues.has(requested) ? requested : 'menu_order';

    const isPaginationKey = (key) =>
      key === 'paged' || key === 'product-page' || key === 'search_page' || /^query-\d+-page$/.test(key);

    const applySort = (value) => {
      const nextParams = new URLSearchParams(window.location.search);

      if (value && value !== 'menu_order') {
        nextParams.set('orderby', value);
      } else {
        nextParams.delete('orderby');
      }

      // Reset pagination so a new sort always starts on the first page.
      Array.from(nextParams.keys()).forEach((key) => {
        if (isPaginationKey(key)) {
          nextParams.delete(key);
        }
      });

      const query = nextParams.toString();
      window.location.assign(window.location.pathname + (query ? '?' + query : ''));
    };

    const closeSortDropdowns = (except) => {
      document.querySelectorAll('.noyona-shop-sort-dropdown[open]').forEach((dropdown) => {
        if (dropdown !== except) {
          dropdown.removeAttribute('open');
        }
      });
    };

    const closePriceDropdowns = () => {
      document.querySelectorAll('.noyona-shop-price-dropdown[open]').forEach((dropdown) => {
        dropdown.removeAttribute('open');
      });
    };

    const buildSortDropdown = (select) => {
      const dropdown = document.createElement('details');
      const summary = document.createElement('summary');
      const panel = document.createElement('div');
      const currentOption = select.options[select.selectedIndex] || select.options[0];

      dropdown.className = 'noyona-shop-sort-dropdown';
      summary.className = 'noyona-shop-sort-dropdown__summary';
      summary.textContent = currentOption ? currentOption.textContent : 'Default sorting';
      panel.className = 'noyona-shop-sort-dropdown__panel';

      Array.from(select.options).forEach((option) => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'noyona-shop-sort-dropdown__option';
        item.textContent = option.textContent;
        item.dataset.sortValue = String(option.value || 'menu_order');
        item.setAttribute('role', 'menuitemradio');
        item.setAttribute('aria-checked', option.value === select.value ? 'true' : 'false');

        if (option.value === select.value) {
          item.classList.add('is-active');
        }

        item.addEventListener('click', () => {
          const value = String(item.dataset.sortValue || 'menu_order').toLowerCase();
          dropdown.removeAttribute('open');
          if (value === select.value) {
            return;
          }
          select.value = value;
          applySort(value);
        });

        panel.appendChild(item);
      });

      dropdown.append(summary, panel);
      dropdown.addEventListener('toggle', () => {
        if (dropdown.open) {
          closeSortDropdowns(dropdown);
          closePriceDropdowns();
        }
      });

      select.classList.add('noyona-shop-sort-select--native');
      select.setAttribute('aria-hidden', 'true');
      select.tabIndex = -1;
      select.insertAdjacentElement('afterend', dropdown);
    };

    selects.forEach((select) => {
      select.value = current;
      if (!select.dataset.noyonaSortEnhanced) {
        select.dataset.noyonaSortEnhanced = '1';
        buildSortDropdown(select);
      }
      select.addEventListener('change', () => {
        const value = String(select.value || 'menu_order').toLowerCase();
        applySort(value);
      });
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('.noyona-shop-sort-dropdown')) {
        closeSortDropdowns(null);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeSortDropdowns(null);
      }
    });
  }

  function initAccountWishlistSort() {
    const forms = Array.from(document.querySelectorAll('.noyona-account-wishlist-sort'));
    if (!forms.length) return;

    const closeWishlistSortDropdowns = (except) => {
      document.querySelectorAll('.noyona-account-wishlist-sort .noyona-shop-sort-dropdown[open]').forEach((dropdown) => {
        if (dropdown !== except) {
          dropdown.removeAttribute('open');
        }
      });
    };

    const buildWishlistSortDropdown = (form, select) => {
      const dropdown = document.createElement('details');
      const summary = document.createElement('summary');
      const panel = document.createElement('div');
      const currentOption = select.options[select.selectedIndex] || select.options[0];

      dropdown.className = 'noyona-shop-sort-dropdown';
      summary.className = 'noyona-shop-sort-dropdown__summary';
      summary.textContent = currentOption ? currentOption.textContent : 'Default sorting';
      panel.className = 'noyona-shop-sort-dropdown__panel';

      Array.from(select.options).forEach((option) => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'noyona-shop-sort-dropdown__option';
        item.textContent = option.textContent;
        item.dataset.sortValue = String(option.value || 'default');
        item.setAttribute('role', 'menuitemradio');
        item.setAttribute('aria-checked', option.value === select.value ? 'true' : 'false');

        if (option.value === select.value) {
          item.classList.add('is-active');
        }

        item.addEventListener('click', () => {
          const value = String(item.dataset.sortValue || 'default');
          dropdown.removeAttribute('open');
          if (value === select.value) {
            return;
          }

          select.value = value;
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            form.submit();
          }
        });

        panel.appendChild(item);
      });

      dropdown.append(summary, panel);
      dropdown.addEventListener('toggle', () => {
        if (dropdown.open) {
          closeWishlistSortDropdowns(dropdown);
          document.querySelectorAll('.noyona-shop-sort-dropdown[open]').forEach((openDropdown) => {
            if (openDropdown !== dropdown && !form.contains(openDropdown)) {
              openDropdown.removeAttribute('open');
            }
          });
        }
      });

      select.classList.add('noyona-shop-sort-select--native');
      select.setAttribute('aria-hidden', 'true');
      select.tabIndex = -1;
      select.insertAdjacentElement('afterend', dropdown);
    };

    forms.forEach((form) => {
      const select = form.querySelector('select[name="wishlist_sort"]');
      if (!select || select.dataset.noyonaWishlistSortEnhanced === '1') return;

      select.dataset.noyonaWishlistSortEnhanced = '1';
      buildWishlistSortDropdown(form, select);
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('.noyona-account-wishlist-sort .noyona-shop-sort-dropdown')) {
        closeWishlistSortDropdowns(null);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeWishlistSortDropdowns(null);
      }
    });
  }

  function initShopPriceDropdown() {
    const dropdowns = Array.from(document.querySelectorAll('.noyona-shop-price-dropdown'));
    if (!dropdowns.length) return;

    const closeAll = (except) => {
      dropdowns.forEach((dropdown) => {
        if (dropdown !== except) {
          dropdown.removeAttribute('open');
        }
      });

      document.querySelectorAll('.noyona-shop-sort-dropdown[open]').forEach((dropdown) => {
        dropdown.removeAttribute('open');
      });
    };

    dropdowns.forEach((dropdown) => {
      dropdown.addEventListener('toggle', () => {
        if (dropdown.open) {
          closeAll(dropdown);
        }
      });
    });

    document.addEventListener('click', (event) => {
      const target = event.target;
      const clickedInside = dropdowns.some((dropdown) => dropdown.contains(target))
        || !!target.closest('.noyona-shop-sort-dropdown');
      if (!clickedInside) {
        closeAll(null);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeAll(null);
      }
    });
  }

  /**
   * Tablet band (641–767px): the header search renders as an icon only, and
   * tapping it opens the existing fixed search overlay — the same UI the search
   * block already uses at <=640px. The block's own toggle is gated to
   * matchMedia('(max-width: 640px)'), so this restores that behavior for the
   * 641–767px band WITHOUT modifying the search block. CSS for the overlay in
   * this band lives in assets/css/header.css.
   */
  function initTabletSearchOverlay() {
    const band = window.matchMedia('(min-width: 641px) and (max-width: 767px)');
    const form = document.querySelector('.search-inline-form');
    if (!form) return;
    const input = form.querySelector('.search-inline-input');
    const submit = form.querySelector('.search-inline-submit');
    if (!input || !submit) return;

    const collapse = () => form.classList.remove('is-mobile-expanded');

    submit.addEventListener('click', (evt) => {
      if (!band.matches) return;
      if (!form.classList.contains('is-mobile-expanded')) {
        // First tap: open the overlay instead of submitting an empty query.
        evt.preventDefault();
        form.classList.add('is-mobile-expanded');
        setTimeout(() => input.focus(), 0);
      }
      // When already expanded, let the normal submit proceed.
    });

    document.addEventListener('click', (evt) => {
      if (!band.matches) return;
      if (!form.contains(evt.target)) collapse();
    });

    input.addEventListener('keydown', (evt) => {
      if (evt.key === 'Escape' && band.matches) {
        collapse();
        submit.focus();
      }
    });

    const onBandChange = () => { if (!band.matches) collapse(); };
    if (typeof band.addEventListener === 'function') {
      band.addEventListener('change', onBandChange);
    } else if (typeof band.addListener === 'function') {
      band.addListener(onBandChange);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initBrandStripFallback();
    normalizeHeaderLogos();
    updateHeaderOffsets();
    toggleScrollState();
    window.addEventListener('scroll', toggleScrollState, { passive: true });
    window.addEventListener('resize', updateHeaderOffsets);

    initWishlist();
    initMiniCartCloseAction();
    initMiniCartDynamicUi();
    initAddToCartSuccessAnimation();
    initWishlistAddToCart();
    initHeaderCartFallback();
    initMiniCartAutoOpen();
    initAccountDropdown();
    initLogoutLinks();
    initMobileMenu();
    initTabletSearchOverlay();
    initMobileSubmenus();
    normalizeShopDropdownLinks();
    initActiveNavLinks();
    initShopToolbarControlsPlacement();
    initShopArchiveViewToggle();
    initShopMobileCategoriesPlacement();
    initShopBrandFilters();
    initShopTagFilters();
    initShopCategoryActiveByPath();
    initShopPriceFilter();
    initShopProductActions();
    initProductCardCart();
    initShopArchiveInfiniteScroll();
    initScrollTopButton();
    initShopFilterModal();
    initShopSort();
    initAccountWishlistSort();
    initShopPriceDropdown();
  });
})();
