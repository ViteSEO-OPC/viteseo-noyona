# Child Theme Architecture & Customization Standard

**Project:** Noyona (WooCommerce, child theme `viteseo-noyona`)
**Parent theme:** Twenty Twenty-Five (`twentytwentyfive`)
**Audience:** Engineers continuing work on the Noyona site.

This document explains the rules we follow when changing how the Noyona site looks
and behaves. The goal is simple: any developer who joins this project should be
able to make safe changes without breaking other features and without making
WordPress or WooCommerce upgrades dangerous.

---

## 1. The child theme principle

Everything custom about this site is layered on top of WordPress and WooCommerce
through a **child theme**. That means:

- **WordPress core is never edited.** Files under `wp-includes/`, `wp-admin/`,
  and the rest of the core tree are read-only from our perspective. They are
  rewritten by every WordPress upgrade, so any local change would silently
  disappear.
- **WooCommerce plugin files are never edited.** Files under
  `wp-content/plugins/woocommerce/` are similarly volatile. They are rewritten by
  every WC upgrade.
- **The parent theme is never edited directly.** Twenty Twenty-Five is a
  WordPress.org theme; it receives upgrades. Local edits would be lost.
- **Everything we customize lives in this child theme.** The directory you are
  reading is the entire customization surface for the site. If a change is not
  in this directory, the site does not own it.

The practical consequence is that this child theme **must absorb every change
the site needs**. When a feature, fix, or style adjustment cannot be expressed
using WordPress and WooCommerce hooks, filters, blocks, and templates, the
escape valves are (a) custom code in `inc/`, (b) custom CSS in `assets/css/`,
and (c) WooCommerce template overrides under `woocommerce/` — in that order of
preference.

---

## 2. File organization

### `functions.php` — main loader

`functions.php` is a thin loader. It does **not** contain feature code. Its job
is to require the partials under `inc/` in a predictable order and guard each
require with `is_readable()` so a missing or in-flight partial cannot fatal the
site.

If you find yourself adding logic directly to `functions.php`, that's a signal
to instead create or extend a file under `inc/`.

### `inc/` — feature partials

Each file in `inc/` owns one concern. The split today is:

| File | Concern |
| --- | --- |
| `theme-setup.php` | WooCommerce theme support, template-part safeguards, block registration, login-form UI hooks, admin-bar handling |
| `enqueue.php` | Frontend CSS/JS enqueuing for the whole site |
| `helpers.php` | Pure utility functions (image rendering, URL builders, price-range helpers, env detection) — used by every other partial |
| `rewrites.php` | URL rewrite rules, term-link rewrites, rewrite flushes, redirects, 404 enforcement |
| `shortcodes.php` | `[product_gatherer]`, `[noyona_register_form]`, `[noyona_account_page]` and their associated form handlers, data helpers, and markup cleanup |
| `ajax.php` | `wp_ajax_*` handlers |
| `admin.php` | Admin-area customizations (e.g. Rank Math column styling) |
| `contact-form.php` | The contact form submit handler and its validators |
| `seo.php` | robots.txt, sitemap toggle, robots meta, document titles, meta descriptions |
| `security.php` | wp-login → branded login redirect, account-gate enforcement, cache-control hardening |
| `performance.php` | Resource hints, preloads, script-loader fixes, asset trimming |
| `woocommerce-general.php` | Generic Woo customizations: shop archive title, product-collection block renderers, mini-cart filters |
| `woocommerce-cart.php` | Cart-specific behavior |
| `woocommerce-checkout.php` | Checkout-specific behavior |
| `woocommerce-pdp.php` | Single product page (PDP) behavior |
| `woocommerce-shipping.php` | Shipping zone / weight matrix setup |

Each `inc/` file starts with `<?php`, a short file-purpose docblock, and an
`ABSPATH` guard. No file has a closing `?>` tag. Each file groups related code
under `/* ----- Section title ----- */` banner comments.

### `assets/css/` — stylesheets

CSS is split by surface (`single-product.css`, `header.css`, `footer.css`,
`fonts.css`, `product-gatherer.css`, etc.) and enqueued from
`inc/enqueue.php`. When you add styling for a feature, prefer extending the
file for that surface over creating a new one.

### `assets/js/` — frontend scripts

