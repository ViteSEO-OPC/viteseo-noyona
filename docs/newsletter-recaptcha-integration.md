# Newsletter reCAPTCHA integration

**Project:** Noyona (WordPress + WooCommerce child theme `viteseo-noyona`)  
**Feature scope:** Newsletter strip submit flow + WooCommerce login form captcha (`blocks/newsletter-strip`, login hooks in `inc/theme-setup.php`)  
**Security layer:** Google reCAPTCHA (`v2` or `v3`) + nonce + server-side verification

## Purpose

This implementation adds server-validated form protection using Google reCAPTCHA for:

- newsletter submission (v3), and
- login form verification (v2).

The `newsletter-strip` block remains primarily a UI template, while submit handling and captcha verification are moved into shared `inc/` modules and login hooks.

---

## What was implemented

### 1) Newsletter submit handler

File: `inc/newsletter.php`

- Registers both guest and logged-in handlers:
  - `admin_post_nopriv_noyona_newsletter_subscribe`
  - `admin_post_noyona_newsletter_subscribe`
- Handles all server-side processing in `noyona_handle_newsletter_subscribe()`.

### 2) Reusable reCAPTCHA module

File: `inc/recaptcha.php`

- Centralized key access, script enqueue, widget markup, and verification logic.
- Can be reused by other forms by calling helper functions instead of duplicating captcha code.

### 3) v3 token generation script

File: `assets/js/recaptcha-v3.js`

- Intercepts newsletter form submit.
- Calls `grecaptcha.execute(...)`.
- Stores token in hidden `g-recaptcha-response`.
- Continues submit only when token is attached.

### 4) Newsletter block integration

File: `blocks/newsletter-strip/render.php`

- Keeps UI rendering intact.
- Adds:
  - hidden form action (`noyona_newsletter_subscribe`)
  - nonce field (`noyona_newsletter_nonce`)
  - captcha markup from helper

### 5) Theme loader wiring

File: `functions.php`

- Loads:
  - `inc/recaptcha.php`
  - `inc/newsletter.php`

### 6) Login form integration (v2)

File: `inc/theme-setup.php`

- Renders reCAPTCHA v2 widget inside WooCommerce login form using hooks.
- Verifies captcha server-side via `woocommerce_process_login_errors`.
- Adds login error message when captcha fails.
- Places captcha in the login footer block (between Google sign-in and "Create an Account").
- Keeps captcha UI visible before verification and hides it after successful human verification.

---

## End-to-end flow (how it works)

### Newsletter flow (v3)

1. User submits `newsletter-strip` form.
2. Form posts to `admin-post.php` with action `noyona_newsletter_subscribe`.
3. WordPress routes action to `noyona_handle_newsletter_subscribe()`.
4. Handler validates nonce.
5. Handler verifies reCAPTCHA response (if captcha is configured).
6. Handler sanitizes and validates email.
7. Handler sends email via `wp_mail()` to `get_option('admin_email')`.
8. User is redirected with:
   - success: `newsletter_success=1`
   - failure: `newsletter_error=...`

### Login flow (v2)

1. User opens `/login/` (WooCommerce account login form context).
2. Theme hook renders reCAPTCHA v2 widget in login form.
3. User verifies the widget (checkbox challenge).
4. On submit, WooCommerce login validation runs and theme checks captcha token server-side.
5. If captcha fails, login is blocked and form error is shown.
6. If captcha passes, normal login validation/authentication continues.

---

## Guards and security checks included

### CSRF guard

- Nonce field is added in form.
- Nonce is validated server-side before processing.

### Bot/spam guard

- reCAPTCHA token is validated server-side using Google verify endpoint.
- Client-side token alone is not trusted without backend verification.

### Input validation guard

- `newsletter_email` is sanitized with `sanitize_email()`.
- Email format is validated with `is_email()`.

### Fail-safe redirects

- Invalid nonce, captcha failure, invalid email, and mail failure all exit safely via redirect.

### Login fail-safe validation

- Login captcha is validated server-side before authentication completes.
- Failed captcha adds a login error via WooCommerce validation pipeline.

---

## Development guardrails

To keep this integration maintainable and safe, apply these rules for future updates:

- Do not change unrelated global behavior while updating newsletter captcha flow.
- Avoid over-engineering; prefer the smallest change that preserves security and readability.
- Do not add or change styles unless a developer/designer explicitly requests styling changes.
- Do not modify WordPress core files (`wp-admin`, `wp-includes`, core `wp-*.php` files).
- Keep block templates focused on markup/UI and keep business logic in `inc/` modules.
- Reuse existing helpers before introducing new utility functions.
- Do not delete or rewrite existing unrelated code; append/integrate changes with minimal impact.
- If requirements are unclear or there is a conflict with existing behavior, ask for clarification first before applying changes.

---

## Function reference and purpose

### `inc/newsletter.php`

- `noyona_handle_newsletter_subscribe()`
  - Main submit controller for newsletter.
  - Performs validation, captcha verify, email sending, and redirect result.

### `inc/theme-setup.php` (login-related hooks)

