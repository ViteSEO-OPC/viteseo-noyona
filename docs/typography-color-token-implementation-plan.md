# Typography & Color Token Implementation Plan

> **Status:** Documentation only. No theme.json, style.css, or component CSS changes were made by this report. Phase 1 implementation is proposed at the end and awaits approval.

---

## 1. Executive summary

The Noyona child theme currently runs on a minimal `theme.json` (4 font sizes, 1 color slug) and a `style.css` that re-declares the same body/H1/H2/H3 rules, then drifts further across 30+ block stylesheets that each hard-code their own sizes and pinks. The previous audit (`typograph_report.text`) and this scan confirm:

- The token system covers only H1/H2/H3 and body; H4–H6, label, button, caption, helper, and input have **no global tokens** even though all of those roles are used in markup.
- The color palette has **one entry** (`#333333` ink). The rest of the site uses **35+ pink variants**, **9+ near-black text colors**, and **7+ gray tones**, only a fraction of which match the designer-approved set.
- H1 in `theme.json` still maps to Noto Serif SemiCondensed; the designer wants Proxima Nova everywhere except the newsletter title em.
- Five CSS files emit **invalid** `letter-spacing: -5%` (percentages are not valid for `letter-spacing` — browsers drop the declaration; designer confirmed Figma -5% should be expressed as `-0.05em`).
- CTA radii are split between 16px (designer-approved for **all** buttons) and 999px pill (124 occurrences), plus 12px/14px/18px stragglers.

**Designer clarifications received (this revision):** input bg = `#FFFFFF`; borders are **contextual** (mix of pink / `#525252` / none — not all `#000`); line-height: `normal` ✓; letter-spacing: `-0.05em` ✓; CTA radius 16px applies to **all** buttons (Phase 2 migration); card radius 24px + padding 15/12/10 (D/T/M) applies site-wide (Phase 2 migration); status colors = standard e-commerce greens/yellows/reds, provisional now and tunable later; 16px is the **minimum** text size site-wide; H4/H5 sizes and label/button/caption/helper/input weights are accepted as Claude's provisional suggestions.

**Still blocked:** pink role mapping for `#EFB5BE` / `#FBDDE2` / `#E199A4`, and the primary CTA hover color.

Recommended approach: ship Phase 1 as a **purely additive token layer** in `theme.json` + `:root` variables in `style.css`, plus the inert `letter-spacing` bug fix. Leave the existing `h-1`, `h-2`, `h-3`, `body` slugs and component rules in place so nothing breaks. Phase 2 migrates components — radii, padding, min-size, color roles — one family at a time, after pink/hover roles arrive.

---

## 2. Current implementation findings

### 2.1 `theme.json` contents
- **Font families (2):** `proxima-nova`, `noto-serif-semicondensed`.
- **Font sizes (4):** `body = clamp(16px, 1.4vw, 20px)`, `h-3 = clamp(18px, 2vw, 24px)`, `h-2 = clamp(32px, 4.2vw, 55px)`, `h-1 = clamp(44px, 6vw, 96px)`.
- **Color palette (1):** `ink = #333333`.
- **Element styles:**
  - `h1`: Noto Serif, 700, line-height 1.05
  - `h2`: Proxima Nova, 700, line-height 1.1
  - `h3`: Proxima Nova, 600, line-height 1.15
  - body: Proxima Nova, 400, color `#333333`

### 2.2 Duplicate global typography in `style.css`
- `style.css:111-137` re-declares `body`, `h1`, `h2`, `h3` font-family/font-size/font-weight/line-height with the same values as `theme.json`. Pure duplication — removable in Phase 2.

### 2.3 H4/H5/H6 usage in markup
- **H4** (admin-facing, in cards): `inc/shortcodes.php:1067` (account profile name), `inc/shortcodes.php:1104` (modal title), plus `style.css:3200` (`.noyona-account-order-modal__progress h4`), `style.css:3987` (`.noyona-account-address-item h4`), `style.css:4159` (`.noyona-account-payment-item__meta h4`), `blocks/search-expand/style.css:463` (search suggestions column), `blocks/location/style.css:494` (store reviews tab).
- **H5** (shop filter section titles): heavy usage in `templates/archive-product.html`, `page-hair.html`, `page-lips.html`, `page-eyes.html`, `page-face.html`, `page-body.html`, `taxonomy-product_cat.html` (8 templates × 3 headings each = 24 markup occurrences) plus `inc/helpers.php:719, 731`. CSS rules: `style.css:1069`, `style.css:2114`. Labels: "Stock Status", "Price", "Star Rating".
- **H6**: **not used anywhere** in markup or CSS.