Same convention as CSS: one file per surface (`header.js`, `single-product.js`,
`account-modals.js`, etc.), enqueued from `inc/enqueue.php`. Scripts use
`strategy=defer` where they don't need to block parse. Inline scripts that
require runtime data should use `wp_localize_script()` rather than echoing
JSON into the page.

### `templates/` — block theme templates

This is a block theme. Templates here (`single-product.html`,
`archive-product.html`, `page-home.html`, etc.) are pure HTML markup composed
of Gutenberg block comments. They override the parent theme's templates of the
same name. **Block templates are markup, not logic** — when you need
conditional behavior on a template, hook into a WordPress or WooCommerce action
from `inc/` and emit the markup there. Do not add PHP to `templates/`.

### `parts/` — reusable template parts

Header, footer, mini-cart, product add-to-cart variants, etc. Templates
include these by slug (`<!-- wp:template-part {"slug":"header"} /-->`).

### `blocks/` — custom Gutenberg blocks

One subdirectory per block. Each block has `block.json`, an editor/view stub,
and a `render.php` for server-side rendering when needed. Blocks live here
when they encapsulate a reusable composition (hero banner, FAQ list, PDP
trust badges, etc.). Use a custom block instead of a shortcode when the
content needs to be authored in the block editor.

### `woocommerce/` — WooCommerce template overrides

**Only used when a hook or filter cannot do the job.** Today this directory
contains overrides for cart, checkout, and one myaccount template:

```
woocommerce/cart/cart.php
woocommerce/cart/cart-totals.php
woocommerce/checkout/form-checkout.php
woocommerce/checkout/thankyou.php
woocommerce/myaccount/form-lost-password.php
```

That is the entire list, and it should stay that small. Any new override here
must come with a written justification (a code comment at the top of the file
explaining why hooks were insufficient) and should be reviewed against the
upstream WooCommerce template on the next WC version bump (see Maintenance
Rules below).

