# Typography & Color Token Implementation Plan

> **Status:** Documentation only. No theme.json, style.css, or component CSS changes were made by this report. Phase 1 implementation is proposed at the end and awaits approval.

---

## 1. Executive summary

The Noyona child theme currently runs on a minimal `theme.json` (4 font sizes, 1 color slug) and a `style.css` that re-declares the same body/H1/H2/H3 rules, then drifts further across 30+ block stylesheets that each hard-code their own sizes and pinks. The previous audit (`typograph_report.text`) and this scan confirm:

- The token system covers only H1/H2/H3 and body; H4–H6, label, button, caption, helper, and input have **no global tokens** even though all of those roles are used in markup.
- The color palette has **one entry** (`#333333` ink). The rest of the site uses **35+ pink variants**, **9+ near-black text colors**, and **7+ gray tones**, only a fraction of which match the designer-approved set.
- H1 in `theme.json` still maps to Noto Serif SemiCondensed. The **designer typography document** specifies the brand font direction as **Hero Regular + Poppins**, not Proxima Nova — see the correction note below.
- Five CSS files emit **invalid** `letter-spacing: -5%` (percentages are not valid for `letter-spacing` — browsers drop the declaration; designer confirmed Figma -5% should be expressed as `-0.05em`).
- CTA radii are split between 16px (designer-approved for **all** buttons) and 999px pill (124 occurrences), plus 12px/14px/18px stragglers.

> **Font-family correction note (this revision):** Earlier versions of this plan said *"Proxima Nova everywhere, Times New Roman Italic on the newsletter em."* That was wrong. The designer typography source of truth is:
> - **Hero Regular** — hero titles, primary page headings, campaign headlines, key brand statements, expressive brand moments.
> - **Poppins Bold** — subheadings, section titles, feature titles, navigation, important CTAs.
> - **Poppins Regular** — body, descriptions, product details, form labels, captions, helper text, supporting content.
>
> The existing theme uses Proxima Nova, Noto Serif SemiCondensed, Times New Roman, Georgia, and an Apple system stack. Font-family migration to Hero Regular + Poppins must therefore be handled carefully — it is **not** a simple "flip everything to Proxima Nova." See §5 #14, §6.1, §6.4, §10, §14, §15, §16 for the corrected direction.

> **Alignment correction note (this revision):** Earlier revisions of this plan misrepresented the designer values for several topics. The plan is now aligned to the **four designer documents** (Color Palette Guide, Typography Guide, Image & Icon Layout Guidelines, Layout Grid Specification). Corrections in this pass:
> - **Typography scale** — Heading sizes/weights from older revisions were wrong. The designer Typography Guide values are: H1 = 64 / 48 / ~40 (mobile inferred), **Regular 400**; H2 = 48 / 40 / 32, **Bold 700**; H3 = 36 / 32 / 28, **Bold 700**; H4 = 32 / 28 / 24, **Bold 700**; H5 = 24 / 22 / 20, **Bold 700**; body = 16/16/16, **Regular 400**; nav & button text = 16/16/16, **Bold 700**. See §3 and §6.2.
> - **Line-height** — The Typography Guide specifies leading = **1.5× font-size**, not `normal`. The earlier "`line-height: normal` everywhere is designer-confirmed" claim is **withdrawn** and tagged as a conflict pending re-confirmation. See §5 #6.
> - **Letter-spacing** — `-0.05em` is the **technical bug-fix translation** for the existing invalid `letter-spacing: -5%` declarations only. It is **not** approved as a universal tracking system. See §5 #7.
> - **Color palette naming** — The Color Palette Guide names `#EFB5BE` as the **Primary** (Soft Pink); `#E199A4` is **Secondary** (Muted Rose); `#FBDDE2` is **Secondary** (Light Blush). The earlier proposed `brand-pink-primary: #E199A4` slug was wrong. See §4 and §6.3.
> - **Color accessibility** — Added explicit Color Palette Guide accessibility rules (no color-alone state, WCAG contrast minimums, dark text on pink fills, pale-pink-on-white non-text contrast caution). See §4.
> - **Image & icon layout** — Added a new §17 capturing the Image & Icon Layout Guidelines (hero crops, card columns, product zoom, touch target minimums).

**Designer clarifications received (this revision):** input bg = `#FFFFFF`; borders are **contextual** (mix of pink / `#525252` / none — not all `#000`); CTA radius 16px applies to **all** buttons (Phase 2 migration); card radius 24px + padding 15/12/10 (D/T/M) applies site-wide (Phase 2 migration); status colors = standard e-commerce greens/yellows/reds, provisional now and tunable later; 16px is the **minimum** text size site-wide; label/button/caption/helper/input weights remain Claude's provisional suggestions (Typography Guide does not specify them, only the documented heading + body + nav/button scale); **font-family direction = Hero Regular + Poppins (not Proxima Nova).**

