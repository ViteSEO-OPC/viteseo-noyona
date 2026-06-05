/**
 * Global Noyona notice banners (pairs with assets/css/noyona-notices.css).
 */
(function () {
  'use strict';

  var DEFAULT_NOTICE_AUTOHIDE_MS = 10000;
  var NOTICE_SELECTOR = [
    '.noyona-notice',
    '.noyona-mini-cart-stock-notice',
    'ul.woocommerce-message',
    'ul.woocommerce-error',
    'ul.woocommerce-info',
    'p.woocommerce-info',
    '.wc-block-components-notice-banner',
  ].join(',');

  function isNoticeBanner(el) {
    if (!el || !el.matches || !el.matches(NOTICE_SELECTOR)) {
      return false;
    }

    // Inline field validation uses the same Woo classes but should stay tied to the field.
    return !el.closest('.form-row');
  }

  function isPersistentEmptyCartNotice(el) {
    if (!el || !document.body) {
      return false;
    }

    var isCartPage =
      document.body.classList.contains('woocommerce-cart') ||
      !!document.querySelector('.noyona-cart-summary-card, .noyona-cart-ajax-region, .woocommerce-cart-form, .cart-empty') ||
      /\/cart\/?$/i.test(window.location.pathname || '');

    if (!isCartPage) {
      return false;
    }

    var message = String(el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    return message.indexOf('your cart is currently empty') !== -1;
  }

  function resolveAutoHideDelay(ms) {
    var delay = parseInt(ms, 10);
    return delay > 0 ? delay : DEFAULT_NOTICE_AUTOHIDE_MS;
  }

  function scheduleAutoHide(el, ms) {
    if (!el) {
      return;
    }
    if (isPersistentEmptyCartNotice(el)) {
      el.removeAttribute('data-noyona-notice-autohide');
      if (el._noyonaAutoHideTimer) {
        window.clearTimeout(el._noyonaAutoHideTimer);
        el._noyonaAutoHideTimer = null;
      }
      return;
    }

    var delay = resolveAutoHideDelay(ms);

    if (el._noyonaAutoHideTimer) {
      window.clearTimeout(el._noyonaAutoHideTimer);
      el._noyonaAutoHideTimer = null;
    }

    el.setAttribute('data-noyona-notice-autohide', String(delay));
    el._noyonaAutoHideTimer = window.setTimeout(function () {
      if (el.parentNode) {
        el.remove();
      }
    }, delay);
  }

  function getNoticeScope(options) {
    var opts = options || {};

    if (opts.insertBefore && opts.insertBefore.parentNode) {
      return opts.insertBefore.parentNode;
    }

    if (opts.root) {
      return opts.root;
    }

    return document.querySelector('main') || document.body;
  }

  function findNoticeInScope(scope, key) {
    if (!scope) {
      return null;
    }

    var keyed = scope.querySelector('[data-noyona-notice-key="' + key + '"]');
    if (keyed) {
      return keyed;
    }

    var notices = scope.querySelectorAll('.noyona-notice');
    return notices.length ? notices[0] : null;
  }

  function removeExtraNoticesInScope(scope, keep) {
    if (!scope) {
      return;
    }

    scope.querySelectorAll('.noyona-notice').forEach(function (notice) {
      if (notice !== keep) {
        if (notice._noyonaAutoHideTimer) {
          window.clearTimeout(notice._noyonaAutoHideTimer);
        }
        notice.remove();
      }
    });
  }

  function updateNoticeElement(el, message, type, key, autoHideMs) {
    el.className = 'noyona-notice is-' + type;
    el.setAttribute('data-noyona-notice-key', key);
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.textContent = String(message || '');
    scheduleAutoHide(el, autoHideMs);
  }

  function showNotice(message, options) {
    var opts = options || {};
    var type =
      opts.type === 'success' || opts.type === 'info' || opts.type === 'warning'
        ? opts.type
        : 'error';
    var scope = getNoticeScope(opts);
    var key = String(opts.key || 'default');

    var autoHideMs = resolveAutoHideDelay(opts.autoHideMs);

    var existing = findNoticeInScope(scope, key);
    removeExtraNoticesInScope(scope, existing);

    if (existing) {
      updateNoticeElement(existing, message, type, key, autoHideMs);
      return existing;
    }

    var el = document.createElement('p');
    updateNoticeElement(el, message, type, key, autoHideMs);

    if (opts.insertBefore && opts.insertBefore.parentNode) {
      opts.insertBefore.parentNode.insertBefore(el, opts.insertBefore);
    } else {
      scope.insertBefore(el, scope.firstChild);
    }

    return el;
  }

  function initAutoHideNotices() {
    document.querySelectorAll(NOTICE_SELECTOR + ',[data-noyona-notice-autohide]').forEach(function (el) {
      if (isNoticeBanner(el) && !el._noyonaAutoHideTimer) {
        scheduleAutoHide(el, el.getAttribute('data-noyona-notice-autohide'));
      }
    });
  }

  function observeAutoHideNotices() {
    if (!window.MutationObserver) {
      return;
    }

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (!node || node.nodeType !== 1) {
            return;
          }

          if (isNoticeBanner(node) && !node._noyonaAutoHideTimer) {
            scheduleAutoHide(node, node.getAttribute('data-noyona-notice-autohide'));
          }

          if (node.querySelectorAll) {
            node.querySelectorAll(NOTICE_SELECTOR + ',[data-noyona-notice-autohide]').forEach(function (el) {
              if (isNoticeBanner(el) && !el._noyonaAutoHideTimer) {
                scheduleAutoHide(el, el.getAttribute('data-noyona-notice-autohide'));
              }
            });
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  function initNotices() {
    initAutoHideNotices();
    observeAutoHideNotices();
  }

  window.noyonaShowNotice = showNotice;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotices);
  } else {
    initNotices();
  }
})();
