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

    const readSubtotalFallback = (root) => {
      if (!root) return 0;
      const subtotalNode = root.querySelector('.wc-block-mini-cart__footer-subtotal');
      if (!subtotalNode) return 0;
      return parseCurrencyText(subtotalNode.textContent);
    };

    const syncCheckoutButton = (root) => {
      if (!root) return;

      const checkoutBtn = root.querySelector('.wp-block-woocommerce-mini-cart-checkout-button-block .wc-block-components-button');
      if (!checkoutBtn) return;
      checkoutBtn.innerHTML = '<span>Checkout</span><span class="noyona-mini-cart-checkout-arrow" aria-hidden="true">→</span>';
      checkoutBtn.dataset.noyonaLabeled = '1';
    };

    const syncTermsGate = (root) => {
      if (!root) return;

      const checkbox = root.querySelector('.noyona-mini-cart-terms-checkbox');
      const checkoutBtn = root.querySelector('.wp-block-woocommerce-mini-cart-checkout-button-block .wc-block-components-button');
      if (!checkbox || !checkoutBtn) return;

      const applyState = () => {
        const isChecked = !!checkbox.checked;
        checkoutBtn.classList.toggle('is-disabled', !isChecked);
        checkoutBtn.setAttribute('aria-disabled', isChecked ? 'false' : 'true');
      };

      if (!checkbox.dataset.noyonaBound) {
        checkbox.addEventListener('change', applyState);
        checkbox.dataset.noyonaBound = '1';
      }

      if (!checkoutBtn.dataset.noyonaGuardBound) {
        checkoutBtn.addEventListener('click', (event) => {
          if (!checkbox.checked) {
            event.preventDefault();
            event.stopPropagation();
          }
        });
        checkoutBtn.dataset.noyonaGuardBound = '1';
      }

      applyState();
    };

    const applySummaryFromSubtotal = (root, subtotal) => {
      if (!root) return;

      const shippingBar = root.querySelector('.noyona-mini-cart-shipping');
      const progressBar = root.querySelector('.noyona-mini-cart-progress-bar');
      const subtotalEl = root.querySelector('.noyona-mini-cart-subtotal');
      const shippingEl = root.querySelector('.noyona-mini-cart-shipping-fee');
      const totalEl = root.querySelector('.noyona-mini-cart-total');

      const threshold = shippingBar ? Number(shippingBar.dataset.freeShippingThreshold || 500) : 500;
      const defaultShippingFee = shippingBar ? Number(shippingBar.dataset.defaultShippingFee || 50) : 50;

      const safeThreshold = Number.isFinite(threshold) && threshold > 0 ? threshold : 500;
      const safeShippingFee = Number.isFinite(defaultShippingFee) && defaultShippingFee >= 0 ? defaultShippingFee : 50;
      const safeSubtotal = Math.max(0, Number(subtotal) || 0);

      const remaining = Math.max(0, safeThreshold - safeSubtotal);
      const shippingFee = safeSubtotal > 0 && safeSubtotal < safeThreshold ? safeShippingFee : 0;
      const total = safeSubtotal + shippingFee;
      const progress = safeThreshold > 0 ? Math.min(100, (safeSubtotal / safeThreshold) * 100) : 0;

      if (shippingBar) {
        if (safeSubtotal <= 0 || remaining > 0) {
          shippingBar.textContent = 'Add ' + formatPeso(remaining > 0 ? remaining : safeThreshold) + ' for Free Shipping';
        } else {
          shippingBar.textContent = 'You unlocked Free Shipping';
        }
      }

      if (progressBar) {
        progressBar.style.width = progress.toFixed(2) + '%';
      }

      if (subtotalEl) subtotalEl.textContent = formatPeso(safeSubtotal);
      if (shippingEl) shippingEl.textContent = formatPeso(shippingFee);
      if (totalEl) totalEl.textContent = formatPeso(total);
    };

    const fetchStoreCart = () => fetch('/wp-json/wc/store/cart', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    }).then((res) => (res.ok ? res.json() : null)).catch(() => null);

    const refreshMiniCart = () => {
      const root = getMiniCartRoot();
      if (!root) return;

      syncProductTitlePriceRows(root);
      syncCheckoutButton(root);
      syncTermsGate(root);

      fetchStoreCart().then((cart) => {
        const totals = cart && cart.totals ? cart.totals : null;
        const subtotalMinor = totals
          ? (parseMinorAmount(totals.total_items) || parseMinorAmount(totals.subtotal_price) || parseMinorAmount(totals.total_price))
          : 0;

        if (subtotalMinor > 0) {
          applySummaryFromSubtotal(root, subtotalMinor / 100);
          return;
        }

        applySummaryFromSubtotal(root, readSubtotalFallback(root));
      });
    };

    let refreshTimer = null;
    const queueRefresh = (delay = 180) => {
      clearTimeout(refreshTimer);
      refreshTimer = setTimeout(refreshMiniCart, delay);
    };

    document.addEventListener('click', (e) => {
      if (
        e.target.closest('.wc-block-mini-cart__button') ||
        e.target.closest('.add_to_cart_button') ||
        e.target.closest('.wc-block-components-quantity-selector__button') ||
        e.target.closest('.wc-block-cart-item__remove-link')
      ) {
        queueRefresh(250);
      }
    });

    if (window.jQuery) {
      window.jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div wc_fragments_refreshed', () => {
        queueRefresh(180);
      });
    }

    const observer = new MutationObserver(() => queueRefresh(100));
    observer.observe(document.body, { childList: true, subtree: true });

    queueRefresh(0);
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

    const applyView = (view) => {
      const isList = view === 'list';
      document.body.classList.toggle('noyona-shop-view-list', isList);
      document.body.classList.toggle('noyona-shop-view-grid', !isList);
      productCollection.setAttribute('data-shop-view', isList ? 'list' : 'grid');

      buttons.forEach((button) => {
        const active = button.dataset.shopView === (isList ? 'list' : 'grid');
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
    };

    const initial = document.body.classList.contains('noyona-shop-view-list') ? 'list' : 'grid';
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
      const nextQuery = nextParams.toString();
      const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}`;
      const currentUrl = `${window.location.pathname}${window.location.search}`;
      if (nextUrl !== currentUrl) {
        window.location.assign(nextUrl);
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

      const footerActions = document.createElement('div');
      footerActions.className = 'noyona-shop-card-footer-actions';

      const cartButton = document.createElement('button');
      cartButton.type = 'button';
      cartButton.className = 'noyona-shop-add-cart-icon';
      cartButton.setAttribute('aria-label', 'Add to cart');
      cartButton.innerHTML = cartSvg;
      cartButton.addEventListener('click', (event) => {
        event.preventDefault();
        if (nativeAdd) {
          nativeAdd.click();
        }
      });

      const buyNowLink = document.createElement('a');
      buyNowLink.className = 'noyona-shop-buy-now';
      buyNowLink.href = productUrl;
      buyNowLink.textContent = 'Buy Now';
      buyNowLink.setAttribute('aria-label', 'Go to product details');

      footerActions.appendChild(cartButton);
      footerActions.appendChild(buyNowLink);
      actions.appendChild(wishlistButton);
      actions.appendChild(footerActions);
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
