/* global grecaptcha, noyonaRecaptchaV3, noyonaRecaptchaForms */
/**
 * Generic reCAPTCHA v3 token generator for the account/contact forms
 * (login, register, contact). The newsletter form uses its own dedicated
 * handler (assets/js/recaptcha/recaptcha-v3.js) and is intentionally not handled here.
 *
 * These forms submit as normal POST requests, so we generate a token on load
 * and refresh it periodically (v3 tokens expire after ~2 minutes), writing it
 * into each form's hidden input[name="g-recaptcha-response"]. The token is then
 * already present when the user submits.
 *
 * Configuration is provided by PHP via wp_localize_script:
 *   - window.noyonaRecaptchaV3.siteKey  (the v3 site key)
 *   - window.noyonaRecaptchaForms.forms = [ { selector, action }, ... ]
 *
 * This script only acts on forms that actually contain the hidden token input,
 * so listing a selector that is not present (or is a v2 form) is harmless.
 */
(function () {
  'use strict';

  var REFRESH_INTERVAL_MS = 100000; // ~100s, safely under the ~120s token lifetime.

  function getSiteKey() {
    return window.noyonaRecaptchaV3 && noyonaRecaptchaV3.siteKey
      ? String(noyonaRecaptchaV3.siteKey)
      : '';
  }

  function getTargets() {
    return window.noyonaRecaptchaForms && Array.isArray(noyonaRecaptchaForms.forms)
      ? noyonaRecaptchaForms.forms
      : [];
  }

  function whenGrecaptchaReady(callback) {
    if (window.grecaptcha && typeof grecaptcha.ready === 'function') {
      grecaptcha.ready(callback);
      return;
    }

    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      if (window.grecaptcha && typeof grecaptcha.ready === 'function') {
        clearInterval(timer);
        grecaptcha.ready(callback);
      } else if (tries > 100) {
        clearInterval(timer);
      }
    }, 100);
  }

  function refreshToken(form, action) {
    var siteKey = getSiteKey();
    if (!siteKey) {
      return;
    }

    var tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
    if (!tokenInput) {
      return;
    }

    whenGrecaptchaReady(function () {
      grecaptcha
        .execute(siteKey, { action: action })
        .then(function (token) {
          tokenInput.value = token || '';
        })
        .catch(function () {
          // Keep any previous token on transient failures.
        });
    });
  }

  function init() {
    getTargets().forEach(function (target) {
      if (!target || !target.selector) {
        return;
      }

      var action = target.action || 'submit';
      var forms = document.querySelectorAll(target.selector);

      Array.prototype.forEach.call(forms, function (form) {
        if (!form.querySelector('input[name="g-recaptcha-response"]')) {
          return;
        }

        refreshToken(form, action);
        setInterval(function () {
          refreshToken(form, action);
        }, REFRESH_INTERVAL_MS);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
