/**
 * PDP: color swatches (hex term slugs), Buy now, content tabs.
 */
(function () {
  'use strict';

  function hexToCssColor(slug) {
    if (!slug || typeof slug !== 'string') {
      return '';
    }

    var s = slug.trim().replace(/^#/, '');

    if (/^[0-9a-fA-F]{6}$/.test(s)) {
      return '#' + s.toLowerCase();
    }

    if (/^[0-9a-fA-F]{3}$/.test(s)) {
      return (
        '#' +
        s[0].toLowerCase() +
        s[0].toLowerCase() +
        s[1].toLowerCase() +
        s[1].toLowerCase() +
        s[2].toLowerCase() +
        s[2].toLowerCase()
      );
    }

    var embedded = s.match(/(?:^|[-_])([0-9a-fA-F]{6})(?:$|[-_])/);
    if (embedded) {
      return '#' + embedded[1].toLowerCase();
    }

    return '';
  }

  function initTabs(root) {
    var wrap = root || document;
    var tabsets = wrap.querySelectorAll('[data-noyona-pdp-tabs]');

    tabsets.forEach(function (set) {
      var tabs = set.querySelectorAll('.noyona-pdp-tabs__tab');
      var panels = set.querySelectorAll('.noyona-pdp-tabs__panel');

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var id = tab.getAttribute('data-noyona-tab');
          if (!id) {
            return;
          }

          tabs.forEach(function (t) {
            var on = t.getAttribute('data-noyona-tab') === id;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
          });

          panels.forEach(function (p) {
            var on = p.getAttribute('data-noyona-panel') === id;
            p.classList.toggle('is-active', on);
            if (on) {
              p.removeAttribute('hidden');
            } else {
              p.setAttribute('hidden', '');
            }
          });
        });
      });
    });
  }

  function isColorAttributeSelect(select) {
    var name = (select.getAttribute('name') || '').toLowerCase();

    if (name === 'attribute_color' || name === 'attribute_colour') {
      return true;
    }

    if (name.indexOf('attribute_pa_color') === 0 || name.indexOf('attribute_pa_colour') === 0) {
      return true;
    }

    return false;
  }

  function shouldUseSwatchUi(select) {
    if (isColorAttributeSelect(select)) {
      return true;
    }

    var name = (select.getAttribute('name') || '').toLowerCase();

    if (/^attribute_(pa_)?(shade|swatch|tone|tint)$/.test(name)) {
      return true;
    }

    var opts = Array.prototype.filter.call(select.options, function (o) {
      return o.value;
    });

    if (opts.length < 2) {
      return false;
    }

    return opts.every(function (o) {
      return hexToCssColor(o.value) !== '';
    });
  }

  function buildSwatchRow(select) {
    if (select.closest('.noyona-pdp-variation__shade-box')) {
      return;
    }

    if (!shouldUseSwatchUi(select)) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'noyona-pdp-variation__shade-box';

    var header = document.createElement('div');
    header.className = 'noyona-pdp-variation__shade-head';

    var label = document.createElement('span');
    label.className = 'noyona-pdp-variation__shade-label';
    label.textContent =
      typeof window.noyonaPdp !== 'undefined' &&
      window.noyonaPdp.i18n &&
      window.noyonaPdp.i18n.selectShade
        ? window.noyonaPdp.i18n.selectShade
        : 'Select shade';

    var current = document.createElement('span');
    current.className = 'noyona-pdp-variation__shade-current';
    current.setAttribute('data-noyona-shade-name', '');

    header.appendChild(label);
    header.appendChild(current);

    var row = document.createElement('div');
    row.className = 'noyona-pdp-variation__swatches';
    row.setAttribute('role', 'list');

    function updateCurrentLabel() {
      var opt = select.options[select.selectedIndex];
      current.textContent = opt && opt.text ? opt.text.trim() : '';
    }

    var options = Array.prototype.slice.call(select.options, 0);

    options.forEach(function (opt, index) {
      if (!opt.value) {
        return;
      }

      var hex = hexToCssColor(opt.value);
      var btn = document.createElement('button');

      btn.type = 'button';
      btn.className = 'noyona-pdp-swatch';
      btn.setAttribute('role', 'listitem');
      btn.setAttribute('aria-label', opt.text || opt.value);
      btn.setAttribute('data-value', opt.value);
      btn.setAttribute('data-index', String(index));

      if (hex) {
        btn.style.setProperty('--noyona-swatch', hex);
      } else {
        btn.classList.add('noyona-pdp-swatch--fallback');
      }

      btn.addEventListener('click', function () {
        select.selectedIndex = index;
        // Keep our custom active UI in sync.
        select.dispatchEvent(new Event('change', { bubbles: true }));
        // Keep Woo variation matching/image switching in sync.
        if (typeof window.jQuery !== 'undefined') {
          window.jQuery(select).trigger('change');
        }
      });

      row.appendChild(btn);
    });

    select.classList.add('noyona-pdp-variation__select-hidden');
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(header);
    wrap.appendChild(row);
    wrap.appendChild(select);

    function syncFromSelect() {
      updateCurrentLabel();
      var selectedValue = '';
      if (select.selectedIndex > -1 && select.options[select.selectedIndex]) {
        selectedValue = select.options[select.selectedIndex].value || '';
      }

      row.querySelectorAll('.noyona-pdp-swatch').forEach(function (button) {
        var value = button.getAttribute('data-value') || '';
        var idx = parseInt(button.getAttribute('data-index') || '-1', 10);
        var disabled = false;
        if (idx > -1 && select.options[idx]) {
          disabled = !!select.options[idx].disabled;
        }
        button.classList.toggle('is-selected', selectedValue !== '' && value === selectedValue);
        button.disabled = disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
      });
    }

    select.addEventListener('change', syncFromSelect);
    syncFromSelect();
  }

  function initSwatches(form) {
    if (!form) {
      return;
    }

    var selects = form.querySelectorAll('select[name^="attribute_"]');
    selects.forEach(buildSwatchRow);
  }

  function bindWooCommercePdpHooks() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.on) {
      return;
    }

    var $ = window.jQuery;

    $(document.body).on('wc_variation_form', 'form.variations_form', function () {
      initSwatches(this);
    });

    $(document.body).on(
      'woocommerce_update_variation_values woocommerce_variation_has_changed reset_data found_variation',
      'form.variations_form',
      function () {
        initSwatches(this);
      }
    );
  }

  function bindVariationPriceSync(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return;
    }
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var mainPrice = document.querySelector(
      '.single-product .wp-block-woocommerce-product-price .wc-block-components-product-price'
    );
    if (!mainPrice) {
      return;
    }

    if (!mainPrice.getAttribute('data-original-price-html')) {
      mainPrice.setAttribute('data-original-price-html', mainPrice.innerHTML);
    }

    var $form = window.jQuery(form);
    if ($form.data('noyonaPriceSyncBound')) {
      return;
    }
    $form.data('noyonaPriceSyncBound', true);

    $form.on('found_variation show_variation', function (event, variation) {
      if (variation && variation.price_html) {
        mainPrice.innerHTML = variation.price_html;
      }
    });

    $form.on('reset_data hide_variation', function () {
      var original = mainPrice.getAttribute('data-original-price-html');
      if (original !== null) {
        mainPrice.innerHTML = original;
      }
    });
  }

  function getAddToCartEndpoint() {
    if (
      typeof window.wc_add_to_cart_params !== 'undefined' &&
      window.wc_add_to_cart_params &&
      window.wc_add_to_cart_params.wc_ajax_url
    ) {
      return String(window.wc_add_to_cart_params.wc_ajax_url).replace('%%endpoint%%', 'add_to_cart');
    }
    return '/?wc-ajax=add_to_cart';
  }

  function maybeOpenMiniCartDrawer() {
    var miniCartBtn = document.querySelector('.wc-block-mini-cart__button');
    if (miniCartBtn) {
      miniCartBtn.click();
    }
  }

  function getGlobalCheckoutLoginModal() {
    return document.querySelector('[data-mini-cart-login-modal-global]');
  }

  function closeGlobalCheckoutLoginModal() {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal) {
      return;
    }
    modal.hidden = true;
    document.documentElement.classList.remove('noyona-mini-cart-login-open');
  }

  function setGlobalCheckoutLoginRedirect(redirectUrl) {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal || !redirectUrl) {
      return;
    }

    var hiddenRedirect = modal.querySelector('[data-mini-cart-login-redirect]');
    if (hiddenRedirect) {
      hiddenRedirect.setAttribute('value', String(redirectUrl));
    }

    var loginLink = modal.querySelector('[data-mini-cart-login-action]');
    if (loginLink) {
      var href = loginLink.getAttribute('href') || '/wp-login.php';
      try {
        var url = new URL(href, window.location.origin);
        url.searchParams.set('redirect_to', String(redirectUrl));
        loginLink.setAttribute('href', url.toString());
      } catch (e) {
        // Keep existing URL when parsing fails.
      }
    }
  }

  function bindGlobalCheckoutLoginModal() {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal || modal.dataset.noyonaBound === '1') {
      return;
    }
    modal.dataset.noyonaBound = '1';

    var closeButtons = modal.querySelectorAll('[data-mini-cart-login-close]');
    closeButtons.forEach(function (button) {
      button.addEventListener('click', closeGlobalCheckoutLoginModal);
    });

    if (document.documentElement.getAttribute('data-noyona-global-login-esc-bound') !== '1') {
      document.documentElement.setAttribute('data-noyona-global-login-esc-bound', '1');
      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
          return;
        }
        closeGlobalCheckoutLoginModal();
      });
    }
  }

  function openGlobalCheckoutLoginModal(redirectUrl, modalCopy) {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal) {
      window.location.href = '/my-account/';
      return;
    }

    bindGlobalCheckoutLoginModal();
    setGlobalCheckoutLoginRedirect(redirectUrl || window.location.href);

    if (modalCopy && typeof modalCopy === 'object') {
      var title = modal.querySelector('.noyona-mini-cart-login-title');
      var copy = modal.querySelector('.noyona-mini-cart-login-copy');
      if (title && modalCopy.title) {
        title.textContent = String(modalCopy.title);
      }
      if (copy && modalCopy.copy) {
        copy.textContent = String(modalCopy.copy);
      }
    }

    modal.hidden = false;
    document.documentElement.classList.add('noyona-mini-cart-login-open');
  }

  function invalidateWooBlocksCartStore() {
    if (!window.wp || !window.wp.data || typeof window.wp.data.dispatch !== 'function') {
      return;
    }

    var stores = ['wc/store/cart', 'wc/store/cart-data', 'wc/store'];
    stores.forEach(function (storeKey) {
      var dispatch = null;
      try {
        dispatch = window.wp.data.dispatch(storeKey);
      } catch (e) {
        dispatch = null;
      }
      if (!dispatch) {
        return;
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
  }

  function broadcastCartAdded(detail) {
    var payload = Object.assign(
      {
        preserveCartData: false,
      },
      detail || {}
    );

    document.body.dispatchEvent(
      new CustomEvent('wc-blocks_added_to_cart', {
        bubbles: true,
        detail: payload,
      })
    );
    document.dispatchEvent(
      new CustomEvent('wc-blocks_added_to_cart', {
        bubbles: true,
        detail: payload,
      })
    );
    window.dispatchEvent(
      new CustomEvent('wc-blocks_added_to_cart', {
        detail: payload,
      })
    );
    document.body.dispatchEvent(
      new CustomEvent('noyona_cart_added', {
        bubbles: true,
        detail: {
          source: 'single-product',
        },
      })
    );
  }

  function clearLegacyAddToCartState() {
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.has('add-to-cart')) {
        url.searchParams.delete('add-to-cart');
        var next =
          url.pathname +
          (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') +
          (url.hash || '');
        window.history.replaceState({}, '', next);
      }
    } catch (e) {
      // Ignore URL parsing errors.
    }

    document
      .querySelectorAll(
        '.wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-success,' +
          '.woocommerce-notices-wrapper .woocommerce-message,' +
          '.wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-error,' +
          '.woocommerce-notices-wrapper .woocommerce-error'
      )
      .forEach(function (notice) {
        notice.remove();
      });
  }

  function bindAjaxAddToCart(form) {
    if (!form || form.getAttribute('data-noyona-ajax-cart-bound') === '1') {
      return;
    }

    form.setAttribute('data-noyona-ajax-cart-bound', '1');
    var addBtn = form.querySelector('.single_add_to_cart_button');
    var buyNowField = form.querySelector('input[name="noyona_buy_now"]');
    var inFlight = false;
    var lastRequestAt = 0;

    function runAjaxAddToCart() {
      addBtn = form.querySelector('.single_add_to_cart_button');
      if (!addBtn || addBtn.disabled || addBtn.classList.contains('disabled')) {
        return;
      }
      if (inFlight || addBtn.classList.contains('loading')) {
        return;
      }

      var now = Date.now();
      if (now - lastRequestAt < 350) {
        return;
      }
      lastRequestAt = now;

      var selectedVariationId = 0;
      // Keep variation UX consistent with Woo validation before request.
      if (form.classList.contains('variations_form')) {
        var variationId = form.querySelector('input[name="variation_id"]');
        if (!variationId || !variationId.value || variationId.value === '0') {
          var msg =
            typeof window.noyonaPdp !== 'undefined' &&
            window.noyonaPdp.i18n &&
            window.noyonaPdp.i18n.selectOptions
              ? window.noyonaPdp.i18n.selectOptions
              : 'Please select all options.';
          window.alert(msg);
          return;
        }
        selectedVariationId = parseInt(variationId.value, 10) || 0;
      }

      var endpoint = getAddToCartEndpoint();
      if (!endpoint) {
        return;
      }

      inFlight = true;
      addBtn.classList.add('loading');

      var formData = new FormData(form);
      var payload = new URLSearchParams();

      formData.forEach(function (value, key) {
        if (value === null || typeof value === 'undefined') {
          return;
        }
        var stringValue = String(value);
        if (key !== 'quantity' && stringValue === '') {
          return;
        }
        payload.append(key, stringValue);
      });

      if (!payload.has('product_id')) {
        var explicitProductId =
          form.querySelector('input[name="add-to-cart"]') ||
          form.querySelector('button[name="add-to-cart"]');
        if (explicitProductId && explicitProductId.value) {
          payload.append('product_id', String(explicitProductId.value));
        }
      }

      if (!payload.has('quantity')) {
        payload.append('quantity', '1');
      }

      // Prevent duplicate additions on variable products:
      // this store doubles quantity when both add-to-cart (parent) and product_id (variation) are sent.
      if (payload.has('add-to-cart')) {
        payload.delete('add-to-cart');
      }

      // For variable products, this store accepts AJAX add-to-cart when product_id is the variation ID.
      if (form.classList.contains('variations_form') && selectedVariationId > 0) {
        payload.set('product_id', String(selectedVariationId));
        payload.set('variation_id', String(selectedVariationId));
      }

      fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: payload.toString(),
      })
        .then(function (response) {
          return response.text();
        })
        .then(function (raw) {
          var data = null;
          if (raw) {
            try {
              data = JSON.parse(raw);
            } catch (e) {
              data = null;
            }
          }

          if (data && data.error) {
            addBtn.classList.add('is-error');
            return;
          }

          addBtn.classList.remove('is-error');
          addBtn.classList.remove('loading');
          addBtn.classList.add('added');

          if (typeof window.jQuery !== 'undefined') {
            window.jQuery(document.body).trigger('added_to_cart', [
              data && data.fragments ? data.fragments : null,
              data && data.cart_hash ? data.cart_hash : '',
              addBtn,
            ]);
            window.jQuery(document.body).trigger('wc_fragment_refresh');
          }

          invalidateWooBlocksCartStore();
          broadcastCartAdded({
            source: 'single-product',
          });
          clearLegacyAddToCartState();

          // Open the mini-cart drawer so user sees updated cart, not cart page link.
          setTimeout(maybeOpenMiniCartDrawer, 120);
        })
        .catch(function () {
          // Do not fallback-submit the form: this causes reload + duplicate cart lines.
        })
        .finally(function () {
          inFlight = false;
          addBtn.classList.remove('loading');
        });
    }
    form.__noyonaRunAjaxAddToCart = runAjaxAddToCart;

    if (addBtn && !addBtn.getAttribute('data-noyona-reset-buy-now-bound')) {
      addBtn.setAttribute('data-noyona-reset-buy-now-bound', '1');
      addBtn.addEventListener('click', function () {
        if (buyNowField) {
          buyNowField.value = '';
        }
      });
    }

    form.addEventListener('submit', function (event) {
      buyNowField = form.querySelector('input[name="noyona_buy_now"]');
      if (buyNowField && buyNowField.value === '1') {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      runAjaxAddToCart();
    }, true);
  }

  function bindGlobalAjaxCartGuards() {
    if (document.documentElement.getAttribute('data-noyona-pdp-cart-guard') === '1') {
      return;
    }
    document.documentElement.setAttribute('data-noyona-pdp-cart-guard', '1');

    document.addEventListener(
      'click',
      function (event) {
        var targetBtn = event.target.closest('form.cart .single_add_to_cart_button');
        if (!targetBtn) {
          return;
        }
        var form = targetBtn.closest('form.cart');
        if (!form) {
          return;
        }

        var buyNowField = form.querySelector('input[name="noyona_buy_now"]');
        if (buyNowField && buyNowField.value === '1') {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (typeof form.__noyonaRunAjaxAddToCart === 'function') {
          form.__noyonaRunAjaxAddToCart();
        }
      },
      true
    );

    document.addEventListener(
      'submit',
      function (event) {
        var form = event.target && event.target.closest ? event.target.closest('form.cart') : null;
        if (!form) {
          return;
        }

        var buyNowField = form.querySelector('input[name="noyona_buy_now"]');
        if (buyNowField && buyNowField.value === '1') {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (typeof form.__noyonaRunAjaxAddToCart === 'function') {
          form.__noyonaRunAjaxAddToCart();
        }
      },
      true
    );
  }

  function addBuyNow(form) {
    var addBtn = form.querySelector('.single_add_to_cart_button');
    if (!addBtn || form.querySelector('.noyona-pdp-buy-now')) {
      return;
    }

    var buy = document.createElement('button');
    buy.type = 'button';
    buy.className =
      'noyona-pdp-buy-now button alt wp-element-button wc-block-components-button';
    buy.textContent =
      typeof window.noyonaPdp !== 'undefined' &&
      window.noyonaPdp.i18n &&
      window.noyonaPdp.i18n.buyNow
        ? window.noyonaPdp.i18n.buyNow
        : 'Buy now';

    var actions = document.createElement('div');
    actions.className = 'noyona-pdp-cart-actions';

    var btnParent = addBtn.parentNode;
    btnParent.insertBefore(actions, addBtn);
    actions.appendChild(addBtn);
    actions.appendChild(buy);

    buy.addEventListener('click', function () {
      if (!document.body.classList.contains('logged-in')) {
        openGlobalCheckoutLoginModal(window.location.href, {
          title: 'Log In to complete your purchase',
          copy: 'Please log in to your account so we can verify your purchase and publish your review.',
        });
        return;
      }

      var msg =
        typeof window.noyonaPdp !== 'undefined' &&
        window.noyonaPdp.i18n &&
        window.noyonaPdp.i18n.selectOptions
          ? window.noyonaPdp.i18n.selectOptions
          : 'Please select all options.';

      if (form.classList.contains('variations_form')) {
        var variationId = form.querySelector('input[name="variation_id"]');
        if (!variationId || !variationId.value || variationId.value === '0') {
          window.alert(msg);
          return;
        }
      }

      var existing = form.querySelector('input[name="noyona_buy_now"]');
      if (!existing) {
        existing = document.createElement('input');
        existing.type = 'hidden';
        existing.name = 'noyona_buy_now';
        form.appendChild(existing);
      }

      existing.value = '1';

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(addBtn);
      } else {
        form.submit();
      }
    });
  }

  function enhanceQuantity(form) {
    if (!form) {
      return;
    }
    var qtyWrap = form.querySelector('.quantity');
    var qtyInput = qtyWrap ? qtyWrap.querySelector('.qty') : null;
    if (!qtyWrap || !qtyInput || qtyWrap.classList.contains('noyona-pdp-qty')) {
      return;
    }

    qtyWrap.classList.add('noyona-pdp-qty');

    var minus = document.createElement('button');
    minus.type = 'button';
    minus.className = 'noyona-pdp-qty__btn noyona-pdp-qty__btn--minus';
    minus.setAttribute('aria-label', 'Decrease quantity');
    minus.textContent = '−';

    var plus = document.createElement('button');
    plus.type = 'button';
    plus.className = 'noyona-pdp-qty__btn noyona-pdp-qty__btn--plus';
    plus.setAttribute('aria-label', 'Increase quantity');
    plus.textContent = '+';

    qtyWrap.insertBefore(minus, qtyInput);
    qtyWrap.appendChild(plus);

    function asNumber(value, fallback) {
      var n = parseFloat(value);
      return isNaN(n) ? fallback : n;
    }

    function clamp(next) {
      var min = asNumber(qtyInput.min, 1);
      var max = qtyInput.max === '' ? Infinity : asNumber(qtyInput.max, Infinity);
      var step = asNumber(qtyInput.step, 1);
      if (!isFinite(step) || step <= 0) {
        step = 1;
      }
      var val = Math.max(min, Math.min(max, next));
      var rounded = Math.round(val / step) * step;
      if (Math.abs(rounded - Math.round(rounded)) < 0.0001) {
        rounded = Math.round(rounded);
      }
      return rounded;
    }

    minus.addEventListener('click', function () {
      var current = asNumber(qtyInput.value, asNumber(qtyInput.min, 1));
      qtyInput.value = String(clamp(current - asNumber(qtyInput.step, 1)));
      qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    plus.addEventListener('click', function () {
      var current = asNumber(qtyInput.value, asNumber(qtyInput.min, 1));
      qtyInput.value = String(clamp(current + asNumber(qtyInput.step, 1)));
      qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function initPdp() {
    clearLegacyAddToCartState();
    initTabs(document);

    document.querySelectorAll('form.cart').forEach(function (form) {
      initSwatches(form);
      enhanceQuantity(form);
      bindVariationPriceSync(form);
      bindAjaxAddToCart(form);
      addBuyNow(form);
    });
  }

  bindWooCommercePdpHooks();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPdp);
  } else {
    initPdp();
  }

  // Handle delayed Woo markup injections in block-based templates.
  if (typeof MutationObserver !== 'undefined' && document.body) {
    var t = null;
    var observer = new MutationObserver(function () {
      clearTimeout(t);
      t = setTimeout(initPdp, 100);
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();