**Still blocked:** pink role mapping (CTA fill / CTA hover / soft bg / accent — distinct from the Primary/Secondary naming in the Color Palette Guide); the primary CTA hover color; **Hero Regular / Poppins font asset availability** (licensing, file location, or approved import source); **mobile H1 size** (designer mobile table malformed — line-height implies ~40px, awaiting confirmation); **line-height system** (typography doc says 1.5× vs. prior project note saying `normal`); **CTA text color/accessibility** (Color Palette Guide implies dark text on pink fills; current white-on-pink CTAs need contrast verification); **`.newsletter-strip__title em` target style** (Times New Roman not in designer doc — Hero Regular vs. Poppins Italic vs. other).

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
- **Noto Serif SemiCondensed** (token): used in `blocks/hero-banner/style.css:94, 103`, `blocks/discover-face-banner/style.css:52`, `blocks/mosaic-grid/style.css:77`, and the H1 element style in `theme.json`. **Designer target = Hero Regular** for hero / page / campaign / key-brand-statement headings (not Proxima Nova). Per-occurrence intent must be confirmed before migration.
- **Times New Roman** (literal): `blocks/newsletter-strip/style.css:36`. **Legacy / current implementation only** — the designer typography document does not list Times New Roman. Treat as existing usage, not approved future direction. Whether `.newsletter-strip__title em` should migrate to Hero Regular, Poppins Italic, or another designer-approved emphasis style is a Phase 2 clarification (see §5 #15).
- **Georgia** (legacy/email): `assets/css/footer.css:441` — looks like an email/invoice block; not in scope per the brief.
- **Apple system stack** (non-token): `blocks/inquiry/style.css:2`, `blocks/contact/style.css:8` — these bypass `--wp--preset--font-family--proxima-nova` and fall back to OS UI font. Should adopt the designer-target token (`poppins` for body/form contexts) in Phase 2, once font assets are confirmed.
- **"Proxima Nova Light"** literal: `blocks/blogs-view/style.css:309` — a different weight string outside the token system. Map to designer target in Phase 2.

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

> Values taken directly from the designer **Typography Guide**. Earlier revisions of this plan listed `H1 96/64/48 @ 700`, `H2 55/40/32 @ 700`, `H3 24/24/20 @ 600`, `Paragraph 20/18/16 @ 400`, all `line-height: normal`, all `letter-spacing: -0.05em`. **Those values are withdrawn** — they were not from this designer document. The corrected table below is the source of truth.

| Role | Desktop | Tablet | Mobile | Weight | Line-height | Letter-spacing |
|---|---|---|---|---|---|---|
| H1 | 64px | 48px | ~40px (inferred — see §5 #16) | **Regular 400** | 1.5 × font-size | not specified |
| H2 | 48px | 40px | 32px | **Bold 700** | 1.5 × font-size | not specified |
| H3 | 36px | 32px | 28px | **Bold 700** | 1.5 × font-size | not specified |
| H4 | 32px | 28px | 24px | **Bold 700** | 1.5 × font-size | not specified |
| H5 | 24px | 22px | 20px | **Bold 700** | 1.5 × font-size | not specified |
| Body | 16px | 16px | 16px | **Regular 400** | 1.5 × font-size | not specified |
| Navigation & Button text | 16px | 16px | 16px | **Bold 700** | 1.5 × font-size | not specified |
| Label / caption / helper / input | not specified by Typography Guide | — | — | Claude-provisional (§5 #9) | 1.5 (assumed) | not specified |

**Important corrections:**

- **H1 weight is 400 (Regular), not 700.** The H1 font is Hero Regular, used at Regular weight.
- **H2–H5 weights are 700 (Bold).** This matches Poppins Bold.
- **Mobile H1 size is inferred ~40px.** The designer mobile table was malformed; the Typography Guide states line-height = 1.5 × font-size and mobile H1 line-height appears to be 60px, which implies a 40px font-size. **Pending designer confirmation** (§5 #16).
- **Line-height = 1.5 × font-size**, not `normal`. See §5 #6 — the earlier "`line-height: normal` everywhere" note is in conflict with the Typography Guide and must be re-confirmed.
- **Letter-spacing is not specified** by the Typography Guide. `-0.05em` is the *technical translation* used to repair invalid `letter-spacing: -5%` declarations in five files (see §5 #7); it is **not** a universal tracking system.
- **WCAG text spacing** must be supported: layouts must not clip or break when users increase line-height, paragraph spacing, letter-spacing, or word-spacing (WCAG 2.2 SC 1.4.12). Avoid fixed-height text containers that would clip resized text.

**Fonts (designer source of truth — Typography Guide):**

- **Hero Regular** — hero titles, primary page headings, campaign headlines, key brand statements, expressive brand moments. Used at Regular weight.
- **Poppins Bold** — subheadings, section titles, feature titles, navigation, important CTAs.
- **Poppins Regular** — body copy, descriptions, product details, form labels, captions, helper text, and supporting content.

The earlier *"Proxima Nova everywhere, Times New Roman Italic on `.newsletter-strip__title em`"* phrasing is **superseded** and no longer the designer direction. The current `.newsletter-strip__title em` Times New Roman usage is treated as legacy; its target style is a Phase 2 clarification (see §5 #15).

---

## 4. Designer-approved color/UI guide (recap)

### 4.1 Color Palette Guide naming (corrected)

Per the designer **Color Palette Guide**:

| Hex | Designer name | Designer role |
|---|---|---|
| `#EFB5BE` | Soft Pink | **Primary** |
| `#E199A4` | Muted Rose | Secondary |
| `#FBDDE2` | Light Blush | Secondary |

> The earlier proposed slug `brand-pink-primary: #E199A4` was **wrong**. `#E199A4` is **Secondary (Muted Rose)** per the Color Palette Guide, not the primary color. The token proposal in §6.3 has been corrected. The role mapping for *interactive* roles (CTA fill / CTA hover / soft bg / accent / badge) is a separate question and still pending (§5 #1) — Primary/Secondary in the color guide is a *palette role*, not necessarily a CTA-fill role.

### 4.2 Other colors

- **Text / utility:** main `#000000`, muted / helper / placeholder / info `#525252`, white `#FFFFFF`.
- **Border:** **contextual** — some pink, some `#525252`, some components have no border. `#000000` is used only as a strong outline where needed. **No global border replacement.**
- **Input background:** `#FFFFFF` (designer-confirmed correction; `#525252` is text only, not a background).
- **Placeholder:** `#525252`.
- **Status:** success = light green, warning = light yellow, error = light red — use standard e-commerce defaults provisionally and tune later (no designer-final hexes).

### 4.3 Typography rules from the Typography Guide (not the Color/UI guide)

- **Line-height:** **1.5 × font-size** per the Typography Guide (see §3). The older "`line-height: normal`" note is in conflict with this and is pending re-confirmation (§5 #6).
- **Letter-spacing:** **not specified** by the Typography Guide. `-0.05em` is **only** the technical translation used to fix invalid `letter-spacing: -5%` declarations in five files (§2.8, §5 #7). Do not roll out `-0.05em` as a universal tracking system.

### 4.4 UI rules

Card radius 24px; card padding 15 / 12 / 10 (D/T/M); CTA radius 16px; CTA padding 10 or 15px; screen padding 110 / 50 / 20 (D/T/M — flagged as legacy/page-inset, see §5 #18 and §8.5).

### 4.5 Color accessibility rules (from the Color Palette Guide)

- **Do not rely on color alone** to convey state, status, or interactivity. Pair color with icons, text, thickness, underline, or another non-color cue.
- **Normal text must meet WCAG contrast minimum** (AA: 4.5:1 for normal text, 3:1 for large text and non-text UI).
- **White text on pastel pinks fails contrast.** Do not use white text on `#EFB5BE` (Soft Pink), `#E199A4` (Muted Rose), or `#FBDDE2` (Light Blush) buttons or panels without an explicit designer override that passes contrast testing.
- **Pink button backgrounds should use black (`#000000`) or very dark text** unless a contrast test proves the alternative is accessible.
- **Pale pink borders on white** can fail non-text contrast (3:1 minimum); use darker rose or dark outlines where the border conveys structure or state.
- **Interactive states must not be communicated by pink alone.** Hover / focus / active / disabled must add at least one non-color cue (icon, text label, outline thickness, underline, or shape change).
- **Phase 2 CTA migration must verify contrast.** Do not assume any existing white-on-pink CTA text is accessible. When migrating CTAs to the designer color tokens, run contrast checks and prefer dark text on pink fills per this guide. (See §5 #18 and §16.)

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
| 6 | **Line-height system (1.5 vs. `normal`)** | 🔴 Conflict — re-confirmation needed | The Typography Guide specifies leading = **1.5 × font-size**. A prior project clarification said `line-height: normal`. These conflict. **Treat 1.5 as the source of truth** (designer document) and treat the prior "`normal`" note as superseded pending designer re-confirmation. **WCAG 2.2 SC 1.4.12 Text Spacing** must be respected: layouts must not clip or break when users increase line-height, paragraph-spacing, letter-spacing, or word-spacing. Avoid fixed-height text containers that would clip text. **No line-height migration in Phase 1.** |
| 7 | **Letter-spacing `-5%` → `-0.05em` (bug-fix scope only)** | 🟢 Resolved for the 5 invalid declarations only · 🔴 Universal tracking not approved | `-0.05em` is the **technical translation** for the existing **invalid** `letter-spacing: -5%` declarations cataloged in §2.8 (5 locations). The Typography Guide does **not** specify universal letter-spacing. Phase 1 fixes only those 5 declarations. **Do not roll out `-0.05em` as a global tracking rule.** Broader tracking rules remain Phase 2 / designer-confirmation work. |
| 8 | **H4 / H5 sizes** | 🟢 Resolved by Typography Guide | Designer Typography Guide values now used (not earlier provisional): H4 = 32 / 28 / 24, **Bold 700**; H5 = 24 / 22 / 20, **Bold 700**. See §3 and §6.2. H6 skipped (unused in markup). |
| 9 | **Label / button / caption / helper / input weights** | 🟡 Provisional (Typography Guide silent) | The Typography Guide specifies sizes/weights for headings, body, and nav/button only. For label / caption / helper / input it is silent. Claude-provisional weights remain: label 600, button 700 (matches nav/button 700), caption/badge 600, helper/small 400, input 400. |
| 10 | **CTA radius 16px** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed **16px applies to all buttons** (including current pills). But the site has 124 × `999px` pills; flipping globally in Phase 1 would change the visual identity site-wide. **Migrate in a controlled Phase 2** component-by-component, with QA per family. |
| 11 | **Card radius 24px + padding 15/12/10 (D/T/M)** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed site-wide. Migrate per component in Phase 2 — not forced globally in Phase 1 — because current cards vary (18/14/12/8px) and many have padding tied to internal grid math. |
| 12 | **Breakpoints and legacy screen padding** | 🟢 Breakpoints resolved · 🟡 screen-padding values pending confirmation | Standard breakpoints are adopted: desktop ≥ 1024px, tablet 768–1023px, mobile ≤ 767px. The prior `110 / 50 / 20` screen-padding values are retained only as **legacy large-section / page-inset tokens** pending designer confirmation. They are **not from the Layout Grid Specification**, whose universal grid outer margins are `24 / 24 / 16`. See §5 #19 and §8.5. |
| 13 | **Minimum text size 16px** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed 16px is the floor even on mobile. The 200+ existing 12–15px declarations cataloged in §2.6 are **flagged as Phase 2 cleanup**, not changed in Phase 1. |
| 14 | **Font-family direction (Hero Regular + Poppins)** | 🟢 Direction resolved · 🔴 Asset availability blocker | **Designer source of truth = Hero Regular + Poppins** (see §3 and the executive-summary correction note). The existing implementation still uses Proxima Nova / Noto Serif SemiCondensed / Times New Roman / Georgia / Apple system stack / "Proxima Nova Light" literal. **Implementation is blocked on:** (a) whether Hero Regular and Poppins assets are already loaded; (b) if not, where the licensed font files or approved `@import` source lives; (c) confirmed fallback stacks. **Do not globally switch font-family in Phase 1** until asset availability is confirmed and migration is explicitly approved. Existing `proxima-nova` and `noto-serif-semicondensed` tokens must remain during migration so current consumers keep working. |
| 15 | **Newsletter emphasis (`.newsletter-strip__title em`)** | 🔴 Clarification needed | Current implementation uses Times New Roman (literal, `blocks/newsletter-strip/style.css:36`). The designer typography document does **not** list Times New Roman, so this is legacy / current implementation, not approved future direction. Designer must confirm whether `.newsletter-strip__title em` should migrate to **Hero Regular**, **Poppins Italic**, or another designer-approved emphasis style. **Do not change the newsletter font in Phase 1 and do not add a `times-italic` token to `theme.json`.** |
| 16 | **Mobile H1 size** | 🔴 Clarification needed | The designer mobile typography table is malformed in the source document. The Typography Guide states line-height = **1.5 × font-size**, and mobile H1 line-height appears to be **60px**, which implies a **mobile H1 of 40px**. This plan uses **~40px (inferred)** for mobile H1 in §3 and §6.2 pending designer confirmation. Do not implement a final mobile H1 token until confirmed. |
| 17 | **Color slug naming vs. Color Palette Guide** | 🟢 Resolved by Color Palette Guide | The Color Palette Guide names `#EFB5BE` as **Primary (Soft Pink)**, `#E199A4` as **Secondary (Muted Rose)**, `#FBDDE2` as **Secondary (Light Blush)**. The earlier proposed slug `brand-pink-primary: #E199A4` is **wrong** and has been corrected in §6.3 to `brand-pink-soft` (Primary, `#EFB5BE`), `brand-pink-muted-rose` (Secondary, `#E199A4`), and `brand-pink-light-blush` (Secondary, `#FBDDE2`). Interactive role mapping (CTA fill / CTA hover / soft bg / accent) is a separate question (§5 #1). |
| 18 | **CTA text color / accessibility on pink fills** | 🔴 Decision needed | Per the Color Palette Guide, **white text on pastel pinks fails WCAG contrast**. Pink CTAs should use **dark text** (typically `#000000`) unless designer provides an accessible alternative. The existing site likely has white-on-pink CTA text in multiple places; this **must be audited in Phase 2** before any CTA color migration ships. Do **not** assume current white-on-pink CTAs are accessible. (§4.5, §16.) |
| 19 | **Screen padding `110 / 50 / 20` not in any designer doc** | 🟡 Legacy / pending confirmation | The `110 / 50 / 20` screen-padding values are **not from** the Layout Grid Specification (which uses 24 / 24 / 16 outer margins) or any other designer doc. They are prior project clarification values. Retained in §7 / §8.5 as **legacy large-section / page-inset tokens** pending designer confirmation of whether they should remain in use, be replaced by the universal grid margin, or be removed. |

---

## 6. Proposed `theme.json` tokens *(not yet applied)*

### 6.1 Font families

**Designer target tokens (proposed — NOT yet implementable until font assets are confirmed):**

```json
{
  "fontFamily": "\"Hero Regular\", serif",
  "name": "Hero Regular",
  "slug": "hero-regular"
},
{
  "fontFamily": "\"Poppins\", system-ui, -apple-system, \"Segoe UI\", sans-serif",
  "name": "Poppins",
  "slug": "poppins"
}
```

**Notes:**

- These tokens should **not** be added to `theme.json` until **font asset availability is confirmed** (see §5 #14): Hero Regular and Poppins files / `@import` source / licensing / fallback stacks all resolved.
- The existing `proxima-nova` and `noto-serif-semicondensed` tokens must **remain in `theme.json` during migration** for backward compatibility — current block CSS and the H1 element style still reference them. Removal happens only in Phase 3, after every consumer has migrated.
- Actual font-family consumption (assigning `hero-regular` to H1 / hero / campaign headings, assigning `poppins` to body / nav / CTAs) belongs in **Phase 2**, not Phase 1, and only once the assets are confirmed and migration is explicitly approved.
- **No `times-italic` token is proposed.** The designer typography document does not list Times New Roman; the existing `.newsletter-strip__title em` usage is legacy and its target style is a Phase 2 clarification (see §5 #15).

### 6.2 Font sizes (add new slugs; keep existing for backward compatibility)

> Sizes/weights are taken directly from the designer **Typography Guide** (§3). The earlier "tightened lower-bound" provisional values (H1 `clamp(48px, 6vw, 96px)`, H2 `clamp(32px, 4.2vw, 55px)`, H3 `clamp(20px, 2vw, 24px)`, H4 `clamp(18px, 1.6vw, 22px)`, H5 `clamp(16px, 1.2vw, 18px)`, all at heading weights of 700/600) are **withdrawn** — they did not match the Typography Guide. Existing `h-1` / `h-2` / `h-3` / `body` slugs in `theme.json` keep their current values during migration so nothing breaks, but the proposed new values below are the migration target.

| Proposed slug | Value (D / T / M) | Weight | Notes |
|---|---|---|---|
| `h-1` (revise target; keep current slug working) | `clamp(40px, 5vw, 64px)` | **Regular 400** | Designer Typography Guide: 64 / 48 / ~40 (mobile inferred §5 #16). H1 is **Hero Regular**, not Bold. |
| `h-2` (revise target) | `clamp(32px, 3.75vw, 48px)` | **Bold 700** | Designer Typography Guide: 48 / 40 / 32. |
| `h-3` (revise target) | `clamp(28px, 2.8vw, 36px)` | **Bold 700** | Designer Typography Guide: 36 / 32 / 28. |
| `h-4` (NEW per Typography Guide) | `clamp(24px, 2.5vw, 32px)` | **Bold 700** | Designer Typography Guide: 32 / 28 / 24 — used in account card titles, modal titles. |
| `h-5` (NEW per Typography Guide) | `clamp(20px, 1.9vw, 24px)` | **Bold 700** | Designer Typography Guide: 24 / 22 / 20 — used in shop filter section titles. |
| `h-6` | skip | — | unused in markup; Typography Guide does not specify. |
| `body` (revise target) | `16px` | **Regular 400** | Designer Typography Guide: 16/16/16 fixed at all viewports. No clamp — earlier `clamp(16px, 1.4vw, 20px)` scaled body above 16px, which the Typography Guide does not authorize. |
| `nav-button` (NEW per Typography Guide) | `16px` | **Bold 700** | Designer Typography Guide: navigation text + button text = 16/16/16 Bold. Use for `.button`, `.noyona-*-submit`, CTAs, primary nav. |
| `label` (NEW, provisional — §5 #9) | `16px` | 600 | Typography Guide silent — Claude-provisional |
| `caption` (NEW, provisional — §5 #9) | `16px` | 600 | Typography Guide silent — Claude-provisional |
| `helper` (NEW, provisional — §5 #9) | `16px` | 400 | Typography Guide silent — Claude-provisional |
| `input` (NEW, provisional — §5 #9) | `16px` | 400 | Typography Guide silent — Claude-provisional |

**Notes:**

- **Line-height** for all tokens should be **1.5** (Typography Guide; §3, §5 #6). The earlier "`line-height: normal`" claim is withdrawn.
- **Letter-spacing** is **not specified** by the Typography Guide. Do not bake `-0.05em` into these tokens. The `-0.05em` fix is scoped to the 5 invalid declarations in §2.8 only (§5 #7).
- **Minimum text size 16px** is approved. All 12 / 13 / 14 / 15px declarations cataloged in §2.6 are flagged for Phase 2 cleanup — not changed in Phase 1.
- The previously-proposed `button` slug (`clamp(18px, 1.6vw, 24px)` weight 700) is **replaced** by the designer-confirmed `nav-button` slug at **16px Bold 700**. Earlier CTA size assumptions of 18–24px are no longer the designer target.
- Existing `h-1` / `h-2` / `h-3` / `body` slugs in `theme.json` are not deleted in Phase 1 — they keep their current values until Phase 2 component migration. Adding the **new** tokens here is additive; **assigning them to elements** is Phase 2 work.

### 6.3 Color palette (additive — slugs aligned to the Color Palette Guide)

```jsonc
// === Designer Color Palette Guide ===
{ "slug": "brand-pink-soft",         "name": "Brand Pink Soft / Primary",          "color": "#EFB5BE" },  // Color Palette Guide: PRIMARY (Soft Pink)
{ "slug": "brand-pink-muted-rose",   "name": "Brand Pink Muted Rose / Secondary",  "color": "#E199A4" },  // Color Palette Guide: SECONDARY (Muted Rose)
{ "slug": "brand-pink-light-blush",  "name": "Brand Pink Light Blush / Secondary", "color": "#FBDDE2" },  // Color Palette Guide: SECONDARY (Light Blush)
{ "slug": "text-main",               "name": "Text Main",                          "color": "#000000" },
{ "slug": "text-muted",              "name": "Text Muted",                         "color": "#525252" },  // also covers helper / placeholder / info
{ "slug": "white",                   "name": "White",                              "color": "#FFFFFF" },
{ "slug": "input-bg",                "name": "Input Background",                   "color": "#FFFFFF" },  // CONFIRMED #FFFFFF (NOT #525252)
{ "slug": "placeholder",             "name": "Placeholder",                        "color": "#525252" },
{ "slug": "border-strong",           "name": "Border Strong",                      "color": "#000000" },  // available, but borders are contextual — do not globally apply
{ "slug": "status-info",             "name": "Status Info",                        "color": "#525252" },

// === Provisional (Claude-suggested, designer said "standard e-commerce, adjust later") ===
{ "slug": "status-success",          "name": "Status Success (provisional)",       "color": "#E6F4EA" },  // NOT DESIGNER-FINAL
{ "slug": "status-warning",          "name": "Status Warning (provisional)",       "color": "#FFF8E1" },  // NOT DESIGNER-FINAL
{ "slug": "status-error",            "name": "Status Error (provisional)",         "color": "#FDEAEA" }   // NOT DESIGNER-FINAL
```

**Important corrections (vs. earlier revisions):**

- **The earlier `brand-pink-primary: #E199A4` slug is withdrawn.** Per the Color Palette Guide (§4.1, §5 #17), `#E199A4` is **Secondary (Muted Rose)**, not the primary color. The primary color is `#EFB5BE` (Soft Pink). The slug for `#E199A4` is now `brand-pink-muted-rose`.
- Slug names above are aligned to the **palette role** (Primary/Secondary) named in the Color Palette Guide. They are **not** the same as the **interactive role** (CTA fill / CTA hover / soft bg / accent / badge) — that interactive mapping is still pending (§5 #1). When the interactive mapping lands, additional aliasing slugs (e.g. `cta-fill`, `cta-hover`) may be added; the palette-role slugs above remain.
- **CTA text color on pink fills is not white.** Per the Color Palette Guide accessibility rules (§4.5, §5 #18), pink CTA fills should use **dark text** unless designer provides an accessible alternative. Do not assume the existing white-on-pink CTAs are correct.
- `border-strong` is **available** but **must not be globally applied** to all borders — borders are contextual (some pink, some `#525252`, some none).
- Status tokens are explicitly **provisional** with `(provisional)` in the slug name so they don't get mistaken for final values.
- The existing `ink = #333333` slug stays in place so any current `var(--wp--preset--color--ink)` references keep working.

### 6.4 H1 element font-family — **do NOT flip in Phase 1**

Earlier revisions of this plan proposed flipping `styles.elements.h1.typography.fontFamily` from Noto Serif SemiCondensed to Proxima Nova in Phase 1. **That instruction is withdrawn.**

Reasoning:

- The designer typography source of truth is **Hero Regular + Poppins** (see §3, §5 #14, executive-summary correction note). Flipping H1 to Proxima Nova would entrench a font that is **not** the designer's brand direction.
- Hero / page / campaign / key-brand-statement headings should target **Hero Regular**, not Proxima Nova.
- Hero Regular asset availability is unresolved (§5 #14).

**Phase 1 instruction:** keep the current H1 font-family unchanged. Do not modify `styles.elements.h1.typography.fontFamily` in `theme.json`.

**Phase 2 instruction (gated):** once Hero Regular assets and approved fallback stacks are confirmed and the `hero-regular` token is added per §6.1, the H1 / hero / campaign / key-brand-statement headings may migrate to `var(--wp--preset--font-family--hero-regular)` as part of a controlled typography migration (see §16 font-family pre-flight). Until then, leaving the current H1 alone avoids an accidental visible brand shift.

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

  /* === Screen padding scale (large-section / page-inset tokens) === */
  /* These are marketing/page-section inset tokens — NOT the universal layout grid outer margin.
     See §8 for the universal 12/8/4 grid margin tokens (24 / 24 / 16). Pending designer
     confirmation of where these large insets should remain in use. */
  /* Desktop ≥ 1024px → 110px · Tablet 768–1023px → 50px · Mobile ≤ 767px → 20px */
  --noyona-screen-pad-desktop: 110px;
  --noyona-screen-pad-tablet: 50px;
  --noyona-screen-pad-mobile: 20px;
  /* Fluid screen padding (single var consumers can use) */
  --noyona-screen-pad: clamp(20px, 6vw, 110px);

  /* === Typography helpers === */
  /* -0.05em is the bug-fix translation for the 5 invalid `letter-spacing: -5%` declarations
     in §2.8. It is NOT a universal tracking system — the Typography Guide does not specify
     letter-spacing. Use this var ONLY in those 5 places (§5 #7). */
  --noyona-letter-spacing-bugfix: -0.05em;
  /* Designer Typography Guide specifies line-height = 1.5 × font-size. Older "normal"
     note is withdrawn (§5 #6). Phase 2 sets line-height: 1.5 on consumers; no global
     var is required since it's a single literal value. */

  /* === Minimum text size (designer floor) === */
  --noyona-text-min: 16px;

  /* === Brand color shorthands mirroring theme.json (so non-block CSS can use them) ===
     Slug names follow the designer Color Palette Guide (PRIMARY/SECONDARY).
     Interactive role mapping (CTA fill / CTA hover / soft bg) is still pending (§5 #1) —
     do NOT swap colors site-wide until that interactive mapping is delivered. */
  --noyona-color-pink-soft:        var(--wp--preset--color--brand-pink-soft,        #EFB5BE);  /* PRIMARY (Soft Pink) */
  --noyona-color-pink-muted-rose:  var(--wp--preset--color--brand-pink-muted-rose,  #E199A4);  /* SECONDARY (Muted Rose) */
  --noyona-color-pink-light-blush: var(--wp--preset--color--brand-pink-light-blush, #FBDDE2);  /* SECONDARY (Light Blush) */
  --noyona-color-text-main:        var(--wp--preset--color--text-main,              #000000);
  --noyona-color-text-muted:       var(--wp--preset--color--text-muted,             #525252);
  --noyona-color-white:            var(--wp--preset--color--white,                  #FFFFFF);
  --noyona-color-input-bg:         var(--wp--preset--color--input-bg,               #FFFFFF);
  --noyona-color-placeholder:      var(--wp--preset--color--placeholder,            #525252);
  /* Strong border available but borders are contextual — NOT for global application */
  --noyona-color-border-strong:    var(--wp--preset--color--border-strong,          #000000);

  /* CTA hover token intentionally NOT defined yet — pending designer mapping */
  /* CTA text color on pink fills should be dark (#000000) per Color Palette Guide
     accessibility rules (§4.5, §5 #18). Verify per CTA in Phase 2 — do NOT assume
     existing white-on-pink CTAs are accessible. */
}
```

All `var(--wp--preset--*)` references include hard-coded fallbacks so the variables still resolve even before `theme.json` is updated. This makes the rollout safely reversible: drop the `:root` block, drop the new `theme.json` slugs, done.

---

## 8. Designer-approved responsive layout grid

> **Status:** Planning only. The grid baseline tokens proposed below may be added to `:root` in Phase 1 *if approved*. No container, product grid, hero, or page layout is migrated to the new grid until Phase 2.

### 8.1 Cross-device grid matrix

| Viewport | Columns | Gutter | Outer margin | Column type | Grid type | WCAG requirement |
|---|---:|---:|---:|---|---|---|
| Mobile (≤ 767px) | 4 | 16px | 16px | Fluid / % / fr | Stretch | Single-column reflow at 320px / no horizontal scroll |
| Tablet (768–1023px) | 8 | 16px | 24px | Fluid / % / fr | Stretch | Reflows |
| Desktop (≥ 1024px) | 12 | 24px | 24px | Fluid / % / fr | Stretch | 12-column fluid grid |

### 8.2 Role of this grid

This is the **global baseline grid** for:

- page / container layout
- e-commerce product layouts
- archive / category pages
- search / product grids
- responsive section layout planning

It is the canonical responsive layout system once Phase 2 layout migration begins. Component-internal grids (cards, mini-cart, modal content) may continue to use their own layout math as long as they fit inside this baseline.

### 8.3 Designer rationale

- **12 columns on desktop** support 2-up, 3-up, 4-up, and 6-up e-commerce layouts.
- **8 columns on tablet** support 2-up and 4-up modular rows.
- **4 columns on mobile** support simplified stacked layouts or two-column internal patterns.
- Spacing honors the **8px base grid** (8 / 16 / 24).
- Desktop uses 24px gutters and 24px margins.
- Tablet keeps 24px outer margins but drops gutters to 16px to preserve usable active content width.
- Mobile uses 16px margins and 16px gutters to maximize horizontal space.

References cited by designer: WCAG 2.2; W3C CSS Grid Layout Module Level 1; Google Material Design Responsive UI Grid; Bootstrap 5.3 Gutters; Nielsen Norman Group on grids in interface design.

### 8.4 Accessibility note (WCAG 2.2 SC 1.4.10 Reflow)

The grid must support **WCAG 2.2 SC 1.4.10 Reflow**:

- Layout must work down to **320px** with no horizontal scrolling.
- Main layout columns must be **fluid**, not fixed px.
- Avoid fixed-width px columns for primary layout containers.
- Prefer CSS Grid using `repeat(n, minmax(0, 1fr))`, percentage widths, or other flexible column systems.

Fixed px columns for the main responsive layout are **not acceptable** because they can fail reflow.

### 8.5 Screen padding tokens vs. grid margin tokens (resolving the conflict)

The existing screen padding tokens in §7 —

```css
--noyona-screen-pad-desktop: 110px;
--noyona-screen-pad-tablet: 50px;
--noyona-screen-pad-mobile: 20px;
--noyona-screen-pad: clamp(20px, 6vw, 110px);
```

— are **legacy large-section / page-inset tokens** (large marketing sections, hero insets, home page bands). **They are not from the designer Layout Grid Specification** — that document specifies `24 / 24 / 16` outer margins for the universal grid. The `110 / 50 / 20` values are prior project clarification, not designer-doc-aligned (§5 #19). They are retained here pending designer confirmation of whether they should remain in use, be replaced by the universal grid margin, or be removed.

The new grid margin tokens (24 / 24 / 16) are the canonical baseline outer margin for the universal 12 / 8 / 4 layout grid. The two scales coexist:

| Token | Role | Values (D / T / M) |
|---|---|---|
| `--noyona-screen-pad-*` | Large-section / page-inset (marketing) | 110 / 50 / 20 |
| `--noyona-grid-margin-*` | Universal grid outer margin | 24 / 24 / 16 |

### 8.6 Proposed layout grid variables *(documentation only — not yet applied)*

```css
:root {
  /* === Layout grid baseline (designer-approved; Phase 2 consumers opt in per layout/container) === */
  --noyona-grid-columns-desktop: 12;
  --noyona-grid-columns-tablet: 8;
  --noyona-grid-columns-mobile: 4;

  --noyona-grid-gutter-desktop: 24px;
  --noyona-grid-gutter-tablet: 16px;
  --noyona-grid-gutter-mobile: 16px;

  --noyona-grid-margin-desktop: 24px;
  --noyona-grid-margin-tablet: 24px;
  --noyona-grid-margin-mobile: 16px;
}
```

These variables are **additive only** in Phase 1 — adding them to `:root` produces no visual change because nothing consumes them yet. Removing them is a one-line revert.

### 8.7 Recommended CSS usage *(example only — NOT to be implemented in Phase 1)*

```css
.noyona-grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  column-gap: var(--noyona-grid-gutter-desktop);
  padding-inline: var(--noyona-grid-margin-desktop);
}

@media (max-width: 1023px) {
  .noyona-grid {
    grid-template-columns: repeat(8, minmax(0, 1fr));
    column-gap: var(--noyona-grid-gutter-tablet);
    padding-inline: var(--noyona-grid-margin-tablet);
  }
}

@media (max-width: 767px) {
  .noyona-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    column-gap: var(--noyona-grid-gutter-mobile);
    padding-inline: var(--noyona-grid-margin-mobile);
  }
}
```

**Important:**

- This is an example only.
- Do **not** implement it in CSS yet.
- Do **not** create a global `.noyona-grid` class yet.
- Component / layout migration happens in Phase 2 (see §16).

### 8.8 Layout blockers / clarifications needed (Phase 2 only)

These do **not** block documenting the grid. They are blockers only for Phase 2 layout implementation:

- Whether the existing `110 / 50 / 20` screen padding values are still desired for large marketing sections, or whether they should be replaced by the `24 / 24 / 16` grid margin baseline.
- Whether product archive grids should map directly to the 12 / 8 / 4 columns, or use product card spans, e.g.:
  - Desktop: 4 products per row = each card spans 3 of 12 columns.
  - Tablet: 2 products per row = each card spans 4 of 8 columns.
  - Mobile: 1 product per row = each card spans 4 of 4 columns.
- Whether any carousel / slider components are exceptions to the universal grid.

---

## 9. H4/H5/H6 usage report

| Level | Used? | Where | Recommended token (pending designer) |
|---|---|---|---|
| H4 | ✓ Yes | Account profile name, modal titles, order modal progress, address card, payment card, search suggestions column, store reviews tab | `h-4` = `clamp(24px, 2.5vw, 32px)`, **Bold 700** — Typography Guide-aligned target (32 / 28 / 24 D/T/M). See §3, §6.2. |
| H5 | ✓ Yes (heavy) | Shop filter section titles ("Stock Status", "Price", "Star Rating") across 7 product/category templates + `helpers.php` | `h-5` = `clamp(20px, 1.9vw, 24px)`, **Bold 700** — Typography Guide-aligned target (24 / 22 / 20 D/T/M). See §3, §6.2. |
| H6 | ✗ Not used | — | Skip — Typography Guide does not specify; not used in markup. |

---

## 10. Font-family usage report (summary of §2.4)

### 10.1 Current implementation reality

| Family | Status | Files |
|---|---|---|
| Proxima Nova (token) | ⚠ Legacy default — not the designer target | All approved files (~30 block stylesheets, `style.css`) |
| Noto Serif SemiCondensed (token) | ⚠ Legacy — not the designer target | H1 element style in `theme.json`; `blocks/hero-banner/style.css:94, 103`; `blocks/discover-face-banner/style.css:52`; `blocks/mosaic-grid/style.css:77` |
| Times New Roman (literal) | ⚠ Legacy — not listed in designer typography document | `blocks/newsletter-strip/style.css:36` `.newsletter-strip__title em` — target style is a Phase 2 clarification (§5 #15) |
| Georgia (literal) | ⏸ Out of scope | `assets/css/footer.css:441` — email/invoice context |
| Apple system stack | ⚠ Bypasses token system | `blocks/inquiry/style.css:2`, `blocks/contact/style.css:8` |
| "Proxima Nova Light" literal | ⚠ Bypasses token system | `blocks/blogs-view/style.css:309` |

### 10.2 Designer target

| Family | Used for |
|---|---|
| **Hero Regular** | Hero titles, primary page headings, campaign headlines, key brand statements, expressive brand moments |
| **Poppins Bold** | Subheadings, section titles, feature titles, navigation, important CTAs |
| **Poppins Regular** | Body, descriptions, product details, form labels, captions, helper text, supporting content |

### 10.3 Migration recommendation

- **Do not** convert everything to Proxima Nova. The earlier "Proxima Nova everywhere" direction is **superseded**.
- **Do not** start font-family migration in Phase 1. Adding `hero-regular` and `poppins` tokens to `theme.json` is gated on **font asset availability** (see §5 #14).
- Audit current Proxima Nova and Noto Serif SemiCondensed usages and **map each occurrence to a designer role**:
  - Hero / page / campaign / key-brand statements → **Hero Regular**
  - H2 / H3 / H4 / H5, navigation, CTAs → **Poppins Bold**
  - Body / forms / helper / product details / supporting content → **Poppins Regular**
- Confirm Hero Regular and Poppins font assets (files, licensing, `@import` source, fallback stack) **before** changing any code.
- The Apple system stack and "Proxima Nova Light" literals migrate to the appropriate `poppins` weight in Phase 2 — not in Phase 1.
- `.newsletter-strip__title em` font choice is pending (§5 #15) and **must not be changed in Phase 1**.

---

## 11. Non-approved colors found (top inventory)

See §2.5. **Highest-impact consolidations:**
- **Near-blacks → `#000000`:** `#111` (51), `#1f1f1f` (24), `#1a1a1a` (15), `#222` (13), `#0f1728` (8), `#2f2f2f` (8), `#2b2b2b` (8), `#101828` (7). Total: 134 references collapsed to one token.
- **Mid-grays → `#525252`:** `#666` (37), `#555` (23), `#777` (22), `#444` (19), `#4f4f4f` (7), `#888` (9), `#999` (7). Total: 124 references.
- **Pink variants → 3 approved pinks (mapping pending):** `#cc848f` (10), `#e39cab` (12), `#f8a8bc` (6), `#fff1f6` (12), `#ff4d6d` (13), `#ff4b81` (6). Includes likely hover (#cc848f), soft bg (#fff1f6), and off-brand reds (#ff4d6d, #ff4b81).
- **Ambiguous neutrals → designer needs to map:** `#ececec` (22), `#ddd` (19), `#eee` (18), `#ece8ea` (10), `#fafafa` (10), `#f0f0f0` (11), `#f5f5f5` (9), `#efefef` (12), `#e5e5e5` (10).

---

## 12. Typography mismatches found

- 200+ `font-size` declarations in 14px / 13px / 12px / 15px / 0.95rem / 0.9rem / 0.85rem / 0.88rem / 0.8rem / 0.78rem range — all **below** the designer minimum (16px mobile body).
- 60+ `font-size` declarations in non-standard mid-range (22px, 1.05rem, 1.1rem, 1.15rem, 2rem) that should map to the proposed `h-3`/`h-4`/`button`/`label` tokens.
- Account section uses `calc(var(--wp--preset--font-size--body) * 0.72)` and similar multipliers ~15× — these compute to roughly 11–14px depending on viewport, all sub-minimum.
- `style.css:111-137` re-declares `body`/`h1`/`h2`/`h3` font-family/size/weight/line-height that already live in `theme.json` (duplication).

---

## 13. Radius / padding mismatches found

| Designer rule | Reality |
|---|---|
| CTA radius 16px | 40 × `16px` ✓ vs **124 × `999px` (pill)** ✗ vs 12/14/18 stragglers |
| Card radius 24px | 17 × `24px` ✓ vs many cards at 18/14/12/8px |
| Card padding 15/12/10 (D/T/M) | No global var; most cards use ad-hoc rem values (1.2–1.7rem) |
| Universal grid margin = 24 / 24 / 16 (Layout Grid Spec); legacy screen padding 110 / 50 / 20 pending confirmation (§5 #12, §5 #19, §8.5) | No global var; pages use ad-hoc clamps or fixed px. Need designer decision on whether 110 / 50 / 20 remains for marketing / page-inset sections or is replaced by grid margins. |
| 11 `border-radius … !important` declarations | High-friction overrides — need targeted refactor |

---

## 14. Recommended safe Phase 1

**Goal:** ship the token layer + one isolated CSS bug fix. Token additions should produce **no visible change**. The only expected visual delta is the approved `letter-spacing` correction, because the previous `letter-spacing: -5%` declarations were invalid and ignored by browsers — applying `-0.05em` is the first time those rules actually paint.

Add tokens **only**. Do not touch component CSS. Do not flip existing CTA pill radii. Do not replace pinks. Do not change card radii/padding. Do not shrink/grow any current text. **Do not apply the new layout grid anywhere** — adding the grid variables to `:root` is the maximum allowed scope (and only if approved).

1. **`theme.json`** — additive only:
   - Add new font-size slugs **per §6.2 (Typography Guide-aligned values)**: `h-4`, `h-5`, `nav-button`, and provisional `label` / `caption` / `helper` / `input`. Existing `h-1` / `h-2` / `h-3` / `body` keep their current values during Phase 1 to avoid visible change; their **revised target values** (per the Typography Guide) ship in Phase 2 component migration.
   - Add new color slugs **per §6.3 (Color Palette Guide-aligned names)**: `brand-pink-soft` (Primary, `#EFB5BE`), `brand-pink-muted-rose` (Secondary, `#E199A4`), `brand-pink-light-blush` (Secondary, `#FBDDE2`), `text-main`, `text-muted`, `white`, `input-bg` (**`#FFFFFF`** ✓), `placeholder`, `border-strong`, `status-info`, and provisional `status-success` / `status-warning` / `status-error` (clearly labeled `(provisional)`).
   - **Do NOT use the old `brand-pink-primary: #E199A4` slug name** — that was misaligned with the Color Palette Guide and has been corrected (§5 #17).
   - **Do NOT change global H1 font-family.** The earlier "flip H1 to Proxima Nova" instruction is withdrawn (§6.4). Hero / H1 should ultimately target Hero Regular at **Regular 400** (not Proxima Nova, not Bold) — and that migration is Phase 2 and gated on font asset availability.
   - **Do NOT add a `times-italic` font-family token.** Times New Roman is not in the designer typography document; the newsletter emphasis is legacy and its target style is a Phase 2 clarification (§5 #15).
   - **Do NOT add `hero-regular` or `poppins` font-family tokens in Phase 1** unless **font asset availability is confirmed** (§5 #14, §6.1). If assets are confirmed and addition is explicitly approved, the new tokens may be added to `theme.json` but **must not be assigned to any element style** in Phase 1.
   - **Do NOT bake `line-height` or `letter-spacing` into the new font-size tokens.** Line-height = 1.5 per the Typography Guide (Phase 2 rollout); letter-spacing is not specified by the Typography Guide — the `-0.05em` is scoped to the 5-file bug fix only (§5 #7).
   - **Do not remove** the `proxima-nova` or `noto-serif-semicondensed` tokens in Phase 1 — block CSS and the H1 element style still reference them. Removal is Phase 3.
2. **`style.css`** — additive only:
   - Add the `:root` block from §7 at the top of the file (after the existing `:root` block if one exists).
   - **Fix the 5 invalid `letter-spacing: -5%` declarations → `-0.05em`** in `blocks/hero-banner/style.css:81, 141`, `blocks/brand-carousel/style.css:67`, `assets/css/header.css:300, 378`. This is a pure bug fix — current value is silently dropped by browsers, so applying `-0.05em` is the first time the rule actually paints.
3. **Layout grid (if approved):**
   - **Allowed in Phase 1, if approved:** add the layout grid variables from §8.6 (`--noyona-grid-columns-*`, `--noyona-grid-gutter-*`, `--noyona-grid-margin-*`) to `:root` only. Adding them is purely additive — nothing consumes them yet, so there is no visual change.
   - **Not allowed in Phase 1:** applying grid variables to containers, product grids, hero layouts, cards, archive pages, checkout, PDP, or account pages. All layout adoption is deferred to Phase 2 (see §16).
4. **No component refactor in Phase 1:**
   - ❌ No CTA radius change (124 pills stay pills until Phase 2).
   - ❌ No card radius / card padding change.
   - ❌ No screen padding rollout.
   - ❌ No pink replacement (interactive role mapping still pending — §5 #1).
   - ❌ No CTA hover token (still pending — §5 #2).
   - ❌ **No CTA text-color change.** Pink CTAs likely need dark text per the Color Palette Guide (§4.5, §5 #18), but the audit and migration are Phase 2 — no contrast remediation in Phase 1.
   - ❌ No text-size cleanup (12–15px → 16px floor is Phase 2).
   - ❌ **No line-height migration.** The Typography Guide says 1.5 × font-size; the older "`normal`" claim is in conflict (§5 #6). Rollout is Phase 2.
   - ❌ **No universal `letter-spacing` rollout.** `-0.05em` is the bug-fix scope only — applied to the 5 invalid declarations in §2.8 and nowhere else (§5 #7).
   - ❌ No border replacement (contextual).
   - ❌ No status-color replacement.
   - ❌ No layout migration to the 12 / 8 / 4 grid.
   - ❌ **No font-family migration.** No flip of H1 to Proxima Nova; no flip of anything to Hero Regular or Poppins; no change to the newsletter `em` font. Font-family migration is Phase 2 and gated on font asset availability (§5 #14, §5 #15).
   - ❌ **No image / icon / carousel / hero-crop / touch-target changes.** All image and icon layout work is Phase 2 per §17.
5. **Verification:**
   - Spot-check home, PDP, login, register, my-account dashboard, my-account orders, addresses, cart, checkout, contact, search results — visual should match today **except** at the 5 letter-spacing locations (hero, brand carousel, header), where the designed `-0.05em` paints for the first time.
   - Token-only additions (typography, color, radius, card padding, screen padding, layout grid) produce **no visible change**; applying layout grid is explicitly not part of Phase 1.

Phase 1 is fully reversible: revert `theme.json`, `style.css`, and the touched letter-spacing files to return to today's state.

---

## 15. What NOT to change yet

- ❌ **Do not replace any pink hex globally.** Pink role mapping for `#EFB5BE` / `#FBDDE2` / `#E199A4` is still pending. Group current pink usage by likely role only — no substitution. (§5 #1)
- ❌ **Do not propose a final CTA hover token.** Designer hasn't mapped it yet. (§5 #2)
- ❌ **Do not flip `999px` pill CTAs to `16px` in Phase 1.** Designer confirmed 16px applies to *all* buttons, but flipping 124 pills site-wide must be a controlled Phase 2 migration with QA per component family. (§5 #10)
- ❌ **Do not force card radius (24px) or card padding (15/12/10) site-wide in Phase 1.** Designer-confirmed values; implementation belongs in Phase 2 component migration. (§5 #11)
- ❌ **Do not globally replace borders with `#000000`.** Borders are contextual — some pink, some `#525252`, some absent. Use `#000` only as a strong outline where explicitly needed. (§5 #5)
- ❌ **Do not globally replace status colors.** Provisional status hexes are clearly marked `(provisional)` and will be tuned later. (§5 #3)
- ❌ **Do not change any 12–15px text in Phase 1.** 16px minimum is approved but cleanup is Phase 2. (§5 #13)
- ❌ Do not change input background to `#525252` — confirmed `#FFFFFF`. (§5 #4)
- ❌ Do not remove the duplicate body/h1/h2/h3 declarations in `style.css:111-137` yet — wait until Phase 2 component sweep to avoid mid-rollout regressions.
- ❌ Do not remove the `noto-serif-semicondensed` token from `theme.json` in Phase 1 — block CSS still references it; migrate usages first in Phase 2 (§14 step 1).
- ❌ Do not touch Noto Serif usages in hero/discover/mosaic blocks yet — Phase 2 conversion to the designer-target font (Hero Regular for hero/campaign headings, Poppins for structural typography) once the designer signs off per block. (§10)
- ❌ Do not touch social brand colors, map colors, admin-only styles, or invoice/email styles (out of scope per brief).
- ❌ Do not touch `assets/css/footer.css:441` Georgia (email/invoice context) until invoice review is requested.

**Font-family (Hero Regular + Poppins corrected direction — §3, §5 #14, §6.4, §10):**

- ❌ **Do not globally switch Proxima Nova or Noto Serif SemiCondensed to Hero Regular or Poppins in Phase 1.**
- ❌ **Do not flip H1 to Proxima Nova.** The earlier "flip H1 to Proxima Nova" Phase 1 instruction is withdrawn (§6.4).
- ❌ **Do not remove `proxima-nova` or `noto-serif-semicondensed` tokens** from `theme.json` in Phase 1 — they keep current consumers working during migration.
- ❌ **Do not reference unloaded fonts in production CSS.** Adding a `hero-regular` or `poppins` token whose underlying font files are not yet loaded would silently fall back to the system default.
- ❌ **Do not change font-family** until Hero Regular and Poppins font assets / `@import` source / licensing / fallback stacks are confirmed (§5 #14).
- ❌ **Do not add a `times-italic` token.** Times New Roman is not in the designer typography document.
- ❌ **Do not change the `.newsletter-strip__title em` font** in Phase 1 — its target style is pending designer clarification (§5 #15: Hero Regular vs. Poppins Italic vs. other).

**Layout grid (new — §8):**

- ❌ **Do not refactor layouts into the new 12 / 8 / 4 grid in Phase 1.** Adding the variables to `:root` is the maximum allowed scope.
- ❌ **Do not change product grid column counts yet.**
- ❌ **Do not change hero / banner layout yet.**
- ❌ **Do not change page / container padding yet.**
- ❌ **Do not replace existing container widths yet.**
- ❌ **Do not introduce fixed px column widths** — they can fail WCAG 2.2 SC 1.4.10 Reflow.
- ❌ **Do not create horizontal overflow** at any viewport ≥ 320px.
- ❌ **Do not apply the new grid globally** until Phase 2 layout migration (see §16).
- ❌ Do not retire `--noyona-screen-pad-*` in Phase 1 — its role (large-section / page-inset vs. universal grid margin) is still pending designer confirmation (§8.5, §8.8, §5 #19).

**Typography rendering (Typography Guide — §3, §5 #6, §5 #7):**

- ❌ **Do not roll out `line-height: 1.5` site-wide in Phase 1.** Rollout is Phase 2 component migration. Adding the value to new tokens or applying it broadly without per-component QA risks clipping in fixed-height containers (WCAG 2.2 SC 1.4.12).
- ❌ **Do not strip existing `line-height` values from component CSS in Phase 1.**
- ❌ **Do not roll out `letter-spacing: -0.05em` as a universal tracking rule.** Apply it *only* to the 5 invalid `letter-spacing: -5%` declarations cataloged in §2.8.
- ❌ **Do not assume the Typography Guide approves any specific letter-spacing.** It does not.
- ❌ **Do not change H1 to weight 700.** The Typography Guide specifies H1 = Regular **400** (Hero Regular).
- ❌ **Do not scale body above 16px.** The Typography Guide specifies body = 16/16/16. The earlier `clamp(16px, 1.4vw, 20px)` body token is being revised in §6.2.

**Color accessibility (Color Palette Guide — §4.5, §5 #18):**

- ❌ **Do not assume current white-on-pink CTAs are accessible.** Pink fills should use **dark text** unless an explicit designer override passes contrast testing. CTA color/text remediation is Phase 2.
- ❌ **Do not rename `#E199A4` as the primary color** — per the Color Palette Guide it is Secondary (Muted Rose). Primary is `#EFB5BE` (Soft Pink). (§5 #17)
- ❌ **Do not communicate state by color alone.** Pair color with icon / text / outline / shape change.
- ❌ **Do not apply pale pink as a border on white** without verifying non-text contrast (3:1 minimum).

**Image, icon, and touch-target layout (Image & Icon Layout Guidelines — §17):**

- ❌ **Do not change hero crop / hero asset behavior in Phase 1.** No swap from desktop-shrink to mobile-specific crops.
- ❌ **Do not change category / collection card column counts in Phase 1.**
- ❌ **Do not change product carousel / zoom / swipe behavior in Phase 1.**
- ❌ **Do not change icon visual sizes or tap-target areas in Phase 1.**
- ❌ **Do not introduce new image asset sizes (e.g. 2000×2000) in Phase 1.** All image/icon migration is Phase 2 per §17.

---

## 16. Suggested Phase 2 / Phase 3 rollout

### Phase 2 — Component migration (gated on the two remaining designer blockers: pink role map + CTA hover)

**Pre-flight:** wait for designer to deliver (1) the pink-role mapping for `#EFB5BE` / `#FBDDE2` / `#E199A4`, and (2) the primary CTA hover color. Once those land, rename the pink slugs in `theme.json` to their roles (e.g. `cta-fill`, `cta-hover`, `bg-pink-soft`, `accent-pink`) — `:root` aliases in `style.css` absorb the rename so component CSS doesn't break.

Migrate one component family per PR, lowest risk → highest visibility:

1. **Account section** (worst typography fragmentation; `inc/shortcodes.php` + `style.css:2748+`, 3145+, etc.) — also bumps any sub-16px text to the 16px floor.
2. **Auth pages** (login/register/lost-password) — already touched; finish token-izing pinks, swap CTA pills → 16px, adopt card radius 24px + padding 15/12/10.
3. **Shop / category filter** (`h-5` token adoption: "Stock Status / Price / Star Rating") + ratchet 14px chip text to 16px.
4. **PDP** (`blocks/pdp-*`, `single-product.css`) — flip remaining pink hexes, adopt CTA 16px, card 24px.
5. **Checkout** (`woocommerce/checkout/*` + `inc/woocommerce-checkout.php`).
6. **Cart, mini-cart, header search**.
7. **Home page sections** (hero, mosaic, discover, newsletter, brand carousel, video reviews) — last because most visually sensitive; this is also where the legacy Noto Serif / Times New Roman / Apple-stack usages migrate to the designer-target fonts (Hero Regular for hero/campaign headings, Poppins for structural typography) per block.

For each component:
- Replace `font-size` literals with `var(--wp--preset--font-size--*)` tokens **using the Typography Guide-aligned values from §6.2** (e.g. body = 16px Regular, nav/button = 16px Bold, H1 = 40/48/64 Regular 400). Lift anything below 16px to the floor.
- Apply `line-height: 1.5` per the Typography Guide (§3, §5 #6). Verify no clipping in fixed-height containers; verify WCAG 2.2 SC 1.4.12 text-spacing.
- Replace pink hex with the appropriate `var(--wp--preset--color--brand-pink-*)` per the Color Palette Guide roles (`brand-pink-soft` = Primary, `brand-pink-muted-rose` / `brand-pink-light-blush` = Secondary). For *interactive* assignments (CTA fill / CTA hover / soft bg / accent), wait for the §5 #1 mapping.
- **Verify CTA text contrast.** Do not preserve white-on-pink CTA text without a contrast check. Default to dark text on pink fills per §4.5 / §5 #18 unless designer provides an accessible alternative.
- Replace near-blacks (`#111`, `#1f1f1f`, `#1a1a1a`, `#222`, `#0f1728`, `#2f2f2f`, `#2b2b2b`, `#101828`) with `var(--noyona-color-text-main)`.
- Replace mid-grays (`#666`, `#555`, `#777`, `#444`, `#4f4f4f`, `#888`, `#999`) with `var(--noyona-color-text-muted)`.
- Migrate CTA `border-radius` (including `999px` pills) to `var(--noyona-radius-cta)` (16px).
- Migrate card `border-radius` to `var(--noyona-radius-card)` (24px), padding to `var(--noyona-card-pad-*)`.
- Replace `letter-spacing: -5%` (already fixed in Phase 1) usage in any newly touched files. **Do not introduce new letter-spacing rules** unless the designer confirms them — the Typography Guide does not specify letter-spacing.
- Convert `apple-system` / `"Proxima Nova Light"` / Proxima Nova / Noto Serif font-family declarations to the **designer-target token** for that role (`hero-regular` for hero/campaign headings; `poppins` for structural and supporting typography) — **only after the font-family pre-flight below clears**.
- Drop `!important` where the new token wins on specificity.
- Keep border colors contextual — do not auto-replace.

### Phase 2 — Line-height migration (Typography Guide 1.5×) — pre-flight

**Pre-flight:**

- Confirm with designer that line-height = 1.5 × font-size is the production target everywhere (vs. the prior project note of `normal`) — §5 #6.
- Audit every fixed-height container in component CSS that could clip text when line-height grows. Refactor to flexible heights / `min-height` before applying 1.5.
- Verify WCAG 2.2 SC 1.4.12 text-spacing: layout must not break when users override line-height, paragraph-spacing, letter-spacing, or word-spacing.

**Migration:** roll out 1.5 per component family alongside the typography-size migration above — do not flip line-height globally in a single PR.

### Phase 2 — CTA contrast / accessibility migration

**Pre-flight:**

- Inventory every CTA / interactive surface that uses pink (`#EFB5BE`, `#E199A4`, `#FBDDE2`, plus the off-brand pinks cataloged in §2.5).
- For each, measure foreground/background contrast against WCAG AA (4.5:1 normal text, 3:1 large text and non-text UI).
- Default to **dark text on pink fills** (§4.5, §5 #18) unless the designer provides an accessible alternative.
- Add a non-color cue (icon, underline, outline change) for every interactive state (hover / focus / active / disabled).

**Migration:** apply per component family in the order above; do not retire white-on-pink CTAs site-wide in a single PR.

### Phase 2 — Typography font-family migration (Hero Regular + Poppins) — pre-flight

**Pre-flight checks (all must pass before any font-family migration begins):**

1. Confirm **Hero Regular** font assets / `@import` source / licensing are available and loaded by the theme.
2. Confirm **Poppins** font assets / `@import` source / licensing are available and loaded by the theme.
3. Confirm exact **fallback stacks** for each (e.g. `"Hero Regular", serif` vs. an alternative serif fallback; `"Poppins", system-ui, -apple-system, "Segoe UI", sans-serif` vs. an alternative sans fallback).
4. Confirm whether `.newsletter-strip__title em` migrates to **Hero Regular**, **Poppins Italic**, or another designer-approved emphasis style (§5 #15). Until clarified, leave the Times New Roman literal alone.
5. Confirm whether Times New Roman remains approved anywhere else (the designer typography document does not list it, so the default assumption is "no").

**Role mapping for the migration:**

| Element / role | Designer-target font |
|---|---|
| H1, hero titles, primary page headings, campaign headlines, key brand statements | **Hero Regular** |
| H2, H3, H4, H5, section titles, feature titles, navigation, important CTAs | **Poppins Bold** |
| Body copy, descriptions, product details, form labels, captions, helper text, supporting content | **Poppins Regular** |

**Migration order (one section at a time):**

1. Add `hero-regular` and `poppins` font-family tokens to `theme.json` (per §6.1).
2. Add `:root` aliases in `style.css` so component CSS can use `var(--wp--preset--font-family--hero-regular)` and `var(--wp--preset--font-family--poppins)` cleanly.
3. Migrate **structural typography first** (body, nav, CTAs) to `poppins` — lowest-risk, highest-coverage change.
4. Migrate **headings H2–H5** to `poppins` (Bold).
5. Migrate **H1 / hero / campaign / key-brand-statement headings** to `hero-regular`. This includes flipping `styles.elements.h1.typography.fontFamily` in `theme.json` from `noto-serif-semicondensed` to `hero-regular`.
6. Address remaining bypass cases: `apple-system` stack in `blocks/inquiry/style.css`, `blocks/contact/style.css`; `"Proxima Nova Light"` literal in `blocks/blogs-view/style.css`.
7. Resolve `.newsletter-strip__title em` per the §5 #15 clarification.
8. Verify per section: weight, size, line-height, letter-spacing render as expected; no FOUT / FOIT regressions; fallback stacks behave acceptably on slow networks.
9. Once every consumer has migrated, retire `proxima-nova` and `noto-serif-semicondensed` tokens in Phase 3.

### Phase 2 — Layout grid migration (runs after typography/color/radius blockers clear; see §8)

**Pre-flight:** the §8.8 layout clarifications should be resolved before mass migration — particularly whether the `110 / 50 / 20` screen padding stays for marketing sections and how product cards span the 12 / 8 / 4 columns.

Suggested order (lowest risk → highest visibility):

1. **Low-risk container wrappers and section shells** (generic `.noyona-section`, `.noyona-container` equivalents).
2. **Product archive / category grids** (`templates/archive-product.html`, `taxonomy-product_cat.html`, `page-{face,hair,lips,eyes,body}.html`).
3. **Search results grid**.
4. **PDP related-products and product recommendation grids**.
5. **Home page content sections** (non-hero bands).
6. **Hero / banner layouts last** — most visually sensitive.
7. **Checkout / account layouts** only after product / shop layouts are stable.

For each migrated layout:

- Use fluid CSS Grid columns: `repeat(n, minmax(0, 1fr))`.
- Use the designer-approved columns / gutters / margins per breakpoint (§8.1).
- Verify **320px reflow** with no horizontal scrolling (WCAG 2.2 SC 1.4.10).
- Verify product cards do not overflow.
- Verify touch targets and text remain readable.
- Avoid fixed-width px columns.
- Decide per layout whether `--noyona-screen-pad-*` (marketing inset) or `--noyona-grid-margin-*` (universal grid) applies — they are distinct (§8.5).

### Phase 3 — Cleanup
- Remove the duplicate `body/h1/h2/h3` block from `style.css:111-137`.
- Remove the `noto-serif-semicondensed` font-family from `theme.json` once no hero/discover/mosaic usage remains (i.e. once those blocks have migrated to `hero-regular`).
- Remove the `proxima-nova` font-family from `theme.json` once no consumer remains (i.e. once all body/nav/CTA/heading usage has migrated to `hero-regular` or `poppins`).
- Remove the legacy Times New Roman literal from `blocks/newsletter-strip/style.css:36` once the §5 #15 newsletter-emphasis clarification is resolved and the replacement style is applied.
- Delete the legacy `ink` palette slug if no consumers remain.
- Convert the footer's two overlapping rule sets (line 3-308 vs. 405-765) into one source of truth.
- Replace the `(provisional)` status tokens with designer-final hexes when they arrive.
- Final type/color audit using the same script as `typograph_report.text`; expect the pink count to collapse from 35+ to 3, near-blacks from 134 references to 0 (all `--text-main`), grays from 124 to 0 (all `--text-muted`).

---

## 17. Image & Icon Layout Guidelines (Phase 2 planning — from the designer Image & Icon Layout Guidelines)

> **Status:** Phase 2 / UX layout migration guidelines. **No image asset, carousel, hero crop, icon, or touch-target change is permitted in Phase 1** — this section is planning-only (§15 image/icon don'ts).

### 17.1 Hero banners

- Do **not** simply shrink desktop hero images for mobile. Mobile reuse of the desktop crop typically loses subject framing and brand impact.
- Mobile should use **mobile-specific crops / assets** (e.g. 1:1 or 4:5 aspect ratios) where appropriate.
- Hero text should **not overlay critical image details on mobile**. Verify text legibility against the subject region of the mobile crop.

### 17.2 Category / collection cards

- Use **consistent grid / card formats** across collections for scannability.
- **Desktop:** may support 4–6 columns (within the §8 12-column grid).
- **Tablet:** may use **3 columns** (within the §8 8-column grid).
- **Mobile:** may use **2 columns** or a **1.5-column carousel pattern** (depending on content density and the §8 4-column grid).

### 17.3 Product images

- **Desktop:** may use thumbnails and hover-zoom.
- **Mobile / tablet:** support a **swipeable carousel** for the product image set.
- **Mobile product images:** support **pinch / double-tap zoom** where the platform allows.
- Use **high-resolution product images** (around **2000 × 2000px** where feasible) so zoom remains sharp.

### 17.4 Icons and touch targets (WCAG 2.2 SC 2.5.8 Target Size)

- **Header icons:** visual size may be **24px**, but the target (tap / click) area must be at least **44px on tablet** and **48px on mobile**.
- **Footer icons:** visual size around **20px** is acceptable, but the target area must still be at least **44px**.
- Respect **WCAG 2.2 SC 2.5.8 Target Size (Minimum)** — interactive target size should generally be ≥ 24×24 CSS pixels, with WCAG AAA recommending ≥ 44×44. Avoid tight tap zones.
- Apply target-area expansion via padding / `::before` overlay rather than scaling the visual icon.

### 17.5 What's still in scope for Phase 2

- Map each existing hero / banner block to its mobile-crop strategy.
- Audit category / collection / product grids and recommend column-count migration aligned to the §8 grid (12 / 8 / 4).
- Audit product PDP carousels for swipe + zoom support.
- Audit header / footer icons for tap-target sizing; remediate per 17.4.

### 17.6 What's explicitly out of Phase 1

- ❌ No image asset re-cropping.
- ❌ No new mobile hero assets.
- ❌ No carousel introduction or removal.
- ❌ No zoom / pinch behavior changes.
- ❌ No icon visual or tap-target resizing.
- ❌ No replacement of product image assets.

---

## Appendix A — Approved hex → CSS var quick map (aligned to Color Palette Guide)

| Hex | Designer role | Token (proposed) | CSS var |
|---|---|---|---|
| `#EFB5BE` | **Primary (Soft Pink)** | `brand-pink-soft`        | `var(--wp--preset--color--brand-pink-soft)` |
| `#E199A4` | **Secondary (Muted Rose)** | `brand-pink-muted-rose`  | `var(--wp--preset--color--brand-pink-muted-rose)` |
| `#FBDDE2` | **Secondary (Light Blush)** | `brand-pink-light-blush` | `var(--wp--preset--color--brand-pink-light-blush)` |
| `#000000` | Text main / strong border | `text-main` / `border-strong` | `var(--wp--preset--color--text-main)` / `--border-strong` |
| `#525252` | Text muted / placeholder / status-info | `text-muted` / `placeholder` / `status-info` | `var(--wp--preset--color--text-muted)` |
| `#FFFFFF` | White / input bg | `white` / `input-bg` | `var(--wp--preset--color--white)` / `--input-bg` |

> The earlier `brand-pink-primary: #E199A4` mapping is withdrawn (§4.1, §5 #17). `#E199A4` is **Secondary (Muted Rose)** per the Color Palette Guide.

## Appendix B — Files inventoried for this report

- `theme.json`
- `style.css`
- `assets/css/header.css`, `assets/css/footer.css`, `assets/css/single-product.css`
- All `blocks/*/style.css` (~35 files)
- All `templates/*.html` and `parts/*.html`
- `inc/helpers.php`, `inc/shortcodes.php`, `inc/theme-setup.php`, `inc/woocommerce-pdp.php`, `inc/woocommerce-checkout.php`
- `typograph_report.text` (prior audit, cross-referenced)