**A note on `templates/` vs `woocommerce/`** — the two directories operate at
different layers and do not collide. `templates/` is the block-theme layer: it
defines page compositions in Gutenberg block markup (e.g.
`templates/single-product.html` says "lay out the PDP using this header, this
columns block, this product-image-gallery block, this product-tabs block,
etc."). Several of those blocks — for example `wp:woocommerce/product-image-gallery`
— are then rendered server-side by WooCommerce and internally invoke
WooCommerce's *classic* template files. The `woocommerce/` directory is where
we override those *classic* template files when needed. In short: adding or
editing a file in `templates/` reshapes the page layout in block-editor terms;
adding a file under `woocommerce/` rewrites what WooCommerce itself outputs
inside one of its blocks or classic hook chains.

### `docs/` — engineering notes

Long-form architecture decisions and operational runbooks (this file lives
here, alongside `order-tracking-approach.md` and `refund-process.md`).

---

## 3. WooCommerce customization standard

When you need to change WooCommerce behavior, follow this preference order.
Start at the top and only move down when the previous level genuinely cannot
solve the problem:

**1. WooCommerce hooks and filters (preferred).**
WooCommerce ships hundreds of `do_action()` and `apply_filters()` calls
specifically so themes can customize behavior without touching plugin files.
For example, the PDP "Sale!" badge is registered on
`woocommerce_before_single_product_summary`, the my-account login is filtered
through `woocommerce_login_redirect`, the checkout fields go through
`woocommerce_checkout_fields`, and so on. Find the right hook first; it almost
always exists.

**2. Child-theme PHP in `inc/`.**
Once you know the hook, the callback goes in the appropriate `inc/` partial
(`woocommerce-cart.php`, `woocommerce-checkout.php`, `woocommerce-pdp.php`, or
the generic `woocommerce-general.php`). Keep the callback small. Reuse
helpers from `inc/helpers.php` where available.

**3. CSS/JS in `assets/`.**
Anything that's purely presentational or behavioral on the client side belongs
in `assets/css/{surface}.css` or `assets/js/{surface}.js`. Resist the urge to
echo `<style>` or `<script>` tags inline from PHP; localize data with
`wp_localize_script()` and let the script file handle the rest.

**4. WooCommerce template overrides (last resort).**
Only when hooks, filters, and CSS cannot express what's needed. To override a
template, copy the upstream file from
`wp-content/plugins/woocommerce/templates/{path}` to
`wp-content/themes/viteseo-noyona/woocommerce/{path}` and edit the copy.
Document why the override exists and which WC version it was copied from.

**Plugin files are off-limits.** No matter how tempting, do not edit anything
under `wp-content/plugins/woocommerce/`. The upgrade cycle is the enemy of
that approach.

---

## 4. Worked example: the PDP "BEST SELLER" badge

The most recent PDP change is a good template for "how the standard plays out
in practice." The requirement was: hide the default "Sale!" flash on the
main product image, and show a custom **BEST SELLER** badge instead when the
product is marked Featured in WooCommerce.

The change was implemented entirely in two files:

- `inc/woocommerce-pdp.php` — two small functions: one removes WooCommerce's
  `woocommerce_show_product_sale_flash` callback from
  `woocommerce_before_single_product_summary` (scoped to PDPs via `is_product()`
  so non-product pages are untouched), and the other renders the BEST SELLER
  pill on the same hook at the same priority when `$product->is_featured()`
  returns true.
- `assets/css/single-product.css` — styling for the new
  `.noyona-pdp-best-seller-badge` class plus a defensive PDP-scoped
  `display: none` for `.onsale` inside the gallery wrapper.

This change is a good example of the standard because:

1. **The sale-badge removal is scoped to single product pages only.** Archive
   pages, shop pages, category landing pages, search results, and the cart all
   render their sale badges through completely different paths (the
   `woocommerce/product-sale-badge` Gutenberg block on archive templates, and
   `woocommerce_show_product_loop_sale_flash` on related-products loops). None
   of them were touched.
2. **The BEST SELLER badge uses the WooCommerce Featured flag** via
   `$product->is_featured()`. That API reads the `featured` term from the
   `product_visibility` taxonomy, which is the canonical, forward-compatible
   way to check featured status. We do not read the deprecated `_featured`
   post meta.
3. **No WooCommerce template was overridden, and hooks were the right choice
   here for concrete reasons.** The native "Sale!" flash is itself registered
   on a WooCommerce action (`woocommerce_before_single_product_summary` at
   priority 10). Removing it is therefore a one-line `remove_action()` from
   PHP — there is no template file to edit. Adding our BEST SELLER badge in
   exactly the same slot is another `add_action()` on the same hook. The
   alternative would have been to copy
   `wp-content/plugins/woocommerce/templates/single-product/sale-flash.php` to
   our `woocommerce/single-product/sale-flash.php` and edit the copy. That
   approach has three drawbacks: (a) `sale-flash.php` is also rendered inside
   product loops, so editing it would silently affect the related-products
   carousel on the PDP and any other shop-loop output that calls the same
   template, breaking our "PDP main image only" scope; (b) it would create a
   new file with a `@version` header we'd have to track against every
   WooCommerce upgrade; and (c) it would couple our markup to whatever
   WooCommerce ships in that template file, while hooks keep us decoupled.
   Hooks gave us a tighter scope and zero version-tracking debt.
4. **Nothing outside the PDP main image area was changed** — no archive, no
   shop, no category, no search, no landing page, no cart, no checkout, no
   account, no related-products carousel.
5. **The change was additive.** The two new functions in
   `inc/woocommerce-pdp.php` were appended to the end of the file, below the
   existing `noyona_pdp_enqueue_assets` function. The CSS rules were appended
   to the end of `assets/css/single-product.css`. Rolling back means deleting
   those appended blocks — no other code was modified.

**Concrete rollback for this change.** If the BEST SELLER feature ever needs
to be reverted, delete the two new functions and their hook registrations
from the end of `inc/woocommerce-pdp.php` (everything from the
`/** PDP main image …` docblock down through `noyona_pdp_render_best_seller_badge`),
and delete the appended PDP-badge CSS block at the end of
`assets/css/single-product.css` (from the `=========== PDP main image badge ...`
banner comment through the closing of the `@media (max-width: 781px)` rule).
No other file in the repo participates. Because the change was additive, the
revert is a pure deletion — there is no prior version of any existing
function to restore.

If you ever wonder "is my change following the standard?", check whether it
shares these five properties. If it doesn't, you're probably reaching too far.

---

## 5. Maintenance rules

These rules exist so the next developer can read the codebase without surprise.

**Keep changes additive where possible.** Adding a new function, a new hook,
a new CSS rule is safer than rewriting an existing one because it is trivially
reversible. When you do need to modify existing logic, do so in place rather
than duplicating, and explain the change in the commit message.

**Don't refactor unrelated code while shipping a feature.** Refactors and
features should be separate commits and ideally separate pull requests. The
PDP badge work, for example, did not touch any unrelated function in
`inc/woocommerce-pdp.php`.

**Comment WooCommerce hook registrations.** Every `add_action` /
`add_filter` whose purpose isn't obvious from the callback name deserves a
short comment explaining *why* it exists. The "why" is far more useful than
the "what" — the code already says the what.

**Escape on output, sanitize on input, follow WordPress coding standards.**
Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` for outputs.
Use `sanitize_text_field()`, `absint()`, `sanitize_email()`, `wp_unslash()`
for inputs. Always check capability and nonce on form handlers — every
`admin_post_*` callback in this project does both, and so should yours.

**Keep PDP logic separate from archive/product-card logic.** The PDP renders
images through the `woocommerce/product-image-gallery` block, which hooks
into `woocommerce_before_single_product_summary`. Archive cards and the
custom unified card layout (`noyona_render_product_card` in
`inc/helpers.php`) render through different paths. A change that affects one
should not implicitly affect the other.

**Before adding a WooCommerce template override, prove that hooks aren't
enough.** Search WooCommerce's source for the relevant `do_action` and
`apply_filters` calls in the template you want to change. If a suitable
hook exists, use it. If you must override, leave a comment at the top of
the new override file explaining what couldn't be expressed via hooks.

**Track WooCommerce template version compatibility.** Every WooCommerce
template (the upstream files in `wp-content/plugins/woocommerce/templates/`)
carries a `@version` header. When you copy a template into our `woocommerce/`
directory, that version becomes a contract: it says "I have reviewed the
upstream template at this version." When WooCommerce updates and the
upstream version bumps, WC's admin status page flags our override as
outdated. The maintenance task is to diff the upstream changes against our
override, merge any non-conflicting changes upstream, and bump the
`@version` header on our copy. Don't ignore that flag — it indicates our
override may be diverging from current WooCommerce behavior.

---

## 6. Where does a new change go?

When you have a new WooCommerce-related change to make, work through these
questions:

1. **What WordPress / WooCommerce hook fires at the right moment?** If you can
   find one, your change is PHP in the appropriate `inc/` partial. PDP →
   `inc/woocommerce-pdp.php`. Cart → `inc/woocommerce-cart.php`. Checkout →
   `inc/woocommerce-checkout.php`. Shipping → `inc/woocommerce-shipping.php`.
   Anything that cuts across these (shop archive, mini-cart filters,
   render_block tweaks for product collections) → `inc/woocommerce-general.php`.
2. **Is it purely visual?** If a CSS rule expresses the change, add it to the
   right file in `assets/css/`. Don't reach for PHP to inject styles inline.
3. **Does it need browser behavior?** Add JS in the right file in `assets/js/`,
   enqueued via `inc/enqueue.php`. Localize PHP data using
   `wp_localize_script()`.
4. **Is the change a brand-new shortcode?** Add it to `inc/shortcodes.php`
   (and its handler/cleanup helpers alongside it, if any).
5. **Is the change a brand-new reusable composition for the block editor?**
   Add a new block under `blocks/` and register it in
   `woocom_ct_register_blocks()` inside `inc/theme-setup.php`.
6. **Is none of the above sufficient?** Now and only now consider a
   WooCommerce template override under `woocommerce/`. Write a code comment
   at the top of the new file explaining what couldn't be done with hooks and
   what upstream `@version` you copied from.

If you find yourself wanting to edit a file outside this child theme — the
WooCommerce plugin, the parent theme, or WordPress core — stop and reconsider.
There is almost always a hook, filter, or override path that achieves the
goal while keeping the upgrade story intact. The cost of getting this wrong
is silent: things work until the next plugin or core update, and then they
don't.

When in doubt, search the existing codebase for similar work. The patterns
above are repeated dozens of times across `inc/` — finding a sibling
implementation is usually faster than designing one from scratch, and it
keeps the codebase coherent for the next person who reads it.
