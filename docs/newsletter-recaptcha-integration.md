# reCAPTCHA integration (per-form v2/v3 switcher)

**Project:** Noyona (WordPress + WooCommerce child theme `viteseo-noyona`)
**Feature scope:** Contact form, Login form, Register form, Newsletter strip
**Security layer:** Google reCAPTCHA (`v2` checkbox or `v3` invisible) + nonce + server-side verification

## Purpose

This implementation protects every public-facing form with Google reCAPTCHA and lets
each form independently use **v2** (checkbox) or **v3** (invisible score) — switchable
from a single map, with no changes to `wp-config.php` keys and no inline reCAPTCHA logic
inside the form files.

Covered forms:

| Form       | Slug         | Default version | Where it lives |
|------------|--------------|-----------------|----------------|
| Contact    | `contact`    | `v2`            | `blocks/contact-form/render.php`, `inc/contact-form.php` (incl. Contact Form 7) |
| Login      | `login`      | `v3`            | `inc/theme-setup.php` (WooCommerce login hooks) |
| Register   | `register`   | `v2`            | `inc/shortcodes.php` (custom register shortcode) |
| Newsletter | `newsletter` | `v3`            | `blocks/newsletter-strip/render.php`, `inc/newsletter.php` |

> Defaults above reflect the current `noyona_recaptcha_form_version_map()`. They can be changed at any time (see "How to switch").

---

## File / folder structure

All reCAPTCHA code is grouped under dedicated folders:

```
inc/recaptcha/
  loader.php           ← single entry point (the only thing functions.php includes)
  recaptcha.php        ← shared base layer (keys, enqueue, widget markup, verify API)
  recaptcha-forms.php  ← per-form switcher + thin wrapper functions (this is the feature layer)

assets/js/recaptcha/
  recaptcha-v3.js        ← newsletter-only v3 handler (intercepts submit, runs execute)
  recaptcha-forms-v3.js  ← generic v3 token generator for login / register / contact
```

`functions.php` loads the whole module with one line:

```php
'recaptcha/loader.php',
```

`loader.php` then includes `recaptcha.php` first (base) and `recaptcha-forms.php` second (depends on the base).

---

## How to switch a form's version

Edit the single map in `inc/recaptcha/recaptcha-forms.php`:

```php
function noyona_recaptcha_form_version_map() {
    $map = array(
        'contact'    => 'v2',
        'login'      => 'v3',
        'register'   => 'v2',
        'newsletter' => 'v3',
    );
    return (array) apply_filters( 'noyona_recaptcha_form_version_map', $map );
}
```

Change a value to `'v2'` or `'v3'` and save. That single change updates, for that form:

- which Google script loads (v2 checkbox API vs v3 `render=KEY`),
- the rendered widget (visible checkbox vs invisible hidden token field),
- the server-side verification (plain pass/fail vs action + score check).

Any value other than `'v2'`/`'v3'` safely falls back to `'v2'`.

**Runtime override (no file edit):** use the `noyona_recaptcha_form_version_map` or
`noyona_recaptcha_form_version` filters.

---

## Architecture: the wrapper layer

Form files never contain reCAPTCHA logic — they only **call** wrappers from
`inc/recaptcha/recaptcha-forms.php`:

| Function | Purpose |
|----------|---------|
| `noyona_recaptcha_form_version_map()` | The switch (per-form version). |
| `noyona_recaptcha_form_version( $form )` | Normalized `v2`/`v3` for a form. |
| `noyona_recaptcha_form_action( $form )` | v3 action name (used by JS `execute` + server verify). |
| `noyona_recaptcha_form_enabled( $form )` | Whether keys exist for the form's version. |
| `noyona_recaptcha_form_enqueue_assets( $form )` | Loads the right Google script (+ generic v3 JS for non-newsletter v3). |
| `noyona_recaptcha_form_widget_html( $form, $class, $attrs )` | Returns widget markup (and enqueues). |
| `noyona_recaptcha_form_render_widget( $form, $class, $attrs )` | Echoes widget markup, escaped via `wp_kses`. |
| `noyona_recaptcha_form_verify_post( $form )` | Verifies `$_POST` token (returns `true` or `WP_Error`). |
| `noyona_recaptcha_form_verify_token( $form, $token, $ip )` | Verifies an already-extracted token (e.g. Contact Form 7). |
| `noyona_recaptcha_register_token_field()` | Register-only: hidden token field that sits INSIDE the form. |
| `noyona_recaptcha_register_external_widget( $class )` | Register-only: visible v2 checkbox placed OUTSIDE the form. |

