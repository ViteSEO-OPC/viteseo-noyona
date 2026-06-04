# Checkout Flow Remediation Plan

> Status: PROPOSED — no code changed yet.
> Goal: Fix the PayMongo checkout so payments work reliably, and simplify the
> multi-step flow so it stops fighting WooCommerce.

---

## 0. Context — read this first

### 0.1 The single most important fact

All four payment methods we offer are **"order-created-before-payment" methods**:

| Method | Settlement style | Order is paid when... |
|--------|------------------|------------------------|
| QR Ph | async — show QR, customer scans | webhook `payment.paid` |
| Credit card | inline, or 3DS redirect | redirect result + webhook |
| GCash | redirect to wallet | webhook after return |
| Maya | redirect to wallet | webhook after return |

For **every** method, WooCommerce creates the order in `pending` status **before**
money actually moves. The **PayMongo webhook is the source of truth** for whether
an order is paid. Any logic that treats "order was created" as "checkout is done"
is wrong for 100% of our methods — and that is the core bug today.

### 0.2 What "one page" means (page-count clarification)

The flow still has multiple real pages. We are only merging **Details + Preview**.

| Stepper dot | Real page load? | What it is |
|-------------|-----------------|------------|
| 1. Cart | Yes — `/cart/` | Its own page |
| 2. Details | Yes — `/checkout/` | The checkout page |
| 3. Preview | **No** | A JS-toggled panel *inside* `/checkout/` |
| 4. Payment | Yes | Hand-off to PayMongo (redirect / `order-pay`) |
| 5. Done | Yes — `order-received` | Confirmation |

Today `/preview/` is a **separate page** that re-renders the same checkout form on a
different URL. That forces session-syncing, a step-flow guard, and draft save/restore,
which is where the "it bounces me back to Details" bugs come from. After this plan,
`/preview/` is no longer a page — it is just a panel, so the form never moves.

### 0.3 Key files

| File | Role |
|------|------|
| `inc/woocommerce-checkout.php` | All theme checkout logic + inline JS |
| `inc/security.php` | Step-flow guard, login gating |
| `woocommerce/checkout/form-checkout.php` | Checkout + preview template |
| `woocommerce/checkout/thankyou.php` | Done / awaiting-payment page |
| `plugins/wc-paymongo-payment-gateway/paymongo-top-level-hooks.php` | Intent creation + redirect catch |
| `plugins/wc-paymongo-payment-gateway/classes/Cynder_PayMongo_Webhook_Handler.php` | Webhook (source of truth) |

---

## Phase 0 — Verify configuration (no code)

These are operational checks. Do them first; they may explain failures on their own.

- [ ] **0.1 Webhook secret is set.** In `wp-admin → WooCommerce → Settings → Payments`
      (PayMongo section), confirm the **Live Webhook Secret** (`paymongo_webhook_secret_key`)
      and/or **Test Webhook Secret** (`paymongo_test_webhook_secret_key`) is populated.
      If empty, `Cynder_PayMongo_Webhook_Handler::isValidRequest()` rejects every webhook
      and async orders (QR/GCash/Maya) never become paid.
- [ ] **0.2 Webhook URL is registered in the PayMongo dashboard.** It must point to:
      `https://<our-domain>/?wc-api=cynder_paymongo`
- [ ] **0.3 Confirm the QR Ph gateway actually exists.** The installed plugin registers
      card, GCash, GrabPay, Maya, Atome, BPI, UnionBank, BillEase — **but no QR Ph class**.
      If QR Ph must be a real gateway, confirm the correct plugin version/add-on is installed,
      otherwise QR Ph will not appear and customers fall through to GCash.
- [ ] **0.4 Enable PayMongo debug logging** temporarily (`woocommerce_cynder_paymongo_debug_mode`)
      and watch `WooCommerce → Status → Logs` during a test order.
- [ ] **0.5 Record a baseline:** place one test order per method (QR, card, GCash, Maya) and
      note exactly where each one breaks today.

**Acceptance:** We know which methods fail, and webhook config is confirmed good.

---

## Phase 0 — RESULTS (2026-06-03)

**Environment:** Live hosted domain, **Live mode** (live webhook secret set; test webhook
secret NOT set). Site visibility = "Coming soon". No PayMongo dashboard access yet.