### 2.4 Font-family usage
- **Proxima Nova** (token): used correctly across ~30 block stylesheets and `style.css`.
- **Noto Serif SemiCondensed** (token): used in `blocks/hero-banner/style.css:94, 103`, `blocks/discover-face-banner/style.css:52`, `blocks/mosaic-grid/style.css:77`, and the H1 element style in `theme.json`. **Designer says: only Proxima Nova going forward** — confirm each occurrence's intent before flipping (some hero typography may be intentionally serifed; flag for designer review).
- **Times New Roman** (literal): `blocks/newsletter-strip/style.css:36` — only legitimate non-Proxima use per designer.
- **Georgia** (legacy/email): `assets/css/footer.css:441` — looks like an email/invoice block; not in scope per the brief.
- **Apple system stack** (non-token): `blocks/inquiry/style.css:2`, `blocks/contact/style.css:8` — these bypass `--wp--preset--font-family--proxima-nova` and fall back to OS UI font. Should adopt the Proxima token in Phase 2.
- **"Proxima Nova Light"** literal: `blocks/blogs-view/style.css:309` — a different weight string outside the token system.

### 2.5 Non-approved color tally (top entries from CSS scan)
Designer-approved hexes: `#EFB5BE`, `#FBDDE2`, `#E199A4`, `#000000`, `#525252`, `#FFFFFF`.

| Hex | Occurrences | Likely role |
|---|---:|---|
| `#e199a4` ✓ | **281** | Primary CTA — already matches designer accent pink |
| `#fff` ✓ | 269 | White — matches |
| `#ffffff` ✓ | 55 | White — matches |
| `#111` ✗ | 51 | Near-black text — should be `#000000` |
| `#000` ✓ | 38 | Black — matches |
| `#666` ✗ | 37 | Muted text — should be `#525252` |
| `#333` ✗ | 32 | Body text — should be `#000000` or `#525252` (review per use) |
| `#1f1f1f` ✗ | 24 | Near-black — should be `#000000` |
| `#555` ✗ | 23 | Muted — should be `#525252` |
| `#ececec` ✗ | 22 | Light border/bg — needs designer mapping |
| `#777` ✗ | 22 | Muted — should be `#525252` |
| `#ddd` ✗ | 19 | Border — needs designer mapping (designer says border `#000`) |
| `#444` ✗ | 19 | Text — should be `#000000` |
| `#eee` ✗ | 18 | Light border/bg — needs designer mapping |
| `#1a1a1a` ✗ | 15 | Near-black — should be `#000000` |
| `#ff4d6d` ✗ | 13 | Off-brand pink — flag for replacement |
| `#222` ✗ | 13 | Near-black — should be `#000000` |
| `#fff1f6` ✗ | 12 | Soft pink bg — candidate for `#FBDDE2` role mapping |
| `#e39cab` ✗ | 12 | Pink variant — close to `#E199A4`; consolidate |
| `#525252` ✓ | 11 | Muted text — matches |
| `#cc848f` ✗ | 10 | Pink hover (darker `#e199a4`) — needs role: CTA hover |
| `#ece8ea` ✗ | 10 | Light bg — needs designer mapping |
| `#0f1728` ✗ | 8 | Near-black — should be `#000000` |
| `#2f2f2f` ✗ | 8 | Near-black — should be `#000000` |
| `#2b2b2b` ✗ | 8 | Near-black — should be `#000000` |
| `#4f4f4f` ✗ | 7 | Muted — should be `#525252` |
| `#333333` ✓ | 7 | Matches `ink` token (theme.json default) |
| `#101828` ✗ | 7 | Near-black — should be `#000000` |
| `#ff4b81` ✗ | 6 | Off-brand pink — flag |
| `#f8a8bc` ✗ | 6 | Pink variant — close to `#EFB5BE`; consolidate |
| `#efb5be` ✓ | 6 | Soft pink — matches |
| `#66bb6a` ✗ | 6 | Green — candidate for success status |

(Full pink/gray/black inventory was tallied in `typograph_report.text` §16.)

### 2.6 Typography mismatches found
- Heavy use of px/rem outside the scale: `font-size: 14px` (43×), `0.95rem` (34×), `0.9rem` (31×), `13px` (26×), `12px` (22×), `0.85rem` (17×), `15px` (19×), `0.88rem` (11×), etc. The designer scale has no values below 16px (mobile body) and no 14px or 13px helper sizes.
- Account section is the worst offender (per prior audit) using `calc(var(--wp--preset--font-size--body) * 0.72)` and similar multipliers in ~15 places.

