/* global grecaptcha, noyonaRecaptchaV3 */
(function () {
  'use strict';

  function runCaptcha(form) {
    if (!window.grecaptcha || !window.noyonaRecaptchaV3 || !noyonaRecaptchaV3.siteKey) {
      return Promise.reject(new Error('reCAPTCHA v3 is not ready.'));
    }

    return new Promise(function (resolve, reject) {
      grecaptcha.ready(function () {
        grecaptcha
          .execute(noyonaRecaptchaV3.siteKey, { action: 'newsletter_subscribe' })
          .then(function (token) {
            var tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
            if (tokenInput) {
              tokenInput.value = token || '';
            }
            resolve();
          })
          .catch(function (error) {
            reject(error);
          });
      });
    });
  }

  function onSubmit(event) {
    var form = event.target;
    if (!form || !form.matches('.newsletter-strip__form')) {
      return;
    }

    var tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
    if (!tokenInput) {
      return;
    }

    if (form.dataset.recaptchaSubmitting === '1') {
      return;
    }

    event.preventDefault();
    form.dataset.recaptchaSubmitting = '1';

    runCaptcha(form)
      .then(function () {
        form.submit();
      })
      .catch(function () {
        form.dataset.recaptchaSubmitting = '0';
      });
  }

  document.addEventListener('submit', onSubmit, true);
})();