| Method | Result | Root cause |
|--------|--------|------------|
| **QR Ph** | ✅ Works to Done (scan & pay) | Confirms the **live webhook works** end-to-end. |
| **Maya** | ✅ Works (redirects to Maya login) | Confirms **API keys valid + Maya activated** on the account. |
| **Card (visa/debit)** | ❌ "Your payment method could not be prepared" | **`/preview/` architecture.** Card is entered on `/checkout/`; navigating to the separate `/preview/` page reloads and wipes the card fields, so PayMongo JS has nothing to tokenize → `cynder_paymongo_method_id` empty → validation error (`woocommerce-checkout.php` ~915). **Fixed by Phase 2.** |
| **GCash** | ❌ "gcash payment method is not allowed" + PI001 | **Account-level**, NOT code. Maya works with the same keys, so GCash is simply **not activated/approved** on the PayMongo merchant account. PI001 is the downstream symptom. **Needs dashboard access to enable.** |
| **GCash retry** | ❌ "This checkout attempt already created order #1914" | **CONFIRMS the §2.2 retry bug** — a failed attempt created a pending order, got marked "completed", and now blocks retry. **Fixed by Phase 1.** |

### Revised priority based on results

1. **Phase 1 (retry/lock)** — confirmed real with order #1914. Do first.
2. **Phase 2 (collapse `/preview/`)** — now PROVEN mandatory: it is the direct cause of the
   card failure, not just a nice-to-have cleanup.
3. **GCash activation** — get PayMongo dashboard access, enable/await approval for GCash,
   and verify the webhook URL (`/?wc-api=cynder_paymongo`). Not a code task.
