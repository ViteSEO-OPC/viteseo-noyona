# Order Tracking: Manual vs API Approach

**Project:** Noyona (WooCommerce, child theme `viteseo-noyona`)
**Tracking plugin:** `noyona-order-tracking` (MU-plugin, currently live)
**Example carriers referenced:** J&T Express PH, LBC Express

---

## 1. Current state

The site ships orders manually. The `noyona-order-tracking` MU-plugin adds:

- Custom WooCommerce order statuses (`to-ship`, `to-receive`, `at-hub`, `rider-assigned`, `out-for-delivery`, `shipped`, `in-transit`).
- A metabox on the order edit screen where admin types each stage + note + timestamp.
- A custom admin page (Orders → Noyona Tracking) with a "quick update" form for batch stage changes.
- A timeline stored as order meta (`_noyona_tracking_events`) that renders on the customer's My Account → Order details page.

There is **no outbound HTTP call to any carrier** — the plugin has zero `wp_remote_*` / cURL calls. Every status line a customer sees was typed by a human.

The plugin also supports **native carrier tracking links**. Per-order meta keys `_noyona_tracking_carrier`, `_noyona_tracking_number`, `_noyona_tracking_url`, `_noyona_tracking_note` are edited from either the order edit screen (Noyona metabox) or the Order Tracking Manager quick-update form. The public tracker URL is auto-built for J&T Express and LBC Express; admin can paste a manual URL to override. A **Track Package** button renders in the customer's My Account order modal whenever tracking data is present.

---

## 2. Manual approach

"Manual" = a staff member is the source of truth. The system never talks to J&T / LBC on its own.

### 2a. Pure manual (what we have right now)

**How it works:**
1. Order moves to *To Ship* after payment.
2. Admin books the package with the courier (physical drop-off or pickup).
3. Admin opens the carrier's own portal (J&T Merchant / LBC dashboard) periodically — e.g. once or twice a day — and reads each order's status.
4. Admin opens the WooCommerce order in WP admin and types that status into the Noyona Tracking metabox ("At Hub", "Rider Assigned", "Out for Delivery", etc.).
5. Customer refreshes their My Account page and sees the updated timeline.

### 2b. Enhanced manual — carrier tracking link (implemented natively)

**How it works:**
1. Same booking step as above.
2. After booking, admin gets the tracking number (AWB) from J&T or LBC.
3. Admin opens the WooCommerce order and uses the **Carrier Tracking** section of the Noyona metabox (or the Quick Update form on WooCommerce → Order Tracking): pick carrier, paste the tracking number, save. The URL auto-builds from the carrier's public tracker format — `https://www.jtexpress.ph/track?billCode=XXXX` for J&T, `https://www.lbcexpress.com/track/?tracking_no=XXXX` for LBC. Admin can paste a manual URL to override.
4. Customer sees a compact pink **Track Package** button in the My Account order modal, directly under the shipping address, with the carrier name and tracking number beside it. Button hides automatically when no tracking data is saved.

**Implementation:** lives entirely inside `noyona-order-tracking` (`class-noyona-order-tracking.php`) + theme modal in `functions.php`. No third-party plugin dependency. See §5 for the concrete state.

**Cost:** Free. Both J&T PH and LBC PH publish public tracking pages that accept the tracking number in the URL — no API key, no contract, no per-shipment fee.

**Caveat:** the customer leaves our site to view live status on the carrier's page. The WooCommerce order status itself (e.g. auto-mark Complete when delivered) still won't advance on its own — a human still has to close the order. Our in-site timeline remains the source of truth; the carrier link is a supplemental convenience.

### Pros — manual

- **Zero integration cost.** No API keys, no contracts, no monthly fee, no vendor lock-in.
- **Works today** for any carrier, even ones without an API (small/local couriers, grab-delivery, Lalamove same-day).
- **Full control of messaging.** Admin can add context ("held at hub for address verification", "reschedule requested") that a generic API feed wouldn't.
- **Simple recovery when a carrier's system is down** — admin can still keep the customer informed from a phone call or SMS from the rider.
- **No engineering time.** The plugin already exists and works.

### Cons — manual