- `woocom_ct_add_register_link_to_login()`
  - Renders the Google sign-in button and register link block.
  - Injects the reCAPTCHA v2 widget into that same block for stable ordering.
  - Enqueues v2 script through shared helper.
  - Includes callback/fallback script to hide captcha UI after successful verification.

- `noyona_validate_login_recaptcha()`
  - Verifies v2 token server-side using shared helper during login validation.
  - Blocks login and adds error when captcha verification fails.

### `inc/recaptcha.php`

- `noyona_get_recaptcha_site_key()`
  - Reads site key by requested captcha version, with fallback constants.

- `noyona_get_recaptcha_secret_key()`
  - Reads secret key by requested captcha version, with fallback constants.

- `noyona_get_recaptcha_version()`
  - Reads captcha version (`v2` or `v3`), defaults safely if invalid.

- `noyona_get_recaptcha_score_threshold()`
  - Provides configurable score threshold for v3 checks.

- `noyona_is_recaptcha_enabled()`
  - Ensures both site and secret keys are available for the requested version.

- `noyona_enqueue_recaptcha_script()`
  - Enqueues version-specific script (`v2` checkbox script or `v3` execute flow script).

- `noyona_get_recaptcha_widget_markup()`
  - Returns markup:
    - v3: hidden token field
    - v2: checkbox widget markup (supports data attributes like callback)

- `noyona_verify_recaptcha_token()`
  - Performs server-side verification call to Google.
  - For v3, validates action and score threshold.

- `noyona_verify_recaptcha_from_post()`
  - Convenience wrapper reading token from `$_POST`.

---

## Configuration used

Current expected constants:

- `NOYONA_RECAPTCHA_SITE_KEY`
- `NOYONA_RECAPTCHA_SECRET_KEY`
- `NOYONA_RECAPTCHA_VERSION`

Version-specific constants (recommended when running mixed versions across forms):

- `NOYONA_RECAPTCHA_V2_SITE_KEY`
- `NOYONA_RECAPTCHA_V2_SECRET_KEY`
- `NOYONA_RECAPTCHA_V3_SITE_KEY`
- `NOYONA_RECAPTCHA_V3_SECRET_KEY`

Note: `NOYONA_RECAPTCHA_V2_VERSION` is not required by this implementation.

---

## Google links and operations guide

### Official links

- reCAPTCHA Admin Console (manage keys, domains, analytics):
  - [https://www.google.com/recaptcha/admin](https://www.google.com/recaptcha/admin)
- reCAPTCHA v3 guide:
  - [https://developers.google.com/recaptcha/docs/v3](https://developers.google.com/recaptcha/docs/v3)
- reCAPTCHA v2 guide:
  - [https://developers.google.com/recaptcha/docs/display](https://developers.google.com/recaptcha/docs/display)
- Server-side verify response reference:
  - [https://developers.google.com/recaptcha/docs/verify](https://developers.google.com/recaptcha/docs/verify)
- Domain/package name validation notes:
  - [https://developers.google.com/recaptcha/docs/domain_validation](https://developers.google.com/recaptcha/docs/domain_validation)

### How to update allowed domains

1. Open [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin).
2. Select the key pair used by this project.
3. In key settings, update the **Domains** list.
4. Save changes.
5. Retest submit flow on target host.

Recommended local/dev entries when needed:

- `noyonaqa.local`
- `localhost`
- `127.0.0.1`

### How to check and interpret v3 score

1. Open the site key in [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin).
2. Go to Analytics/traffic view for that key.
3. Look for score distribution and action activity (for this flow, action is `newsletter_subscribe`).
4. Compare observed scores against your threshold logic.

General interpretation:

- Closer to `1.0` = more likely human.
- Closer to `0.0` = more likely bot/risk.
- Common starting threshold is `0.5`, then tune based on real traffic quality.

### How to identify if the integration works

Expected newsletter success path:

- Form submits and redirects with `newsletter_success=1`.
- Backend accepted nonce, captcha verification, and email validation.

Expected newsletter failure path:

- Redirect includes `newsletter_error=captcha_failed`.
- Indicates captcha token was missing/invalid or verification failed.

What to verify during testing:

- Request payload includes `g-recaptcha-response`.
- The submit action is `noyona_newsletter_subscribe`.
- For v3, no checkbox UI is expected (invisible flow is normal).

Expected login success path:

- User can complete v2 challenge and log in successfully.
- After verification, login captcha container is hidden.

Expected login failure path:

- Login blocked with captcha error when token is missing/invalid.

---

## Reusability and version support

Yes, this module is reusable.

- Reusable across forms:
  - Any form can use the same helpers by adding nonce + token field + submit handler call to `noyona_verify_recaptcha_from_post()`.

- Reusable across reCAPTCHA versions:
  - Supports both `v2` and `v3` through `NOYONA_RECAPTCHA_VERSION`.
  - v2 uses checkbox widget markup.
  - v3 uses hidden token flow + JS execute flow.

---

## Considerations

- v3 is intentionally invisible (no checkbox UI).
- v2 is visible (checkbox widget UI).
- Server-side verification is the source of truth.
- Receiver email is not from form action; it is `get_option('admin_email')`.
- `formAction` controls request destination only (routing), not recipient.