### 2.7 Border-radius mismatches (vs. designer: CTA 16px, card 24px)
| Value | Occurrences | Typical role |
|---|---:|---|
| `999px` | 124 | Pills — most CTAs, badges, pill controls (designer wants 16px on CTAs) |
| `12px` | 54 | Inputs, smaller cards |
| `16px` | 40 | ✓ Some CTAs already use the target |
| `50%` | 33 | Avatars/icons (legitimate — not in scope) |
| `14px` | 31 | Inputs (between 12 and 16) |
| `0` | 30 | Reset/no radius (legitimate) |
| `18px` | 23 | Mid-card variant |
| `8px` | 18 | Small chips |
| `24px` | 17 | ✓ Cards already using target |
| `!important` variants | 11 | `999px` (4×), `26px` (2×), `16px` (2×), `12px` (2×), `50%` (1×) — these are hard to override and need review |

### 2.8 Invalid `letter-spacing` (CSS spec violation)
`letter-spacing: -5%` is **invalid CSS** — browsers ignore it. Designer's -5% from Figma should be `letter-spacing: -0.05em`. Found in 5 places:
- `blocks/hero-banner/style.css:81, 141`
- `blocks/brand-carousel/style.css:67`
- `assets/css/header.css:300, 378`

---

## 3. Designer-approved typography guide (recap)

| Role | Desktop | Tablet | Mobile | Weight | Line-height | Letter-spacing |
|---|---|---|---|---|---|---|
| H1 | 96px | 64px | 48px | 700 | normal | −0.05em |
| H2 | 55px | 40px | 32px | 700 | normal | −0.05em |
| H3 | 24px | 24px | 20px | 600 | normal | −0.05em |
| Paragraph | 20px | 18px | 16px | 400 | normal | −0.05em |
| Label | 18px | — | 16px | TBD | normal | TBD |
| Button | 24px | 20px | 18px | TBD | normal | TBD |
| Caption / badge | 18px | — | 16px | TBD | normal | TBD |
| Small / helper | 18px | — | 16px | TBD | normal | TBD |
| Input | 20px | 18px | 16px | TBD | normal | TBD |

Fonts: **Proxima Nova everywhere**, Times New Roman Italic only on `.newsletter-strip__title em`.

---

## 4. Designer-approved color/UI guide (recap)

**Pinks (role mapping pending):** `#EFB5BE`, `#FBDDE2`, `#E199A4`.
**Text/utility:** main `#000000`, muted/helper/placeholder/info `#525252`, white `#FFFFFF`.
**Border:** **contextual** — some pink, some `#525252`, some components have no border. `#000000` is used only as a strong outline where needed. **No global border replacement.**
**Input bg:** `#FFFFFF` (designer-confirmed correction; `#525252` is text only, not background).
**Placeholder:** `#525252`.
**Status:** success = light green, warning = light yellow, error = light red — use standard e-commerce defaults provisionally and tune later (no designer-final hexes).
**Line-height:** `normal` (designer-confirmed).
**Letter-spacing:** `-0.05em` (designer-confirmed translation of Figma −5%).

**UI rules:** card radius 24px; card padding 15 / 12 / 10 (D/T/M); CTA radius 16px; CTA padding 10 or 15px; screen padding 110 / 50 / 20 (D/T/M).

---

## 5. Missing / unclear designer values

Status legend: 🔴 **blocker** (Phase 1 must skip) · 🟡 **provisional** (Claude-suggested, designer-allowed for planning, tunable in Phase 2) · 🟢 **resolved** (designer-confirmed).

