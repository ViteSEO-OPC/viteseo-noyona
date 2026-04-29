console.log('🔥 HEADER JS LOADED — build', Date.now());

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

    const isShadeKey = (key) => /(?:^|[\s_-])(color|colour|shade|swatch|tone|tint)(?:$|[\s_-])/i.test(String(key || '').trim());

    const extractShadeFromCartItem = (item) => {
      if (!item || typeof item !== 'object') return '';

      if (Array.isArray(item.item_data)) {
        for (const detail of item.item_data) {
          const key = detail && (detail.key || detail.name || detail.label) ? String(detail.key || detail.name || detail.label) : '';
          const val = detail && (detail.value || detail.display_value || detail.display || detail.raw) ? String(detail.value || detail.display_value || detail.display || detail.raw) : '';
          if (isShadeKey(key) && stripToText(val)) return stripToText(val);
        }
      }

      if (Array.isArray(item.variation)) {
        for (const detail of item.variation) {
          const key = detail && (detail.attribute || detail.name || detail.key || detail.label) ? String(detail.attribute || detail.name || detail.key || detail.label) : '';
          const val = detail && (detail.value || detail.display_value || detail.display) ? String(detail.value || detail.display_value || detail.display) : '';
          if (isShadeKey(key) && stripToText(val)) return stripToText(val);
        }
      } else if (item.variation && typeof item.variation === 'object') {
        for (const [key, val] of Object.entries(item.variation)) {
          if (isShadeKey(key) && stripToText(val)) return stripToText(val);
        }
      }

      return '';
    };

    const extractShadeFromDomDetails = (wrap) => {
      if (!wrap) return '';
      const detailNames = wrap.querySelectorAll('.wc-block-components-product-details__name');
      for (const nameEl of detailNames) {
        const key = stripToText(nameEl.textContent);
        if (!isShadeKey(key)) continue;
        let valueEl = null;
        if (nameEl.parentElement) {
          valueEl = nameEl.parentElement.querySelector('.wc-block-components-product-details__value');
        }
        if (!valueEl) {
          const next = nameEl.nextElementSibling;
          if (next) valueEl = next;
        }
        const val = stripToText(valueEl ? valueEl.textContent : '');
        if (val) return val;
      }
      return '';
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

    const syncProductTitlePriceRows = (root) => {
      if (!root) return;

      const rows = root.querySelectorAll('.wc-block-cart-items__row, .wc-block-mini-cart-items__row');
      rows.forEach((row) => {
        const wrap = row.querySelector('.wc-block-cart-item__wrap');
        if (!wrap) return;

        const nameAnchor = wrap.querySelector('a.wc-block-components-product-name');
        const nameFallback = wrap.querySelector('.wc-block-components-product-name');
        const nameEl = nameAnchor || nameFallback;
        if (!nameEl) return;

        const discountedPrice = wrap.querySelector('.wc-block-components-product-price__value.is-discounted');
        const fallbackPrice = wrap.querySelector('.wc-block-components-product-price__value');
        const priceValue = discountedPrice || fallbackPrice;
        const priceEl = priceValue
          ? priceValue.closest('.wc-block-components-product-price') || priceValue
          : null;
        if (!priceEl) return;

        const existingRow = wrap.querySelector('.noyona-mini-cart-title-price-row');
        if (existingRow) {
          if (!existingRow.contains(nameEl)) existingRow.prepend(nameEl);
          if (!existingRow.contains(priceEl)) existingRow.append(priceEl);
          return;
        }

        const titlePriceRow = document.createElement('div');
        titlePriceRow.className = 'noyona-mini-cart-title-price-row';
        nameEl.parentNode.insertBefore(titlePriceRow, nameEl);
        titlePriceRow.appendChild(nameEl);
        titlePriceRow.appendChild(priceEl);
      });
    };

    const syncSelectedShadeRows = (root, cart) => {
      if (!root) return;
      const items = cart && Array.isArray(cart.items) ? cart.items : [];
      const rows = root.querySelectorAll('.wc-block-cart-items__row, .wc-block-mini-cart-items__row');

      rows.forEach((row, index) => {
        const wrap = row.querySelector('.wc-block-cart-item__wrap');
        if (!wrap) return;

        const existing = wrap.querySelector('.noyona-mini-cart-selected-shade');
        const fromCart = extractShadeFromCartItem(items[index] || null);
        const fromDom = extractShadeFromDomDetails(wrap);
        const shadeValue = fromCart || fromDom;

        if (!shadeValue) {
          if (existing) existing.remove();
          return;
        }

        const shadeText = 'Shade: ' + shadeValue;
        if (existing) {
          existing.textContent = shadeText;
          return;
        }

        const rowEl = document.createElement('div');
        rowEl.className = 'noyona-mini-cart-selected-shade';
        rowEl.textContent = shadeText;

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

        // Terms gate.
        const termsBox = root.querySelector('.noyona-mini-cart-terms-checkbox');
        if (termsBox && !termsBox.checked) {
          event.preventDefault();
          event.stopPropagation();
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

    const applySummaryFromSubtotal = (root, subtotal) => {
      if (!root) return;

      const shippingBar = root.querySelector('.noyona-mini-cart-shipping');
      const subtotalEl = root.querySelector('.noyona-mini-cart-subtotal');
      const shippingEl = root.querySelector('.noyona-mini-cart-shipping-fee');
      const totalEl = root.querySelector('.noyona-mini-cart-total');

      const safeSubtotal = Math.max(0, Number(subtotal) || 0);

      // Mini-cart cannot know real shipping (no destination yet) — show neutral copy
      // and let WooCommerce compute the actual J&T rate at checkout.
      if (shippingBar) {
        shippingBar.textContent = 'Shipping calculated at checkout';
      }

      if (subtotalEl) subtotalEl.textContent = formatPeso(safeSubtotal);
      if (shippingEl) shippingEl.textContent = '—';
      if (totalEl) totalEl.textContent = formatPeso(safeSubtotal);
    };

    const fetchStoreCart = () => fetch('/wp-json/wc/store/cart?_t=' + Date.now(), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    }).then((res) => (res.ok ? res.json() : null)).catch(() => null);

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

    const ensureHeaderCartBadge = () => {
      const controls = document.querySelectorAll(
        '.header-mini-cart .wc-block-mini-cart__button, .header-icons .header-cart-fallback'
      );
      controls.forEach((control) => {
        const nativeBadge = control.querySelector('.wc-block-mini-cart__badge, .wc-block-mini-cart__button-badge');
        let customBadge = control.querySelector('.noyona-cart-count-badge');

        // If Woo renders its own badge, keep that one and remove custom clone.
        if (nativeBadge) {
          if (customBadge) {
            customBadge.remove();
          }
          return;
        }

        if (!customBadge) {
          customBadge = document.createElement('span');
          customBadge.className = 'noyona-cart-count-badge is-hidden';
          customBadge.textContent = '0';
          customBadge.setAttribute('aria-hidden', 'true');
          control.appendChild(customBadge);
        }
      });
    };

    const syncHeaderCartBadge = (count) => {
      const safeCount = Math.max(0, parseInt(count, 10) || 0);
      ensureHeaderCartBadge();

      document
        .querySelectorAll(
          '.noyona-cart-count-badge, .wc-block-mini-cart__badge, .wc-block-mini-cart__button-badge'
        )
        .forEach((badge) => {
          badge.textContent = String(safeCount);
          badge.classList.toggle('is-hidden', safeCount < 1);
          badge.setAttribute('aria-hidden', safeCount < 1 ? 'true' : 'false');
        });

      document.querySelectorAll('.header-mini-cart .wc-block-mini-cart__button').forEach((button) => {
        button.classList.toggle('wc-block-mini-cart__button--empty', safeCount < 1);
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
      fetchStoreCart().then((cart) => {
        syncHeaderCartBadge(readCartItemCount(cart));
        syncWooBlocksCartStore(cart);

        const root = getMiniCartRoot();
        if (!root) return;

        syncProductTitlePriceRows(root);
        syncSelectedShadeRows(root, cart);
        reconcileMiniCartRows(root, cart);
        syncCheckoutButton(root);
        syncTermsGate(root);
        bindMiniCartLoginModal();

        // Fallback: full cart totals from Store API.
        const totals = cart && cart.totals ? cart.totals : null;
        const subtotalMinor = totals
          ? (parseMinorAmount(totals.total_items) || parseMinorAmount(totals.subtotal_price) || parseMinorAmount(totals.total_price))
          : 0;

        applySummaryFromSubtotal(root, subtotalMinor > 0 ? subtotalMinor / 100 : readSubtotalFallback(root));
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
      window.jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div wc_fragments_refreshed', () => {
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
    const openMiniCart = () => {
      const btn = document.querySelector('.wc-block-mini-cart__button');
      if (btn) {
        btn.click();
        return true;
      }
      return false;
    };

    // Auto-open after /cart → /?open-cart=1 redirect.
    const params = new URLSearchParams(window.location.search);
    if (params.has('open-cart')) {
      params.delete('open-cart');
      const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
      window.history.replaceState({}, '', clean);

      const tryOpen = (attempts) => {
        if (openMiniCart() || attempts <= 0) return;
        setTimeout(() => tryOpen(attempts - 1), 250);
      };
      // Give WC blocks time to mount.
      setTimeout(() => tryOpen(12), 400);
    }

    // Handle #open-cart links anywhere on the page.
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[href="#open-cart"]');
      if (!link) return;
      e.preventDefault();
      openMiniCart();
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
    $$('.submenu-toggle').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const navItem = btn.closest('.nav-item');
        const icon = btn.querySelector('i');
        if (!navItem) return;

        const isOpen = navItem.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (icon) {
          icon.classList.toggle('fa-plus', !isOpen);
          icon.classList.toggle('fa-minus', isOpen);
        }
      });
    });
  }

  function initActiveNavLinks() {
    const currentPath = window.location.pathname;
    const navLinks = $$('.nav-link, .dropdown-menu a, .mobile-drawer-links a');

    navLinks.forEach((link) => {
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

    const buttons = Array.from(toggle.querySelectorAll('button[data-shop-view]'));
    if (!buttons.length) return;
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

      buttons.forEach((button) => {
        const active = button.dataset.shopView === (isList ? 'list' : 'grid');
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
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

    buttons.forEach((button) => {
      button.addEventListener('click', () => applyView(button.dataset.shopView || 'grid'));
    });
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

    const minInput = root.querySelector('input[name="noyona-price-min"]');
    const maxInput = root.querySelector('input[name="noyona-price-max"]');
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

          window.history.replaceState({}, '', nextUrl);

          const isListView = document.body.classList.contains('noyona-shop-view-list');
          nextProducts.setAttribute('data-shop-view', isListView ? 'list' : 'grid');

          initShopProductActions();
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
      const titleLink = card.querySelector('.wp-block-post-title a');
      const imageLink = card.querySelector('.wc-block-components-product-image a');
      const productUrl = (titleLink && titleLink.href) || (imageLink && imageLink.href) || '#';

      if (nativeButtonWrap) {
        nativeButtonWrap.classList.add('noyona-shop-native-add-to-cart');
      }

      const actions = document.createElement('div');
      actions.className = 'noyona-shop-product-actions';

      const wishlistButton = document.createElement('button');
      wishlistButton.type = 'button';
      wishlistButton.className = 'noyona-shop-wishlist-btn';
      wishlistButton.setAttribute('aria-label', 'Add to wishlist');
      wishlistButton.setAttribute('aria-pressed', 'false');
      wishlistButton.innerHTML = heartSvg;
      wishlistButton.addEventListener('click', () => {
        const isOn = wishlistButton.getAttribute('aria-pressed') === 'true';
        wishlistButton.setAttribute('aria-pressed', isOn ? 'false' : 'true');
      });

      actions.appendChild(wishlistButton);

      // Cards rendered with the Noyona footer already include the primary Buy Now CTA.
      // Keep only wishlist on those cards to avoid duplicate actions in list view.
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

        const buyNowLink = document.createElement('a');
        buyNowLink.className = 'noyona-shop-buy-now';
        buyNowLink.href = productUrl;
        buyNowLink.textContent = 'Buy Now';
        buyNowLink.setAttribute('aria-label', 'Go to product details');
        footerActions.appendChild(buyNowLink);

        actions.appendChild(footerActions);
      }

      card.appendChild(actions);

      card.dataset.noyonaActionsReady = '1';
    });
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

    const MOBILE_BREAKPOINT = 999;

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
    initHeaderCartFallback();
    initMiniCartAutoOpen();
    initAccountDropdown();
    initLogoutLinks();
    initMobileMenu();
    initMobileSubmenus();
    normalizeShopDropdownLinks();
    initActiveNavLinks();
    initShopArchiveViewToggle();
    initShopCategoryActiveByPath();
    initShopPriceFilter();
    initShopProductActions();
    initScrollTopButton();
    initShopFilterModal();
  });
})();