- **Staff time scales linearly with orders.** 50 orders/day across two carriers ≈ 50 × 2-3 status checks each ≈ hours of admin work daily that produces no revenue.
- **Delay between truth and display.** Customer might see "At Hub" for 8 hours when it already moved to "Out for Delivery" because nobody checked.
- **Human error.** Wrong stage, wrong order, typos, missed updates, forgotten orders. Our timeline becomes less trustworthy than the carrier's own page — which defeats the point.
- **No automation hooks.** WooCommerce "Completed" status won't fire when the package is actually delivered, so Completed-order emails, review requests, loyalty points, and refund windows all misalign with reality.
- **Weekends / off-hours go dark.** If nobody updates on Sunday, the customer sees a 2-day silence even if the package moved.
- **Doesn't scale.** Enhanced manual (2b) solves *display* but not the WooCommerce-side automation gap. You still won't know a package was delivered without opening the carrier page.

---

## 3. API approach

"API" = our site asks the carrier (or an aggregator) "what's the status of tracking number X?" and updates the WooCommerce order automatically. No one types anything after the tracking number is entered.

### How it works in practice

**Path A — direct carrier API (J&T / LBC)**

1. Merchant signs an API / integration agreement with J&T PH or LBC. Usually gated: requires business registration, minimum shipment volume, and an account manager. Not self-serve.
2. Carrier issues API credentials (app ID + secret) and API documentation.
3. We build a WordPress service that:
   - On order ship-out, calls the carrier's "create waybill" endpoint (optional) and/or stores the tracking number.
   - Runs a scheduled WP-Cron job (e.g. every 30 min) that loops through open shipments and calls the carrier's "get status" endpoint, or
   - Exposes a webhook URL that the carrier POSTs to when a status changes (preferred — no polling).
