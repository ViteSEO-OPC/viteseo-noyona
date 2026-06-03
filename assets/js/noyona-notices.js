/**
 * Global Noyona notice banners (pairs with assets/css/noyona-notices.css).
 */
(function () {
  'use strict';

  var DEFAULT_SUCCESS_AUTOHIDE_MS = 6000;

  function scheduleAutoHide(el, ms) {
    var delay = parseInt(ms, 10);
    if (!el) {
      return;
    }

    if (el._noyonaAutoHideTimer) {
      window.clearTimeout(el._noyonaAutoHideTimer);
      el._noyonaAutoHideTimer = null;
    }

    if (!delay || delay < 1) {
      el.removeAttribute('data-noyona-notice-autohide');
      return;
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

    var autoHideMs = parseInt(opts.autoHideMs, 10);
    if ((!autoHideMs || autoHideMs < 1) && type === 'success') {
      autoHideMs = DEFAULT_SUCCESS_AUTOHIDE_MS;
    }

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
    document.querySelectorAll('[data-noyona-notice-autohide]').forEach(function (el) {
      if (!el._noyonaAutoHideTimer) {
        scheduleAutoHide(el, el.getAttribute('data-noyona-notice-autohide'));
      }
    });
  }

  window.noyonaShowNotice = showNotice;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutoHideNotices);
  } else {
    initAutoHideNotices();
  }
})();