| # | Topic | Status | Detail |
|---|---|---|---|
| 1 | **Pink role mapping** | 🔴 Blocker | 3 pinks named (`#EFB5BE`, `#FBDDE2`, `#E199A4`) but still no mapping to CTA fill / CTA hover / soft bg / accent. **Do not globally replace pinks in Phase 1.** Group current pink usage by *likely* role in Phase 2 audit, then await designer mapping before substitution. |
| 2 | **Primary CTA hover color** | 🔴 Blocker | No hover hex yet. Current site uses ad-hoc darker pinks (`#cc848f`, etc.). **Do not propose a final hover token in Phase 1.** Defer to designer mapping in Phase 2. |
| 3 | **Status hex values** | 🟡 Provisional | Designer said "use standard e-commerce greens/yellows/reds and adjust later." Provisional tokens proposed in §6.3 are clearly marked *not designer-final*. **No global replacement in Phase 1.** |
| 4 | **Input background** | 🟢 Resolved | `#FFFFFF` (designer-corrected). `#525252` is for muted text / placeholder / helper / info — never an input background. |
| 5 | **Borders** | 🟢 Resolved as contextual | Border role mapping is **contextual** — some borders are pink, some `#525252`, some components have no border. Use `#000000` only as a strong outline where explicitly needed. **No global border replacement in Phase 1.** |
| 6 | **Line-height `normal`** | 🟢 Resolved | Designer-confirmed `line-height: normal` everywhere. |
| 7 | **Letter-spacing `-5%`** | 🟢 Resolved | Designer-confirmed translation to `letter-spacing: -0.05em`. (Fixes the 5 invalid declarations cataloged in §2.8.) |
| 8 | **H4 / H5 sizes** | 🟡 Provisional | Designer didn't supply them; provisional tokens approved for planning: H4 `clamp(18px, 1.6vw, 22px)` weight 600; H5 `clamp(16px, 1.2vw, 18px)` weight 600; line-height `normal`; letter-spacing `-0.05em`. H6 skipped (unused). |
| 9 | **Label / button / caption / helper / input weights** | 🟡 Provisional | Designer allowed Claude to suggest. Approved provisional weights: label 600, button 700, caption/badge 600, helper/small 400, input 400. |
| 10 | **CTA radius 16px** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed **16px applies to all buttons** (including current pills). But the site has 124 × `999px` pills; flipping globally in Phase 1 would change the visual identity site-wide. **Migrate in a controlled Phase 2** component-by-component, with QA per family. |
| 11 | **Card radius 24px + padding 15/12/10 (D/T/M)** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed site-wide. Migrate per component in Phase 2 — not forced globally in Phase 1 — because current cards vary (18/14/12/8px) and many have padding tied to internal grid math. |
| 12 | **Screen padding 110 / 50 / 20 (D/T/M)** | 🟢 Resolved | Standard breakpoints adopted: desktop ≥ 1024px, tablet 768–1023px, mobile ≤ 767px. CSS var proposed in §7. |
| 13 | **Minimum text size 16px** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed 16px is the floor even on mobile. The 200+ existing 12–15px declarations cataloged in §2.6 are **flagged as Phase 2 cleanup**, not changed in Phase 1. |
| 14 | **Serif fonts** | 🟢 Resolved | Proxima Nova everywhere; Times New Roman Italic only on `.newsletter-strip__title em`. Noto Serif and Apple-stack usages cataloged in §9 — converted to Proxima Nova in Phase 2; the `noto-serif-semicondensed` token stays in `theme.json` for Phase 1 so existing usages don't break. |

---

## 6. Proposed `theme.json` tokens *(not yet applied)*

### 6.1 Font families
```json
{
  "fontFamily": "\"Proxima Nova\", system-ui, -apple-system, \"Segoe UI\", sans-serif",
  "name": "Proxima Nova",
  "slug": "proxima-nova"
},
{
  "fontFamily": "\"Times New Roman\", Times, serif",
  "name": "Times New Roman Italic",
  "slug": "times-italic"
}
```
*Keep `noto-serif-semicondensed` temporarily; remove in Phase 3 after auditing hero/discover/mosaic blocks.*

### 6.2 Font sizes (add new slugs; keep existing for backward compatibility)
All values use designer-confirmed `line-height: normal` and `letter-spacing: -0.05em` unless noted.

| Proposed slug | Value | Weight | Notes |
|---|---|---|---|
| `h-1` (keep) | `clamp(48px, 6vw, 96px)` | 700 | tightened lower-bound to match designer mobile 48px |
| `h-2` (keep) | `clamp(32px, 4.2vw, 55px)` | 700 | already matches designer |
| `h-3` (keep) | `clamp(20px, 2vw, 24px)` | 600 | tightened mobile to 20px |
| `h-4` (NEW, provisional) | `clamp(18px, 1.6vw, 22px)` | 600 | account card titles, modal titles — Claude-suggested |
| `h-5` (NEW, provisional) | `clamp(16px, 1.2vw, 18px)` | 600 | shop filter section titles — Claude-suggested |
| `h-6` | skip | — | unused in markup |
| `body` (keep) | `clamp(16px, 1.4vw, 20px)` | 400 | already matches designer |
| `label` (NEW) | `clamp(16px, 1.2vw, 18px)` | **600** | for `<label>` / form labels — provisional |
| `button` (NEW) | `clamp(18px, 1.6vw, 24px)` | **700** | for `.button`, `.noyona-*-submit`, CTAs — provisional |
| `caption` (NEW) | `clamp(16px, 1.2vw, 18px)` | **600** | badges, image captions — provisional |
| `helper` (NEW) | `clamp(16px, 1.2vw, 18px)` | **400** | small helper text — provisional |
| `input` (NEW) | `clamp(16px, 1.4vw, 20px)` | **400** | input / textarea / select — provisional |

**Minimum text size 16px is approved.** All 12px / 13px / 14px / 15px declarations cataloged in §2.6 are flagged for Phase 2 cleanup — not changed in Phase 1.

