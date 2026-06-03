/* global grecaptcha, noyonaRecaptchaV3, noyonaNewsletterStrip */
(function () {
  'use strict';

  var cfg = window.noyonaNewsletterStrip || {};
  var SELECTORS = {
    form: '.newsletter-strip__form',
    email: '[name="newsletter_email"]',
    submit: '.newsletter-strip__button[type="submit"]',
    notice: '.newsletter-strip__inner > .noyona-notice[data-noyona-notice-key="newsletter-strip"]',
  };
  var messages = {
    invalidEmail: cfg.invalidEmail || 'Please enter a valid email address.',
    captchaFailed: cfg.captchaFailed || 'Captcha verification failed. Please try again.',
    networkError: cfg.networkError || 'Could not subscribe right now. Please try again.',
    submitting: cfg.submitting || 'Submitting...',
  };

  function showNotice(form, message, type, autoHideMs) {
    if (!form || typeof window.noyonaShowNotice !== 'function') {
      return null;
    }

    return window.noyonaShowNotice(message, {
      type: type || 'error',
      key: 'newsletter-strip',
      insertBefore: form,
      autoHideMs: autoHideMs || 0,
    });
  }

  function isValidEmail(value) {
    var input = document.createElement('input');
    input.type = 'email';
    input.value = value;
    return input.checkValidity();
  }

  function validateForm(form) {
    var field = form.querySelector(SELECTORS.email);
    var value = field ? String(field.value || '').trim() : '';

    if (value && isValidEmail(value)) {
      return true;
    }

    showNotice(form, messages.invalidEmail, 'error', 0);
    if (field) {
      field.focus();
    }
    return false;
  }

  function setBusy(form, busy) {
    var button = form.querySelector(SELECTORS.submit);
    if (!button) {
      return;
    }

    if (!button.dataset.newsletterDefaultLabel) {
      button.dataset.newsletterDefaultLabel = button.textContent || '';
    }

    button.disabled = !!busy;
    button.toggleAttribute('aria-busy', !!busy);
    button.textContent = busy ? messages.submitting : button.dataset.newsletterDefaultLabel;
  }

  function runCaptcha(form) {
    var tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
    if (!tokenInput) {
      return Promise.resolve();
    }

    if (!window.grecaptcha || !window.noyonaRecaptchaV3 || !noyonaRecaptchaV3.siteKey) {
      return Promise.reject(new Error('recaptcha_unavailable'));
    }

    return new Promise(function (resolve, reject) {
      grecaptcha.ready(function () {
        grecaptcha
          .execute(noyonaRecaptchaV3.siteKey, { action: 'newsletter_subscribe' })
          .then(function (token) {
            tokenInput.value = token || '';
            resolve();
          })
          .catch(reject);
      });
    });
  }

  function noticeFromResponse(data) {
    return data && data.data && data.data.notice ? data.data.notice : null;
  }

  function applyNotice(form, notice) {
    if (!notice || !notice.message) {
      showNotice(form, messages.networkError, 'error', 0);
      return;
    }

    showNotice(
      form,
      notice.message,
      notice.type || 'error',
      parseInt(notice.autohide, 10) || 0
    );

    if (notice.type === 'success') {
      form.reset();
    }
  }

  function postForm(form) {
    var body = new FormData(form);
    body.set('action', cfg.ajaxAction || 'noyona_newsletter_subscribe');

    setBusy(form, true);

    return fetch(cfg.ajaxUrl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (response) {
        return response.json().catch(function () {
          return null;
        });
      })
      .then(function (data) {
        applyNotice(form, noticeFromResponse(data));
      })
      .catch(function () {
        showNotice(form, messages.networkError, 'error', 0);
      })
      .finally(function () {
        setBusy(form, false);
        form.dataset.newsletterSubmitting = '0';
      });
  }

  function handleSubmit(event) {
    var form = event.target;
    if (!form || !form.matches || !form.matches(SELECTORS.form)) {
      return;
    }

    event.preventDefault();

    if (form.dataset.newsletterSubmitting === '1' || !validateForm(form)) {
      return;
    }

    form.dataset.newsletterSubmitting = '1';

    runCaptcha(form)
      .then(function () {
        return postForm(form);
      })
      .catch(function () {
        form.dataset.newsletterSubmitting = '0';
        showNotice(form, messages.captchaFailed, 'error', 0);
      });
  }

  function cleanNewsletterQueryFromUrl() {
    try {
      var url = new URL(window.location.href);
      var hasNewsletterQuery =
        url.searchParams.has('newsletter_success') || url.searchParams.has('newsletter_error');

      if (!hasNewsletterQuery) {
        return;
      }

      url.searchParams.delete('newsletter_success');
      url.searchParams.delete('newsletter_error');
      window.history.replaceState({}, '', url.pathname + url.search + url.hash);
    } catch (e) {
      // URL/history APIs may be unavailable in older embedded browsers.
    }
  }

  function scrollToNotice() {
    var notice = document.querySelector(SELECTORS.notice);
    if (notice) {
      notice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  }

  function onReady() {
    scrollToNotice();
    cleanNewsletterQueryFromUrl();
  }

  document.addEventListener('submit', handleSubmit, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