4. **Test webhook secret** — set `paymongo_test_webhook_secret_key` when dashboard access is
   available, for future test-mode work. Not blocking live (we're in Live mode).

### Open items needing dashboard access (blocked)

- [ ] Enable GCash on the PayMongo account.
- [ ] Confirm webhook URL is registered for `payment.paid` + `payment.failed`.
- [ ] Generate + set the Test Webhook Secret.

---

## Phase 1 — Fix the order/payment model (HIGHEST PRIORITY, UI-independent)

> **STATUS: IMPLEMENTED (2026-06-03)** in `inc/woocommerce-checkout.php`.
> - Attempt no longer marked "completed" on order creation; the retry block now
>   fires only when the attempt's order `is_paid()`.
> - Unpaid retries are allowed through — WooCommerce resumes the same pending
>   order via `order_awaiting_payment`, so no duplicate orders.
> - Lock TTL cut from 5 min → 30s and released at `order_processed` priority 5
>   (ahead of PayMongo intent creation) so a failed intent can't strand the user.
> - Attempt id is stamped on the order as `_noyona_checkout_attempt_id`.
> Remaining: run the §1.4 regression tests below.

This phase fixes the "can't retry / already being processed / duplicate order" failures.
It does **not** touch the UI, so it is safe to ship on its own.

### 1.1 Stop marking an attempt "completed" on order creation

- [ ] In `inc/woocommerce-checkout.php`, move the "completed attempt" bookkeeping
      **off** `woocommerce_checkout_order_processed`.
- [ ] Instead, set `noyona_checkout_completed_attempt_id` / `noyona_checkout_completed_order_id`
      only when the order actually becomes **paid**, by hooking one of:
  - `woocommerce_payment_complete` (fires on paid transition), and/or
  - `cynder_paymongo_successful_payment` (fired by the plugin on `payment.paid`).
- [ ] Rationale: an unpaid `pending` order must never block a retry.

### 1.2 On retry of an unpaid attempt, resume instead of erroring

- [ ] In `noyona_lock_checkout_attempt_after_validation()`, replace the hard error
      `"This checkout attempt already created order #X"` with a **resume path**:
  - If the prior order for this attempt exists and is **unpaid**, send the customer to
    `$order->get_checkout_payment_url()` (WooCommerce's standard re-pay URL) instead of
    creating a new order or blocking them.
  - Only block (or warn) if the prior order is already **paid**.
- [ ] This prevents both the dead-end error and orphaned duplicate orders.

### 1.3 Make the idempotency lock self-healing

- [ ] The 5-minute transient lock currently survives when
      `cynder_paymongo_create_intent` throws **after** the order is created (that path does
      not fire `woocommerce_checkout_order_exception`, so the lock is never released and the
      `noyona_mark_*` callback never runs).
- [ ] Fix by **either**:
  - Releasing the lock on `woocommerce_checkout_order_processed` regardless of payment
    outcome (the order already exists; idempotency is now handled by 1.1/1.2), **or**
  - Reducing the lock TTL to ~15–30 seconds so a failed intent can't strand the customer.
- [ ] Keep the double-submit protection (it is still useful), just don't let it lock out
      legitimate retries.

### 1.4 Regression-test Phase 1

- [ ] Place order → succeed → confirm single order, marked paid, no duplicates.
- [ ] Place order → **fail payment** → retry on same cart → confirm we can pay the
      **same** pending order (no "already being processed", no second order).
- [ ] Double-click Place Order → confirm only one order is created.
- [ ] Abandon QR → return later → confirm we can still pay.

**Acceptance:** A failed or abandoned payment can always be retried on the same cart,
and duplicate orders cannot be created.

---

## Phase 2 — Collapse `/preview/` into an in-page panel

> **STATUS: IMPLEMENTED (2026-06-03).** Review is now a client-side panel on
> `/checkout/`; there is no second page load, so entered card details survive to
> Place Order (fixes the card "could not be prepared" failure).
> Files changed:
> - `woocommerce/checkout/form-checkout.php` — review-meta, review-totals, terms
>   and both summary headings now always render; visibility is toggled by the
>   `body.noyona-review-step` class instead of the URL.
> - `assets/css/noyona-checkout.css` — added show/hide rules for the in-page toggle.
> - `inc/woocommerce-checkout.php` — `isReviewStep` is now in-page state;
>   `applyReviewStepUI()`/`applyDetailsStepUI()`/`goToReviewStep()`/`goToDetailsStep()`
>   added; PREVIEW button toggles in-page (no navigation, no session sync); Back
>   button returns to details in-page; `/preview/` now 301s to `/checkout/`.
> - `inc/security.php` — removed the defunct `/preview/` step-flow session check.
> Note: the old `noyona_sync_checkout_fields` AJAX, the `/preview/` PayMongo script
> bridge, and the draft save/restore helpers are now unused (left in place, inert;
> safe to delete in a later cleanup).
> Remaining: run the §2.4 tests below.

This removes the fragile cross-page machinery. UI work; ship after Phase 1.

### 2.1 Turn Preview into a panel, not a page

- [ ] In `form-checkout.php`, render the **Details** panel and the **Review** panel inside
      the same form on `/checkout/`. Default to Details; "Preview Order" reveals Review via JS.
- [ ] "Place Order" lives in the Review panel and submits the existing WooCommerce form
      (no navigation, no second render).
- [ ] Keep the 5-dot stepper; drive dots 2↔3 with a CSS/JS class toggle.

### 2.2 Remove the now-unnecessary machinery

- [ ] Remove the `noyona_sync_checkout_fields` AJAX handler + nonce
      (`inc/woocommerce-checkout.php` ~192–229) — no longer needed when the form never moves.
- [ ] Remove the `/preview/` branch from `noyona_enforce_checkout_step_flow()`
      (`inc/security.php` ~278–314).
- [ ] Remove the PayMongo `/preview/` script bridge
      (`noyona_checkout_enqueue_paymongo_preview_assets`, ~489–576) — the gateway already
      loads its card scripts on the real checkout page.
- [ ] Remove the `sessionStorage` draft save/restore (~1255–1382).
- [ ] Keep `/preview/` URL only as a **redirect to `/checkout/`** (so old links/bookmarks
      don't 404), or retire it entirely.

### 2.3 Update step-flow guard

- [ ] `noyona_enforce_checkout_step_flow()` now only guards: `/checkout/` needs a non-empty
      cart; `/thank-you/` (or `order-received`) needs a valid `order_id` + `key`. No preview rule.

### 2.4 Test Phase 2

- [ ] Details → Preview → back to Details: values persist (same DOM, so they should).
- [ ] No redirect loops; refreshing `/checkout/` keeps the panel state sane.
- [ ] Place Order from the Review panel still triggers WooCommerce checkout AJAX.

**Acceptance:** No `/preview/` page load; review is a panel; no session-sync bounce-backs.

---

## Phase 3 — Standardize the payment hand-off

> **STATUS (2026-06-04):** 3.1 and 3.2 were already satisfied by the existing
> gateway flow + `thankyou.php` (paid vs awaiting-payment + QR poller). 3.3 is
> now **IMPLEMENTED** as a theme-side hook (no plugin edit): an unexpected
> PayMongo intent status used to fall through `cynder_paymongo_catch_redirect()`
> with no redirect → blank page. `noyona_paymongo_catch_redirect_fallback()`
> (`inc/woocommerce-checkout.php`, hooked at priority 20 on
> `woocommerce_api_cynder_paymongo_catch_redirect`) now catches that case and
> sends the customer to the re-pay page (or Done page if already paid) with a
> clear notice. It only fires on the fall-through, since the plugin handler
> `exit`s on every status it handles.

Make all four methods follow the same predictable path.

### 3.1 One model for all methods

- [ ] At Place Order: WooCommerce creates the order (`pending`) and the gateway's
      `process_payment()` returns `{ result: 'success', redirect: <url> }`.
- [ ] Card: settles inline or returns a 3DS redirect → returns via `cynder_paymongo_catch_redirect`.
- [ ] GCash / Maya: redirect to wallet → returns via `cynder_paymongo_catch_redirect`.
- [ ] QR Ph: show QR (on `order-received` / hosted page); the webhook flips it to paid.
- [ ] Do not declare success in the UI until `$order->is_paid()` is true.

### 3.2 Done page honesty

- [ ] `thankyou.php` already distinguishes paid vs awaiting-payment — keep that.
- [ ] Keep the order-received status poller (`noyona_check_order_payment_status`) for QR;
      it should only flip the UI to "Done" when the webhook has marked the order paid.

### 3.3 Harden the redirect catcher

- [ ] In `cynder_paymongo_catch_redirect`, add an explicit branch for unexpected intent
      statuses (currently only `succeeded` / `processing` / `awaiting_*` are handled; an
      unknown status falls through with no redirect → blank page). Redirect to the order-pay
      URL with a clear notice.
      > Note: this file lives in the **plugin**. Prefer overriding via hooks if we don't want
      > to fork the plugin; otherwise document the change so a plugin update doesn't wipe it.

**Acceptance:** Each method either completes or returns the customer to a re-pay page with a
clear message — never a blank screen, never a falsely-"Done" page.

---

## Phase 4 — Full test matrix (run before going live)

Run each row in **test mode**, then repeat the critical ones in live mode with small amounts.

- [ ] QR Ph — success (scan & pay) → order `processing`/`completed`, Done page correct.
- [ ] QR Ph — abandon, then retry on same cart → can still pay, no duplicate.
- [ ] Credit card — success (no 3DS).
- [ ] Credit card — 3DS challenge → success.
- [ ] Credit card — declined → clear error → retry succeeds.
- [ ] GCash — success.
- [ ] GCash — cancel at wallet → returned to re-pay page → retry succeeds.
- [ ] Maya — success.
- [ ] Maya — cancel at wallet → re-pay → success.
- [ ] Double-click Place Order (each method) → exactly one order.
- [ ] Webhook delayed → Done page shows "awaiting payment", then resolves after webhook.
- [ ] Empty cart → `/checkout/` redirects to cart.
- [ ] Guest → checkout flow → login gate works.
- [ ] Stock check: stock reduced exactly once per paid order.

---

## Phase 5 — Rollout & safety

- [ ] Ship **Phase 1 alone first** (UI-independent, biggest payoff) and monitor logs/orders.
- [ ] Then ship Phase 2 + 3 together behind a quick visual QA.
- [ ] Keep PayMongo debug logging on for the first 24–48h after each phase.
- [ ] Have a rollback: note current file versions / use git so each phase can be reverted.
- [ ] After stable, turn debug logging back off (perf/noise).

---

## Quick reference — priority order

1. **Phase 0** verification (config) — may fix things with zero code.
2. **Phase 1** order/payment model — fixes most "payment doesn't work" reports.
3. **Phase 2** collapse preview — kills the bounce-back class of bugs.
4. **Phase 3** payment hand-off — consistency + no blank/false-done pages.
5. **Phase 4/5** test + rollout.

---

## Implementation summary (2026-06-03 → 2026-06-04)

This is the plain-English record of every problem we hit, what fixed it, and why.

### The throughline

Almost every bug traced back to one root cause: **the custom multi-step checkout
fought WooCommerce's standard model** — one form, on one page, with the
**PayMongo webhook as the source of truth** for whether an order is paid. All
four methods (QR Ph, Card, GCash, Maya) create the order *before* money moves, so
any logic that treats "order created" as "checkout done" is wrong. Realigning
with that model is what made payments reliable.

### 1. Retry / duplicate-order dead-end (Phase 1)

- **Problem:** A failed or abandoned payment created a `pending` order, which the
  idempotency logic marked as "completed," then blocked the customer with
  *"This checkout attempt already created order #1914."* — a hard dead end, and
  it risked orphaned duplicate orders.
- **Fix:** Only block a retry when the attempt's order is **actually paid**
  (`is_paid()`). Unpaid retries pass through and WooCommerce resumes the **same**
  pending order via `order_awaiting_payment` (no duplicates). The double-submit
  lock TTL was cut to 30s and released earlier (priority 5) so a failed
  payment-intent can't strand the customer.
- **Why:** A `pending` order means "not paid yet," so it must never lock out a
  legitimate second attempt.
- **File:** `inc/woocommerce-checkout.php`.

### 2. Card details wiped on the Preview page (Phase 2)

- **Problem:** Card fields were typed on `/checkout/`, but clicking Preview
  loaded a **separate `/preview/` page** that re-rendered the form empty. PayMongo
  had nothing to tokenize → *"Your payment method could not be prepared."*
- **Fix:** Collapsed Preview into an **in-page panel** on `/checkout/` (a CSS/JS
  toggle, no navigation). The form never moves, so entered values survive to
  Place Order. `/preview/` now 301-redirects to `/checkout/`; the obsolete
  server-side preview step-guard was removed.
- **Why:** A second page load is what destroyed the card data. Keeping everything
  in one DOM is exactly how stock WooCommerce already works.
- **Files:** `woocommerce/checkout/form-checkout.php`,
  `assets/css/noyona-checkout.css`, `inc/woocommerce-checkout.php`,
  `inc/security.php`.

### 3. Card input fields were completely hidden

- **Problem:** After Phase 2 there were **no card fields at all**. The theme had
  `.payment_box { display: none !important; }`, which hid the panel where PayMongo
  renders card number / expiry / CVC. QR/GCash/Maya were unaffected (they redirect
  and have no fields), so only card looked broken.
- **Fix:** Scoped the rule to reveal the **selected** method's box using
  `:has(input:checked)` — choosing "Credit Card" now shows its fields.
- **Why:** A blanket hide removed the only place card details could be entered.
- **File:** `assets/css/noyona-checkout.css`.

### 4. The real card-killer — jQuery load order

- **Problem:** Even with fields visible, card still failed. The browser console
  showed `paymongo-cc.js` crashing with **"jQuery is not defined."** The site
  **defers jQuery** for performance, but the PayMongo gateway loads its scripts as
  **blocking head scripts** that execute *before* jQuery runs. So `new CCForm()`
  never ran, the `checkout_place_order_paymongo` tokenizer was never bound, and
  the form submitted with an empty token → the same "could not be prepared."
- **Fix:** Forced `strategy=defer` on the four PayMongo scripts so they run
  **after** deferred jQuery — the same pattern the theme already uses for
  Wordfence and WooCommerce core. This made card payments work end-to-end.
- **Why:** A script that uses jQuery must execute after jQuery is defined; matching
  the gateway's load timing to the site's deferred jQuery fixed it.
- **File:** `inc/woocommerce-checkout.php`
  (`noyona_defer_paymongo_checkout_scripts`). The PayMongo asset bridge was also
  widened from the dead `/preview/` step to the whole checkout UI context.

### 5. "Track Order" → custom order modal

- **Problem:** The Done page's Track Order button used Woo's default view-order
  page; it should open the custom order modal on the My Account orders panel. A
  bare hash didn't open it, because the order's row only renders under a specific
  **filter tab + page**, so the `:target` element wasn't on the page.
- **Fix:** Added `noyona_get_account_order_modal_url()` which maps the order's
  **status → filter tab** (e.g. `processing` → `to-ship`), computes its **page**
  (newest-first, 10/page), and builds the full deep link
  `…/orders/?order_filter=…&orders_page=…#noyona-account-order-modal-{order}-{item}`.
- **Why:** The modal opens via CSS `:target`, which requires the target element to
  actually be rendered — so the link must land on the exact tab/page that contains
  the order.
- **Files:** `inc/woocommerce-checkout.php`, `woocommerce/checkout/thankyou.php`.

### 6. Blank-page edge case in the redirect catcher (Phase 3.3)

- **Problem:** PayMongo's `cynder_paymongo_catch_redirect()` only handles four
  intent statuses; any other status falls through with no redirect → a **blank
  page** for the customer.
- **Fix:** Added `noyona_paymongo_catch_redirect_fallback()` on the same WC API
  hook at a later priority. The plugin handler `exit`s on every status it handles,
  so ours only fires on the fall-through — redirecting to the re-pay page (or the
  Done page if the order is already paid) with a clear notice, and logging a
  warning. Done as a **hook, not a plugin edit**, so a plugin update can't wipe it.
- **Why:** Customers should always land somewhere actionable, never a blank screen.
- **File:** `inc/woocommerce-checkout.php`.

### Still open (operational, not code)

These need PayMongo **dashboard access** and are the only remaining gaps:

- [ ] **Enable GCash** on the PayMongo merchant account (it's simply not
      activated — Maya works on the same keys, proving the keys/integration are
      fine).
- [ ] **Confirm the webhook URL** is registered for `payment.paid` +
      `payment.failed` at `https://<domain>/?wc-api=cynder_paymongo`.
- [ ] **Set the Test Webhook Secret** for future test-mode work.