### 6.3 Color palette (additive)
```jsonc
// === Designer-confirmed ===
{ "slug": "brand-pink-primary",   "name": "Brand Pink Primary",   "color": "#E199A4" },  // role mapping pending — name will likely change
{ "slug": "brand-pink-soft",      "name": "Brand Pink Soft",      "color": "#EFB5BE" },  // role mapping pending
{ "slug": "brand-pink-pale",      "name": "Brand Pink Pale",      "color": "#FBDDE2" },  // role mapping pending
{ "slug": "text-main",            "name": "Text Main",            "color": "#000000" },
{ "slug": "text-muted",           "name": "Text Muted",           "color": "#525252" },  // also covers helper / placeholder / info
{ "slug": "white",                "name": "White",                "color": "#FFFFFF" },
{ "slug": "input-bg",             "name": "Input Background",     "color": "#FFFFFF" },  // CONFIRMED #FFFFFF (NOT #525252)
{ "slug": "placeholder",          "name": "Placeholder",          "color": "#525252" },
{ "slug": "border-strong",        "name": "Border Strong",        "color": "#000000" },  // available, but borders are contextual — do not globally apply
{ "slug": "status-info",          "name": "Status Info",          "color": "#525252" },

// === Provisional (Claude-suggested, designer said "standard e-commerce, adjust later") ===
{ "slug": "status-success",       "name": "Status Success (provisional)", "color": "#E6F4EA" },  // NOT DESIGNER-FINAL
{ "slug": "status-warning",       "name": "Status Warning (provisional)", "color": "#FFF8E1" },  // NOT DESIGNER-FINAL
{ "slug": "status-error",         "name": "Status Error (provisional)",   "color": "#FDEAEA" }   // NOT DESIGNER-FINAL
```
- The pink slug **names** above (`primary`/`soft`/`pale`) are placeholders ordered by saturation only — they will be renamed once the designer maps each hex to a role (CTA fill / CTA hover / soft bg / accent / badge).
- `border-strong` is **available** but **must not be globally applied** to all borders — borders are contextual (some pink, some `#525252`, some none).
- Status tokens are explicitly **provisional** with `(provisional)` in the slug name so they don't get mistaken for final values.
- The existing `ink = #333333` slug stays in place so any current `var(--wp--preset--color--ink)` references keep working.

### 6.4 H1 element font-family flip
Change in `theme.json` `styles.elements.h1.typography.fontFamily`:
- **From:** `var(--wp--preset--font-family--noto-serif-semicondensed)`
- **To:** `var(--wp--preset--font-family--proxima-nova)`

Phase 1 safe; designer-confirmed.

---

## 7. Proposed `style.css` `:root` variables *(not yet applied)*

```css
:root {
  /* === Radius scale (designer-confirmed; consumers opt-in per component in Phase 2) === */
  --noyona-radius-card: 24px;        /* designer site-wide */
  --noyona-radius-cta: 16px;         /* designer says applies to ALL buttons (Phase 2 migration) */
  --noyona-radius-input: 12px;       /* matches dominant input radius today; no designer override */

  /* === Card padding scale (designer-confirmed; consumers opt-in per component in Phase 2) === */
  --noyona-card-pad-desktop: 15px;
  --noyona-card-pad-tablet: 12px;
  --noyona-card-pad-mobile: 10px;

  /* === Screen padding scale (designer-confirmed breakpoints) === */
  /* Desktop ≥ 1024px → 110px · Tablet 768–1023px → 50px · Mobile ≤ 767px → 20px */
  --noyona-screen-pad-desktop: 110px;
  --noyona-screen-pad-tablet: 50px;
  --noyona-screen-pad-mobile: 20px;
  /* Fluid screen padding (single var consumers can use) */
  --noyona-screen-pad: clamp(20px, 6vw, 110px);

  /* === Typography helpers (designer-confirmed) === */
  --noyona-letter-spacing-tight: -0.05em;  /* Figma −5% translation */
  /* line-height is "normal" everywhere per designer — no var needed */

  /* === Minimum text size (designer floor) === */
  --noyona-text-min: 16px;

  /* === Brand color shorthands mirroring theme.json (so non-block CSS can use them) ===
     Pink slug names are placeholders; designer role mapping pending — do NOT swap colors
     site-wide until the designer maps each hex to a role. */
  --noyona-color-pink-primary: var(--wp--preset--color--brand-pink-primary, #E199A4);
  --noyona-color-pink-soft:    var(--wp--preset--color--brand-pink-soft,    #EFB5BE);
  --noyona-color-pink-pale:    var(--wp--preset--color--brand-pink-pale,    #FBDDE2);
  --noyona-color-text-main:    var(--wp--preset--color--text-main,          #000000);
  --noyona-color-text-muted:   var(--wp--preset--color--text-muted,         #525252);
  --noyona-color-white:        var(--wp--preset--color--white,              #FFFFFF);
  --noyona-color-input-bg:     var(--wp--preset--color--input-bg,           #FFFFFF);
  --noyona-color-placeholder:  var(--wp--preset--color--placeholder,        #525252);
  /* Strong border available but borders are contextual — NOT for global application */
  --noyona-color-border-strong: var(--wp--preset--color--border-strong,     #000000);

  /* CTA hover token intentionally NOT defined yet — pending designer mapping */
}
```