These wrap the shared base helpers in `inc/recaptcha/recaptcha.php`
(`noyona_get_recaptcha_site_key`, `noyona_enqueue_recaptcha_script`,
`noyona_get_recaptcha_widget_markup`, `noyona_verify_recaptcha_token`,
`noyona_verify_recaptcha_from_post`, etc.).

---

## Per-form integration

### Contact (`contact`)

- `blocks/contact-form/render.php` builds the widget via `noyona_recaptcha_form_widget_html('contact', 'contact-form__captcha')`.
- The same markup is injected into the Contact Form 7 variant (`.contact-form__form--cf7`).
- `inc/contact-form.php`:
  - native handler verifies via `noyona_recaptcha_form_verify_post('contact')`,
  - CF7 validation (`wpcf7_validate`) verifies via `noyona_recaptcha_form_verify_token('contact', $token, $ip)`.

### Login (`login`)

- `inc/theme-setup.php` renders the widget inside the WooCommerce login form footer via
  `noyona_recaptcha_form_widget_html('login', 'noyona-login-recaptcha', ['data-callback' => 'noyonaLoginRecaptchaVerified'])`,
  echoed through `wp_kses( ..., noyona_recaptcha_form_allowed_html() )` (the allowlist permits both the v2 `div` and the v3 hidden `input`).
- Server check runs in `noyona_validate_login_recaptcha()` via `noyona_recaptcha_form_verify_post('login')`.
- For v3 (default) there is no checkbox; the hidden token field is filled by the generic v3 JS.

### Register (`register`) — split placement

On the register page the "Sign Up with Google" button and the "Log In" link live **outside**
the `<form>`, but the token must submit **with** the form. So it is rendered in two pieces:

1. `noyona_recaptcha_register_token_field()` — a hidden `g-recaptcha-response` field placed **inside** the form (before the Sign Up button).
2. `noyona_recaptcha_register_external_widget()` — the visible **v2 checkbox** placed **below the Google button** (and above the Log In link). Its `data-callback` copies the solved token into the in-form hidden field; `data-expired/error-callback` clears it.

For **v3**, the external widget outputs nothing (invisible) and the hidden field is filled by the generic v3 JS.

Server check runs in `woocom_ct_handle_register_form()` via `noyona_recaptcha_form_verify_post('register')`; failure redirects with `register_error=captcha_failed`.

CSS: `.noyona-register-recaptcha` (in `style.css`) centers the checkbox and adds a gap above it.

### Newsletter (`newsletter`)

- `blocks/newsletter-strip/render.php` builds the widget via `noyona_recaptcha_form_widget_html('newsletter', 'newsletter-strip__captcha')`.
- `inc/newsletter.php` verifies via `noyona_recaptcha_form_verify_post('newsletter')`.
- Newsletter keeps its **own** front-end handler (`assets/js/recaptcha/recaptcha-v3.js`), which intercepts the submit, runs `grecaptcha.execute(action: 'newsletter_subscribe')`, and submits once the token is attached.

---

## Front-end token flow (v3)

- **Newsletter:** `recaptcha-v3.js` intercepts submit, generates the token on demand, then submits.
- **Login / Register / Contact:** `recaptcha-forms-v3.js` reads `noyonaRecaptchaForms.forms`
  (selector + action, localized from PHP) and `noyonaRecaptchaV3.siteKey`, then generates a token
  on page load and refreshes it every ~100s (v3 tokens expire after ~2 min), writing it into each
  form's hidden `g-recaptcha-response`. It only touches forms that actually contain that hidden field,
  so listing a non-present or v2 form selector is harmless.

For **v2**, no token JS is needed — the checkbox writes its own response (register being the
exception, where the checkbox sits outside the form and uses a callback to sync the in-form field).

---

## Guards and security

- **Server-side verification is the source of truth.** A client token alone is never trusted; every
  form calls Google's `siteverify`. v3 additionally enforces **action match** and **score ≥ threshold**.
- **CSRF:** nonce fields on contact (`noyona_contact_form_nonce`), newsletter (`noyona_newsletter_nonce`),
  register (`noyona_register_nonce`); login uses the WooCommerce login pipeline.
- **Output escaping:** widget markup is echoed via `wp_kses()` with an explicit allowlist
  (`noyona_recaptcha_form_allowed_html()` — `div` + `input` with `data-*`), or via `esc_attr()` for the
  register external widget. The inline sync scripts contain no user input.