4. The response is mapped to our WooCommerce statuses (e.g. carrier's "DELIVERED" → WC `completed`). The timeline meta is updated automatically, the "Delivered" email fires, etc.

**Path B — third-party aggregator (realistic path for PH SMBs)**

Aggregators already have integrations with J&T, LBC, Ninja Van, Flash, etc. You integrate once with them and get all carriers.

Common options:
- **AfterShip** — global, covers J&T PH + LBC. Has an official WooCommerce plugin. Free tier: ~50 shipments/month, paid tiers from ~USD 11/month at the time of writing.
- **Shipmates / Locad / Parcels.ph** — PH-focused aggregators that also handle booking + label printing, not just tracking. Usually per-shipment fee baked into shipping cost.

### Pricing — is it free?

**Short answer: no, not realistically free above very low volume.** The nuance:

| Option | Setup cost | Ongoing cost | Notes |
|---|---|---|---|
| Direct J&T PH API | Free in dollars but gated by contract / volume | Usually no per-call fee, but requires business agreement | Only practical if you have enough volume that J&T assigns you an account manager |
| Direct LBC API | Same as above | Same as above | Same gating |
| AfterShip | Free to USD ~11/mo | Tiered by shipment count | Free tier ≈ 50 shipments/mo. Drop-in WC plugin. Easiest starter. |
| Shipmates / Locad | Free signup | Per-shipment fee (often bundled into label cost) | Also handle booking, not just tracking |

Plus **engineering time** — building the WC ↔ API glue (status mapping, retry logic, webhook endpoint, admin UI), ~2–5 days of dev work depending on path.

### Pros — API

- **No daily admin work** to maintain the timeline. Admin enters the tracking number once; the system takes over.
- **Near-real-time status** for the customer. Webhook-based updates arrive within seconds of the carrier scanning the package.
- **WooCommerce automation works correctly.** Completed status fires when actually delivered, triggering review emails, loyalty, refund windows, reporting.
- **Scales with order volume** without more headcount.
- **Fewer customer "where's my order?" tickets** because the page is always current.
- **Single dashboard if using aggregator** — J&T, LBC, and any future carrier all look the same to our admin.

### Cons — API

- **Costs money** at any realistic volume (see table). Not zero.
- **Contract friction with carriers** for direct integration (Path A). Often not available to new / small merchants.
- **Dev time + maintenance.** APIs change, credentials expire, webhook endpoints need monitoring. Not a one-time build.
- **Vendor lock-in risk** with aggregators — if AfterShip raises prices or changes terms, migration is non-trivial.
- **Failure modes are silent.** If the webhook is down, orders look frozen and nobody notices until customers complain. Requires monitoring + alerts.
- **Carriers occasionally send wrong data.** Still need an admin override for the cases the API gets wrong.
- **Data privacy review** — customer names and addresses get shared with a third party (aggregator). Needs to be covered in the privacy policy.

---

## 4. Side-by-side

| Factor | Manual (current) | Manual + tracking link (2b) | API |
|---|---|---|---|
| Cost | Free | Free | Paid above ~50 shipments/mo |
| Admin work per order | High (multi-update) | Low (enter tracking # once) | Near-zero after setup |
| Status freshness | Hours of lag | Real-time (via carrier site) | Real-time (in our site) |
| Status shown inside our site | Yes | No — customer leaves to carrier page | Yes |
| WC Completed fires automatically | No | No | Yes |
| Works for any carrier | Yes | Yes, if carrier has public tracker | Only carriers the API / aggregator supports |
| Scales to 100+ orders/day | Painful | OK for display, painful for WC automation | Yes |
| Setup effort | Already done | Already done (native plugin fields + customer-modal button) | 2–5 dev days |
| Failure mode | Staff forgets | Staff forgets tracking # | Silent webhook outage |
| Vendor dependency | None | None | Aggregator / carrier |

---

## 5. Recommendation

**Start with enhanced manual (2b), move to API once volume justifies it.**

Reasoning:

1. **Today we don't even have carrier accounts set up.** Direct J&T / LBC API access typically requires an existing merchant relationship and volume — it isn't an option yet. Paying for an aggregator before we have meaningful shipments would mean spending money on idle integration.
2. **The tracking-link approach (2b) is already implemented natively** in the `noyona-order-tracking` plugin — carrier + number meta fields, auto-built URL for J&T / LBC, and the customer-facing Track Package button. No third-party plugin dependency, no monthly fee, admin now has a single place to enter tracking info.
3. **Graduate to API when any of these is true:**
   - We're doing >~50 orders/day and admin time on status updates exceeds ~1 hr/day.
   - Customer support tickets about "where's my order?" become a regular load.
   - We want post-purchase automation (review requests, loyalty points on delivery) to fire at the right time — that needs a real "Delivered" signal.
   - We onboard a second carrier and the manual work doubles.
4. **When we graduate, start with AfterShip** (free tier → USD ~11/mo). Skip direct J&T / LBC API unless carrier reps approach us — the effort/reward is poor for our stage.

**Current status:** enhanced-manual tracking (§2b) is live. Admin enters carrier + tracking number from the Noyona metabox on the order edit screen OR the Quick Update form on the Order Tracking Manager page. The `_noyona_tracking_carrier`, `_noyona_tracking_number`, `_noyona_tracking_url`, and `_noyona_tracking_note` meta keys are the canonical source. Customer sees the Track Package button in the My Account order modal under Shipping Address.

---

## 6. Shipping fee at checkout (manual approach)

Separate but related question: how do we compute the shipping fee the customer pays at checkout so it stays close to the real J&T / LBC cost, without calling any carrier API?

### 6.1 The core tension

- If we hardcode rates, J&T / LBC update their cards eventually and our numbers drift.
- If we call the carrier's API for a live quote, we're back on the API path (cost + contract).
- If we undercharge, margin gets eaten by shipping. If we overcharge, customers bounce at checkout.

### 6.2 Two framings to correct up front

**"Distance" in Philippine courier pricing is not kilometers — it's zones.** J&T and LBC both price by zone buckets (roughly: NCR / North Luzon / South Luzon / Visayas / Mindanao / Island-only), not by actual km distance. This massively simplifies our job: a rate matrix is ~5 regions × ~5 weight buckets ≈ 25 cells, not a formula.

**Exact 1:1 mirroring of carrier rates is not realistic** — not even with the API. Real carrier math also factors in:
- Volumetric (dimensional) weight — bulky-but-light items are charged on volume, not actual weight. We often don't know the box dimensions until admin packs it.
- COD fee — carriers add a percentage when the order is cash-on-delivery.
- Fuel / peak-season surcharge — rises in BER months and when fuel prices spike.
- Declared-value insurance — optional per shipment.

The honest PH e-commerce standard is **"close enough + small markup buffer"**, not exact match.

### 6.3 Options without API

#### 6.3a — Flat rate per zone (simplest)

One number per region. Example: NCR ₱99, Luzon ₱150, Visayas/Mindanao ₱199. Built into WooCommerce → Shipping Zones → Flat Rate. Zero plugins needed.

- **Pros:** trivial to maintain (~5 numbers), customer sees a single predictable price.
- **Cons:** ignores weight — unfair if we sell both a 200g item and a 4kg bundle.

Good fit only if our catalog is weight-uniform.

#### 6.3b — Zone × weight buckets (recommended)

A small matrix built from WooCommerce Shipping Zones + Shipping Classes (both in WC core, free).

Example rate table:

| Weight → / Zone ↓ | 0–1 kg | 1–3 kg | 3–5 kg | 5–10 kg |
|---|---|---|---|---|
| NCR | ₱95 | ₱140 | ₱195 | ₱280 |
| Luzon (ex-NCR) | ₱140 | ₱195 | ₱260 | ₱380 |
| Visayas | ₱180 | ₱250 | ₱340 | ₱480 |
| Mindanao | ₱195 | ₱275 | ₱370 | ₱540 |

(Numbers illustrative — set each cell to current J&T / LBC published rate × ~1.10 to 1.15 for buffer.)

- **Pros:** ~20 cells, matches how carriers actually price, fair across light/heavy products, all in native WC (no plugin).
- **Cons:** ~20 numbers to review; doesn't handle volumetric weight.

This is our recommendation.

#### 6.3c — Table rate plugin (most granular, most maintenance)

Paid plugins (Flexible Shipping by Octolize — free tier; WooCommerce Table Rate Shipping — USD 99/yr) let us build more complex rules: per-item + per-kg + min/max + COD surcharge. Closer to a real carrier rate card.

- **Pros:** closest to carrier rates, handles edge cases.
- **Cons:** more cells to maintain, plugin dependency, still drifts when carriers update.

Not worth it at current volume. Revisit if we expand to heavy products or multi-carrier choice at checkout.

#### 6.3d — "Shipping quoted after order" (escape hatch)

For oversize or odd-shape items, skip computed shipping at checkout and use a "Contact for shipping" method — admin sends the real quote after packing. Common for furniture, large cosmetics bundles, fragile items.

- **Pros:** never wrong, never drifts.
- **Cons:** adds a manual step, hurts conversion on standard products.

Use it only for a small subset of SKUs (if at all).

### 6.4 How to store and maintain the rate table (the real answer to your worry)

The anxiety isn't really about computation — it's about **where rates live** and **how we keep them fresh**. Two defensible patterns:

**Pattern A — Use WooCommerce's built-in Shipping Zones + Classes.**
All rates editable from WC Admin → Settings → Shipping. No code, no deploy. Admin can update any cell in 30 seconds. This is what 6.3b uses.

**Pattern B — Central rate config file in the theme / plugin.**
Store the matrix in a single PHP / JSON file (e.g. `noyona-order-tracking/config/shipping-rates.json`). A small admin page in our Noyona plugin reads + writes it. One source of truth, auditable via git, diff-friendly.

**Recommendation: Pattern A.** We don't need version control for 20 numbers a non-developer edits twice a year. Use WC core. Add a calendar reminder every 3 months to check J&T / LBC rate card vs our table.

### 6.5 Update cadence — how often we actually need to touch this

Carriers don't update weekly. Realistic cadence for PH:

| Event | Frequency |
|---|---|
| J&T / LBC major rate card revision | 1×/year, typically |
| Fuel / peak surcharge adjustment | 1–2×/year (BER months, fuel spikes) |
| New zone / service tier added | rare |

**Operational rule of thumb:** check carrier rate pages every quarter (Jan / Apr / Jul / Oct). Re-sync any cells that drifted. Budget 30 minutes.

This is much less maintenance than mirroring the code 1:1 would suggest.

### 6.6 Transparency to the customer

Matching the carrier price exactly is neither achievable nor necessary. What *is* achievable is honest labelling at checkout:

- Shipping method label: `J&T Express — Metro Manila` or `LBC — Provincial`.
- Helper text under the total: *"Shipping fee is an estimate based on package weight and delivery region. Minor adjustments possible for oversize items — we'll contact you if that applies."*
- On the order confirmation email: include tracking number (once assigned) + link to the carrier's public tracker (the 2b pattern from Section 2).

This is honest and legally safe — customers accept "estimate" framing as long as the number doesn't change upward silently.

### 6.7 When to move shipping to API

Same triggers as tracking (Section 5), with one addition: **move to API once margin loss from wrong-weight shipments exceeds the API/aggregator cost.** Concretely: if we find ourselves eating ₱1,000+/month on underpriced shipments, an aggregator-provided live quote pays for itself. Until then, 6.3b is cheaper than the fix.

### 6.8 Recommendation summary for shipping fee

1. Use **WooCommerce Shipping Zones + Shipping Classes** (core, free) to build a **Zone × Weight** matrix per carrier option we offer (start with one — J&T — add LBC as a second method later if we want customer choice).
2. Set each cell to **current carrier rate × 1.10–1.15** to absorb minor drift and small volumetric surprises.
3. **Label shipping as an estimate** at checkout and in emails.
4. **Quarterly review** of the rate table against J&T / LBC published cards.
5. Keep one or two SKUs on "Contact for shipping" if we start selling oversize items.
6. Revisit API / aggregator shipping quotes only when (a) tracking goes API per Section 5 triggers, or (b) monthly shipping margin loss exceeds aggregator cost.

---

## 7. Multi-branch fulfillment decision

### 7.1 Context

Noyona has multiple physical branches, registered as the `store` CPT and rendered on the Find-a-Store page (`blocks/location/render.php`). Each `store` has:

- Coordinates: `_nsl_lat`, `_nsl_lng`
- Address: `_nsl_store_address`
- Island group: `_nsl_island_group` (Luzon / Visayas / Mindanao)
- Region: `_nsl_region` (NCR / CALABARZON / Central Visayas / Davao Region / etc.)
- Hours, phone, email, gallery, reviews

As of now, **branches are display-only**. There is no link between `store` posts and WooCommerce products or orders. Physical store stock is tracked outside WooCommerce — the `_stock` field on WC products represents online-available stock only.

### 7.2 Decision (2026-04-24)

- **Single online origin:** Makati HQ — Burgundy Corporate Tower, 252 Sen. Gil Puyat Ave., Makati City.
- **Other branches remain retail walk-in only**, not shipping origins.
- **No pickup-at-branch option** at checkout. Online = ship-only.
- **Inventory is split:** WC stock = online only; physical store stock is managed separately.
- **Future branches fulfilling online: not scheduled.** No plans in the next 6 months.

### 7.3 Implications for shipping computation

Because there is exactly one online origin, Section 6.3b's Zone × Weight matrix applies directly with no modifications:

- One rate table, not N.
- No multi-origin / split-shipment logic.
- No per-branch rate maintenance.
- Destination zones mirror the regional taxonomy already baked into the Find-a-Store block (`nsl_v2_guess_region`), which is itself consistent with how PH carriers bucket zones.

### 7.4 When to revisit (Pattern 2+)

Move to multi-origin only if one of these becomes true:

- A second branch physically starts shipping online orders.
- Customer feedback repeatedly asks for "ship from nearest branch" for faster delivery.
- Makati HQ outgrows its stockroom and online inventory gets physically distributed across branches.

Until then, single-origin is correct.

### 7.5 Implementation outline

Planned WooCommerce configuration to replace the current ₱50 flat rate:

| Layer | What |
|---|---|
| Shipping Zones | NCR · Luzon (ex-NCR) · Visayas · Mindanao |
| Shipping Classes | `weight-0-1kg` · `weight-1-3kg` · `weight-3-5kg` · `weight-5-10kg` |
| Shipping Method per zone | Flat Rate, with per-class cost |
| Class auto-assignment | Theme hook reads `_weight` on each cart item and picks the matching class at runtime — **admin enters weight per product; no manual class assignment** |
| Rate source | Starting values = Section 6.3b illustrative table × confirmed against current J&T / LBC rate card before going live |
| Maintenance | Quarterly review (Jan / Apr / Jul / Oct) against carrier published cards |

Why the auto-assignment hook: WooCommerce Shipping Classes are normally per-product — admin has to pick a class for each product in the product edit screen. That's 200+ click-clicks if the catalog grows, and easy to forget on new products. A tiny filter on `woocommerce_product_get_shipping_class_id` (or `woocommerce_shipping_package_class_id`) reads the product's weight and returns the correct class ID dynamically. Single source of truth, zero per-product admin overhead, ~30 lines of code in the theme.

---

## 8. Shipping fee implementation — status

### 8.1 Decisions locked in (2026-04-24, revised 2026-04-29)

| Decision | Value |
|---|---|
| Origin | Makati HQ, Burgundy Corporate Tower (single online branch) |
| Carriers at checkout | **J&T Express only** (revised 2026-04-29) |
| LBC Express | **Retained in code, disabled at checkout.** Toggle `'enabled' => true` on the LBC entry in `CARRIERS` (`inc/woocommerce-shipping.php`) and re-run setup to re-expose. |
| Rate type | **Walk-in** rates (no merchant contract yet) |
| COD surcharge | **None** — COD is not a payment option on this site |
| Weight buckets | `0-1kg`, `1-3kg`, `3-5kg`, `5-10kg` |
| Destination zones | NCR, Luzon (ex-NCR), Visayas, Mindanao |
| Active rate cells | 4 zones × 4 weight buckets × 1 carrier (J&T) = **16** |
| Code-only rate cells | Same matrix shape preserved for LBC (kept at `0.00` until re-enabled) |

**Why J&T-only for now (2026-04-29):** Noyona wants to focus on a single carrier at launch — one rate card to verify, one booking workflow, simpler operational training. LBC will be reintroduced once J&T is proven on production volume.

### 8.2 Code in place

- `inc/woocommerce-shipping.php` — `Noyona_Shipping` class, included from `functions.php` alongside the other `inc/woocommerce-*.php` files.
- Registers 4 shipping classes, 4 zones mapped to WooCommerce PH province codes, and a flat-rate method per **enabled** carrier in each zone — all idempotent.
- **Auto-assigns shipping class from product weight** via `woocommerce_product_get_shipping_class_id` filter. Admin only fills the weight field; class is picked at runtime.
- **Carrier enable/disable flag** (`CARRIERS[*]['enabled']`). Disabled carriers either get `is_enabled = 0` written to their existing `wp_woocommerce_shipping_zone_methods` row, or are skipped entirely on a fresh setup. As of 2026-04-29: J&T enabled, LBC disabled.
- **Admin page** at `Tools → Noyona Shipping` — displays each carrier's matrix (disabled ones tagged "DISABLED at checkout"), last-run timestamp, run log, and a "Run Setup" button.
- **Safety guard**: setup runner refuses to apply if all *enabled* carriers' cells are still `0.00`. "Run Setup" button is disabled in the admin page while the enabled matrix is unpopulated.

### 8.3 Pending

- **J&T rate matrix:** ✅ filled 2026-04-29 from the 2023 J&T public walk-in rate card, "From Metro Manila" origin. 16 cells live in `RATE_MATRIX['jt']`. Tier-merge notes (501g–1kg → `0-1kg`; avg of 3.01–4kg / 4.01–5kg → `3-5kg`; avg of 5.01–10kg tiers → `5-10kg`) are in the docblock above `RATE_MATRIX` for review.
- **LBC rate matrix:** deferred. 16 cells stay at `0.00` while LBC is disabled. To revive: fill rates from the LBC walk-in card, set `'enabled' => true` on the LBC entry in `CARRIERS`, click Run Setup.
- **First Run Setup click** — replaces the current ₱50 flat rate with the J&T zone × weight matrix.
- **Checkout verification** on local site before deploying to production — confirm J&T appears as a single option per destination, correct rate per zone, correct rate per cart weight, and LBC does **not** appear.

### 8.4 Merge strategy when carrier bands don't match our buckets

J&T's rate card splits sub-kilo (0–0.5kg vs 0.51–1kg) and breaks 3–5kg into two tiers. LBC has its own bands. When a carrier's bands don't line up with our 4 buckets:

- **For lower tiers (0–1kg):** take the *highest* rate in the overlapping range so we never undercharge a heavier sub-kilo package.
- **For wider tiers (3–5kg, 5–10kg):** average the carrier's component tiers — this matches the J&T transcription on 2026-04-29.
- Note each merged/averaged cell in the docblock above `RATE_MATRIX` so reviewers can audit the source.

### 8.5 Maintenance cadence (applies once live)

Quarterly review (Jan / Apr / Jul / Oct) per §6.5:

1. Open the current J&T public rate page (and LBC's, if/when LBC is re-enabled).
2. Re-check the 16 active cells (32 if LBC is back on) against the current card(s).
3. Edit `RATE_MATRIX` in `inc/woocommerce-shipping.php`, commit, deploy.
4. Visit `Tools → Noyona Shipping → Run Setup` — the runner updates WooCommerce flat-rate costs in place. No zone recreation.

### 8.6 When this implementation stops being enough

Flip to API / aggregator (per §3 and §5) when any of:

- More than one branch starts fulfilling online orders (then §7.4 triggers first — rework the zone matrix for multi-origin).
- Monthly shipping margin loss from weight / zone mismatches exceeds an aggregator subscription (~USD 11/mo).
- Volume crosses ~50 shipments/month *and* customer "where's my order?" tickets make tracking automation worthwhile too.
- A second carrier is enabled at checkout (LBC re-introduced, or a new carrier added) and the matrix grows unmaintainable.