All `var(--wp--preset--*)` references include hard-coded fallbacks so the variables still resolve even before `theme.json` is updated. This makes the rollout safely reversible: drop the `:root` block, drop the new `theme.json` slugs, done.

---

## 8. H4/H5/H6 usage report

| Level | Used? | Where | Recommended token (pending designer) |
|---|---|---|---|
| H4 | ✓ Yes | Account profile name, modal titles, order modal progress, address card, payment card, search suggestions column, store reviews tab | `h-4` ≈ `clamp(18px, 1.6vw, 22px)`, weight 600 |
| H5 | ✓ Yes (heavy) | Shop filter section titles ("Stock Status", "Price", "Star Rating") across 7 product/category templates + `helpers.php` | `h-5` ≈ `clamp(16px, 1.4vw, 18px)`, weight 600 |
| H6 | ✗ Not used | — | Skip — do not add a global token yet |

---

## 9. Font-family usage report (summary of §2.4)

| Family | Should keep? | Files |
|---|---|---|
| Proxima Nova (token) | ✅ Keep — default everywhere | All approved files |
| Noto Serif SemiCondensed (token) | ⚠ Designer review required | H1 element style in `theme.json`; `blocks/hero-banner/style.css:94, 103`; `blocks/discover-face-banner/style.css:52`; `blocks/mosaic-grid/style.css:77` |
| Times New Roman (literal) | ✅ Keep — only in `blocks/newsletter-strip/style.css:36` `.newsletter-strip__title em` (designer-approved scope) |
| Georgia (literal) | ⏸ Out of scope | `assets/css/footer.css:441` — email/invoice context |
| Apple system stack | ❌ Replace with Proxima token | `blocks/inquiry/style.css:2`, `blocks/contact/style.css:8` |
| "Proxima Nova Light" literal | ❌ Replace with token + explicit `font-weight: 300` | `blocks/blogs-view/style.css:309` |

---

## 10. Non-approved colors found (top inventory)