- **Secret key is never output** — only the public site key appears in markup (as intended).
- **Fail-safe:** missing/empty/invalid tokens fail verification and exit via safe redirect or a form error.
- **Graceful disable:** if keys are not configured for a form's version, the wrappers return `true`
  (skip) so the site keeps working; protection is simply inactive until keys exist.

---

## Audit notes (known, non-blocking considerations)

- **Mixed v2 + v3 on the same page:** loading both Google API variants on one page is unofficial and
  best avoided. In this theme the newsletter strip only appears on the `page-home`/`page-lovial`
  templates, which do not contain the login/register/contact forms, so no collision occurs with the
  current layout. If you ever place a v3 form and a v2 form on the same page, set them to the same version.
- **Login notice-hider on v3:** the inline script that hides the "captcha failed" notice on a `change`
  event won't fire for invisible v3 (programmatic value sets don't trigger `change`). This is cosmetic
  only and does not affect verification.
- **Register cleanup regex:** the register markup passes through `noyona_clean_register_markup()` which
  collapses whitespace between tags (`>\s+<`). The current inline scripts contain no `>`/`<` comparison
  patterns, so they are safe; avoid adding HTML-like comparisons inside those inline scripts.

---

## Development guardrails

- Switch versions only via `noyona_recaptcha_form_version_map()` (or its filters) — do not hardcode versions in form files.
- Keep all reCAPTCHA logic inside `inc/recaptcha/`; form files should only **call** the wrappers.
- Do not modify `wp-config.php` reCAPTCHA constants or unrelated configuration.
- Keep block templates focused on markup/UI; keep verification/logic in the module.
- Do not add or change styles unless explicitly requested.
- Reuse existing wrappers before adding new ones.

---

## Configuration (wp-config.php)

Generic constants:

- `NOYONA_RECAPTCHA_SITE_KEY`
- `NOYONA_RECAPTCHA_SECRET_KEY`
- `NOYONA_RECAPTCHA_VERSION`

Version-specific constants (recommended when running mixed versions across forms):

- `NOYONA_RECAPTCHA_V2_SITE_KEY`
- `NOYONA_RECAPTCHA_V2_SECRET_KEY`
- `NOYONA_RECAPTCHA_V3_SITE_KEY`
- `NOYONA_RECAPTCHA_V3_SECRET_KEY`

Optional:

- `NOYONA_RECAPTCHA_SCORE_THRESHOLD` (defaults to `0.5`, clamped to `0..1`).

Key resolution: a version-specific key is used when present, otherwise it falls back to the generic key.

---

## Google links and operations guide

### Official links

- reCAPTCHA Admin Console: [https://www.google.com/recaptcha/admin](https://www.google.com/recaptcha/admin)
- reCAPTCHA v3 guide: [https://developers.google.com/recaptcha/docs/v3](https://developers.google.com/recaptcha/docs/v3)
- reCAPTCHA v2 guide: [https://developers.google.com/recaptcha/docs/display](https://developers.google.com/recaptcha/docs/display)
- Server-side verify reference: [https://developers.google.com/recaptcha/docs/verify](https://developers.google.com/recaptcha/docs/verify)
- Domain validation notes: [https://developers.google.com/recaptcha/docs/domain_validation](https://developers.google.com/recaptcha/docs/domain_validation)

### Update allowed domains

1. Open the [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin).
2. Select the key pair used by this project.
3. Update the **Domains** list and save.
4. Retest the submit flow on the target host.

Recommended local/dev entries: `noyonaqa.local`, `localhost`, `127.0.0.1`.

### Interpreting v3 score

- Closer to `1.0` = more likely human; closer to `0.0` = more likely bot.
- Common starting threshold is `0.5`; tune against real traffic (Analytics view per action: `contact`, `login`, `register`, `newsletter_subscribe`).

---

## Testing checklist

For each form, with reCAPTCHA configured:

- **v2:** the checkbox renders; solving it then submitting succeeds; submitting without solving is blocked server-side.
- **v3:** no checkbox; the request payload includes a non-empty `g-recaptcha-response`; submission succeeds for normal users.
- **Tampering:** a direct POST with a missing/garbage token is rejected (captcha error / safe redirect).
- **Switch test:** flip a form in the map (`v2` ↔ `v3`), reload, and confirm the UI and verification both follow.

Expected failure markers:

- Newsletter: `newsletter_error=captcha_failed`
- Register: `register_error=captcha_failed`
- Contact: `cf_error=captcha_failed`
- Login: WooCommerce login error "Captcha verification failed. Please try again."