See §2.5. **Highest-impact consolidations:**
- **Near-blacks → `#000000`:** `#111` (51), `#1f1f1f` (24), `#1a1a1a` (15), `#222` (13), `#0f1728` (8), `#2f2f2f` (8), `#2b2b2b` (8), `#101828` (7). Total: 134 references collapsed to one token.
- **Mid-grays → `#525252`:** `#666` (37), `#555` (23), `#777` (22), `#444` (19), `#4f4f4f` (7), `#888` (9), `#999` (7). Total: 124 references.
- **Pink variants → 3 approved pinks (mapping pending):** `#cc848f` (10), `#e39cab` (12), `#f8a8bc` (6), `#fff1f6` (12), `#ff4d6d` (13), `#ff4b81` (6). Includes likely hover (#cc848f), soft bg (#fff1f6), and off-brand reds (#ff4d6d, #ff4b81).
- **Ambiguous neutrals → designer needs to map:** `#ececec` (22), `#ddd` (19), `#eee` (18), `#ece8ea` (10), `#fafafa` (10), `#f0f0f0` (11), `#f5f5f5` (9), `#efefef` (12), `#e5e5e5` (10).

---

## 11. Typography mismatches found

- 200+ `font-size` declarations in 14px / 13px / 12px / 15px / 0.95rem / 0.9rem / 0.85rem / 0.88rem / 0.8rem / 0.78rem range — all **below** the designer minimum (16px mobile body).
- 60+ `font-size` declarations in non-standard mid-range (22px, 1.05rem, 1.1rem, 1.15rem, 2rem) that should map to the proposed `h-3`/`h-4`/`button`/`label` tokens.
- Account section uses `calc(var(--wp--preset--font-size--body) * 0.72)` and similar multipliers ~15× — these compute to roughly 11–14px depending on viewport, all sub-minimum.
- `style.css:111-137` re-declares `body`/`h1`/`h2`/`h3` font-family/size/weight/line-height that already live in `theme.json` (duplication).

---

## 12. Radius / padding mismatches found

| Designer rule | Reality |
|---|---|
| CTA radius 16px | 40 × `16px` ✓ vs **124 × `999px` (pill)** ✗ vs 12/14/18 stragglers |
| Card radius 24px | 17 × `24px` ✓ vs many cards at 18/14/12/8px |
| Card padding 15/12/10 (D/T/M) | No global var; most cards use ad-hoc rem values (1.2–1.7rem) |
| Screen padding 110/50/20 (D/T/M) | No global var; pages use ad-hoc clamps or fixed px |
| 11 `border-radius … !important` declarations | High-friction overrides — need targeted refactor |

---

## 13. Recommended safe Phase 1

**Goal:** ship the token layer + one pure bug fix. Visual state of the site must be **identical** to today.

Add tokens **only**. Do not touch component CSS. Do not flip existing CTA pill radii. Do not replace pinks. Do not change card radii/padding. Do not shrink/grow any current text.

1. **`theme.json`** — additive only:
   - Add `times-italic` font family (`"Times New Roman", Times, serif`) for the newsletter em.
   - Add new font-size slugs: `label`, `button`, `caption`, `helper`, `input`, `h-4`, `h-5` (provisional; keep existing `h-1`/`h-2`/`h-3`/`body`).
   - Add new color slugs (per §6.3): `brand-pink-primary`, `brand-pink-soft`, `brand-pink-pale`, `text-main`, `text-muted`, `white`, `input-bg` (**`#FFFFFF`** ✓), `placeholder`, `border-strong`, `status-info`, and provisional `status-success`/`status-warning`/`status-error` (clearly labeled `(provisional)` in the slug name).
   - Flip `styles.elements.h1.typography.fontFamily` to the Proxima Nova token (designer-confirmed).
   - **Do not remove** the `noto-serif-semicondensed` token in Phase 1 — block CSS still references it. Migration in Phase 2.
2. **`style.css`** — additive only:
   - Add the `:root` block from §7 at the top of the file (after the existing `:root` block if one exists).
   - **Fix the 5 invalid `letter-spacing: -5%` declarations → `-0.05em`** in `blocks/hero-banner/style.css:81, 141`, `blocks/brand-carousel/style.css:67`, `assets/css/header.css:300, 378`. This is a pure bug fix — current value is silently dropped by browsers, so applying `-0.05em` is the first time the rule actually paints.
3. **No component refactor in Phase 1:**
   - ❌ No CTA radius change (124 pills stay pills until Phase 2).
   - ❌ No card radius / card padding change.
   - ❌ No screen padding rollout.
   - ❌ No pink replacement (role mapping still pending).
   - ❌ No CTA hover token (still pending).
   - ❌ No text-size cleanup (12–15px → 16px floor is Phase 2).
   - ❌ No border replacement (contextual).
   - ❌ No status-color replacement.
4. **Verification:**
   - Spot-check home, PDP, login, register, my-account dashboard, my-account orders, addresses, cart, checkout, contact, search results — visual should be **identical** to today.
   - Visual delta should be limited to the 5 letter-spacing locations getting their designed `-0.05em` for the first time (hero, brand carousel, header).

Phase 1 is fully reversible: revert two files (`theme.json` + `style.css` + the three letter-spacing files), back to today's state.

---

## 14. What NOT to change yet

- ❌ **Do not replace any pink hex globally.** Pink role mapping for `#EFB5BE` / `#FBDDE2` / `#E199A4` is still pending. Group current pink usage by likely role only — no substitution. (§5 #1)
- ❌ **Do not propose a final CTA hover token.** Designer hasn't mapped it yet. (§5 #2)
- ❌ **Do not flip `999px` pill CTAs to `16px` in Phase 1.** Designer confirmed 16px applies to *all* buttons, but flipping 124 pills site-wide must be a controlled Phase 2 migration with QA per component family. (§5 #10)
- ❌ **Do not force card radius (24px) or card padding (15/12/10) site-wide in Phase 1.** Designer-confirmed values; implementation belongs in Phase 2 component migration. (§5 #11)
- ❌ **Do not globally replace borders with `#000000`.** Borders are contextual — some pink, some `#525252`, some absent. Use `#000` only as a strong outline where explicitly needed. (§5 #5)
- ❌ **Do not globally replace status colors.** Provisional status hexes are clearly marked `(provisional)` and will be tuned later. (§5 #3)
- ❌ **Do not change any 12–15px text in Phase 1.** 16px minimum is approved but cleanup is Phase 2. (§5 #13)
- ❌ Do not change input background to `#525252` — confirmed `#FFFFFF`. (§5 #4)
- ❌ Do not remove the duplicate body/h1/h2/h3 declarations in `style.css:111-137` yet — wait until Phase 2 component sweep to avoid mid-rollout regressions.
- ❌ Do not remove the `noto-serif-semicondensed` token from `theme.json` in Phase 1 — block CSS still references it; migrate usages first in Phase 2 (§13 #14).
- ❌ Do not touch Noto Serif usages in hero/discover/mosaic blocks yet — Phase 2 conversion to Proxima Nova once the designer signs off per block. (§9)
- ❌ Do not touch social brand colors, map colors, admin-only styles, or invoice/email styles (out of scope per brief).
- ❌ Do not touch `assets/css/footer.css:441` Georgia (email/invoice context) until invoice review is requested.

---

## 15. Suggested Phase 2 / Phase 3 rollout

### Phase 2 — Component migration (gated on the two remaining designer blockers: pink role map + CTA hover)

**Pre-flight:** wait for designer to deliver (1) the pink-role mapping for `#EFB5BE` / `#FBDDE2` / `#E199A4`, and (2) the primary CTA hover color. Once those land, rename the pink slugs in `theme.json` to their roles (e.g. `cta-fill`, `cta-hover`, `bg-pink-soft`, `accent-pink`) — `:root` aliases in `style.css` absorb the rename so component CSS doesn't break.

Migrate one component family per PR, lowest risk → highest visibility:

1. **Account section** (worst typography fragmentation; `inc/shortcodes.php` + `style.css:2748+`, 3145+, etc.) — also bumps any sub-16px text to the 16px floor.
2. **Auth pages** (login/register/lost-password) — already touched; finish token-izing pinks, swap CTA pills → 16px, adopt card radius 24px + padding 15/12/10.
3. **Shop / category filter** (`h-5` token adoption: "Stock Status / Price / Star Rating") + ratchet 14px chip text to 16px.
4. **PDP** (`blocks/pdp-*`, `single-product.css`) — flip remaining pink hexes, adopt CTA 16px, card 24px.
5. **Checkout** (`woocommerce/checkout/*` + `inc/woocommerce-checkout.php`).
6. **Cart, mini-cart, header search**.
7. **Home page sections** (hero, mosaic, discover, newsletter, brand carousel, video reviews) — last because most visually sensitive; this is also where the Noto Serif → Proxima Nova flip happens per block.

For each component:
- Replace `font-size` literals with `var(--wp--preset--font-size--*)` tokens. Lift anything below 16px to the floor.
- Replace pink hex with `var(--wp--preset--color--<role-slug>)` per the designer's role map (renamed slugs).
- Replace near-blacks (`#111`, `#1f1f1f`, `#1a1a1a`, `#222`, `#0f1728`, `#2f2f2f`, `#2b2b2b`, `#101828`) with `var(--noyona-color-text-main)`.
- Replace mid-grays (`#666`, `#555`, `#777`, `#444`, `#4f4f4f`, `#888`, `#999`) with `var(--noyona-color-text-muted)`.
- Migrate CTA `border-radius` (including `999px` pills) to `var(--noyona-radius-cta)` (16px).
- Migrate card `border-radius` to `var(--noyona-radius-card)` (24px), padding to `var(--noyona-card-pad-*)`.
- Replace `letter-spacing: -5%` (already fixed in Phase 1) usage in any newly touched files.
- Convert `apple-system` / `"Proxima Nova Light"` font-family declarations to `var(--wp--preset--font-family--proxima-nova)`.
- Drop `!important` where the new token wins on specificity.
- Keep border colors contextual — do not auto-replace.

### Phase 3 — Cleanup
- Remove the duplicate `body/h1/h2/h3` block from `style.css:111-137`.
- Remove the `noto-serif-semicondensed` font-family from `theme.json` if no hero/discover/mosaic usage remains.
- Delete the legacy `ink` palette slug if no consumers remain.
- Convert the footer's two overlapping rule sets (line 3-308 vs. 405-765) into one source of truth.
- Replace the `(provisional)` status tokens with designer-final hexes when they arrive.
- Final type/color audit using the same script as `typograph_report.text`; expect the pink count to collapse from 35+ to 3, near-blacks from 134 references to 0 (all `--text-main`), grays from 124 to 0 (all `--text-muted`).

---

## Appendix A — Approved hex → CSS var quick map

| Hex | Token (proposed) | CSS var |
|---|---|---|
| `#E199A4` | `brand-pink-primary` | `var(--wp--preset--color--brand-pink-primary)` |
| `#EFB5BE` | `brand-pink-soft`    | `var(--wp--preset--color--brand-pink-soft)`    |
| `#FBDDE2` | `brand-pink-pale`    | `var(--wp--preset--color--brand-pink-pale)`    |
| `#000000` | `text-main` / `border-strong` | `var(--wp--preset--color--text-main)` / `--border-strong` |
| `#525252` | `text-muted` / `placeholder` / `status-info` | `var(--wp--preset--color--text-muted)` |
| `#FFFFFF` | `white` | `var(--wp--preset--color--white)` |

## Appendix B — Files inventoried for this report

- `theme.json`
- `style.css`
- `assets/css/header.css`, `assets/css/footer.css`, `assets/css/single-product.css`
- All `blocks/*/style.css` (~35 files)
- All `templates/*.html` and `parts/*.html`
- `inc/helpers.php`, `inc/shortcodes.php`, `inc/theme-setup.php`, `inc/woocommerce-pdp.php`, `inc/woocommerce-checkout.php`
- `typograph_report.text` (prior audit, cross-referenced)
