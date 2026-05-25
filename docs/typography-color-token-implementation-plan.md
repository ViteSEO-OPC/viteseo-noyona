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

> **Font-family correction note (this revision):** Earlier versions of this plan said *"Proxima Nova everywhere, Times New Roman Italic on the newsletter em."* That was wrong. The designer typography source of truth was Hero Regular + Poppins, but **Hero Regular font files have not been provided** and are not on disk. To unblock the next typography migration, the practical Phase 2B target is **Poppins everywhere** (no Hero Regular dependency):
> - **Poppins Regular / 400** — H1, hero titles, primary page headings, campaign headlines, key brand statements (the roles Hero Regular *would* have covered if files existed).
> - **Poppins Bold / 700** — subheadings, section titles, feature titles, navigation, important CTAs, newsletter emphasis.
> - **Poppins Regular / 400** — body, descriptions, product details, form labels, captions, helper text, supporting content.
>
> **Hero Regular is no longer a Phase 2B blocker.** If Hero Regular files are provided later, the H1 / hero / campaign role can be re-evaluated and migrated again as an optional brand-refinement phase. See §5 #14, §6.1, §6.4, §10, §14, §15, §16 for the corrected direction. The existing theme uses Proxima Nova (compatibility-aliased to Poppins in Phase 2A), Noto Serif SemiCondensed (Google Fonts, used by current H1), Times New Roman (newsletter), Georgia (email), and an Apple system stack — Phase 2B migrates these to Poppins per the role mapping above.

> **Alignment correction note (this revision):** Earlier revisions of this plan misrepresented the designer values for several topics. The plan is now aligned to the **four designer documents** (Color Palette Guide, Typography Guide, Image & Icon Layout Guidelines, Layout Grid Specification). Corrections in this pass:
> - **Typography scale** — Heading sizes/weights from older revisions were wrong. The designer Typography Guide values are: H1 = 64 / 48 / **40** (mobile confirmed), **Regular 400**; H2 = 48 / 40 / 32, **Bold 700**; H3 = 36 / 32 / 28, **Bold 700**; H4 = 32 / 28 / 24, **Bold 700**; H5 = 24 / 22 / 20, **Bold 700**; body = 16/16/16, **Regular 400**; nav & button text = 16/16/16, **Bold 700**. See §3 and §6.2.
> - **Line-height** — **Production target = `normal`** (designer-confirmed post-Phase 1). The earlier "1.5× font-size" interpretation is withdrawn. Components must still tolerate WCAG 2.2 SC 1.4.12 user text-spacing overrides without clipping or breaking. **No Phase 2 line-height migration to 1.5× is planned.** See §5 #6.
> - **Letter-spacing** — `-0.05em` is the **technical bug-fix translation** for the existing invalid `letter-spacing: -5%` declarations only. It is **not** approved as a universal tracking system. See §5 #7.
> - **Color palette naming** — The Color Palette Guide names `#EFB5BE` as the **Primary** (Soft Pink); `#E199A4` is **Secondary** (Muted Rose); `#FBDDE2` is **Secondary** (Light Blush). The earlier proposed `brand-pink-primary: #E199A4` slug was wrong. See §4 and §6.3.
> - **Pink interactive role mapping (fully resolved)** — CTA fill = `#EFB5BE`; CTA hover = `#FBDDE2`; CTA text = `#333333`; **soft background = `#FBDDE2`**; **accent / badge = `#D81B60`** (new token `brand-pink-accent` proposed in §6.3; use **white text** on `#D81B60` for WCAG AA, ~4.95:1 contrast). See §5 #1, §5 #2, §5 #18, §6.3.
> - **Color accessibility** — Added explicit Color Palette Guide accessibility rules (no color-alone state, WCAG contrast minimums, dark text on pink fills, pale-pink-on-white non-text contrast caution). See §4.5.
> - **Image & icon layout** — Added a new §17 capturing the Image & Icon Layout Guidelines (hero crops, card columns, product zoom, touch target minimums).

**Designer clarifications received (this revision):** input bg = `#FFFFFF`; borders are **contextual** (mix of pink / `#525252` / none — not all `#000`); CTA radius 16px applies to **all** buttons (Phase 2 migration); card radius 24px + padding 15/12/10 (D/T/M) applies site-wide (Phase 2 migration); status colors = standard e-commerce greens/yellows/reds, provisional now and tunable later; 16px is the **minimum** text size site-wide; label/button/caption/helper/input weights remain Claude's provisional suggestions (Typography Guide does not specify them, only the documented heading + body + nav/button scale); **practical Phase 2B font-family target = Poppins everywhere** (Hero Regular unblocked but not used; if provided later, treat as future brand-refinement phase); **line-height = `normal` in production**; **mobile H1 = 40px**; **CTA fill `#EFB5BE`, CTA hover `#FBDDE2`, CTA text `#333333`**; **soft background = `#FBDDE2`**; **accent / badge = `#D81B60` with white text** (new token proposed in §6.3); **newsletter emphasis target = Poppins Bold** (Times New Roman remains as legacy only until migration); **screen-padding tokens coexist** — `110 / 50 / 20` for marketing / large-section / page-inset, `24 / 24 / 16` for the universal grid.

**Still pending (post-Phase 2A — none of these block Phase 2B):**

- **Component font-family migration** (`proxima-nova` → `poppins`). The next Phase 2B sub-step: replace `font-family: var(--wp--preset--font-family--proxima-nova)` / `font-family: "Proxima Nova"` references in component CSS and theme.json element styles with the `poppins` slug. Also migrate H1 element-style from `noto-serif-semicondensed` → `poppins` (weight 400). Visual delta should be ~none for body/H2/H3 because the Phase 2A compatibility aliases already render those consumers as Poppins.
- **Newsletter emphasis migration to Poppins Bold** (§5 #15). Pending component migration window.
- **New `brand-pink-accent` token** (`#D81B60`) for accent / badge — documentation-only proposal in §6.3; implementation is Phase 2B-or-later additive change to `theme.json`.
- **Optional future:** if Hero Regular files are ever provided, H1 / hero / campaign role can be re-migrated to `hero-regular` as a separate brand-refinement phase. **Not a blocker.**

**Resolved by Phase 2A (font registration cleanup, see `docs/typography-color-token-phase-2a-font-registration.md`):** Poppins TTF files confirmed in `assets/fonts/`; OFL license committed; `@font-face` registrations added for `font-family: "Poppins"` (300 / 400 / 400 italic / 500 / 600 / 700 / 700 italic / 800 / 900); broken Proxima `@font-face` rules replaced with temporary compatibility aliases pointing at Poppins TTFs; `poppins` slug added to `theme.json` additively (not yet consumed by element styles). The Proxima Nova removal hazard is now closed.

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

> Values taken directly from the designer **Typography Guide** plus designer post-Phase 1 clarifications. Earlier revisions of this plan listed `H1 96/64/48 @ 700`, `H2 55/40/32 @ 700`, `H3 24/24/20 @ 600`, `Paragraph 20/18/16 @ 400`, all `line-height: normal`, all `letter-spacing: -0.05em`. **Those values are withdrawn** — they were not from this designer document. The corrected table below is the source of truth.

| Role | Desktop | Tablet | Mobile | Weight | Line-height | Letter-spacing |
|---|---|---|---|---|---|---|
| H1 | 64px | 48px | **40px** | **Regular 400** | `normal` | not specified |
| H2 | 48px | 40px | 32px | **Bold 700** | `normal` | not specified |
| H3 | 36px | 32px | 28px | **Bold 700** | `normal` | not specified |
| H4 | 32px | 28px | 24px | **Bold 700** | `normal` | not specified |
| H5 | 24px | 22px | 20px | **Bold 700** | `normal` | not specified |
| Body | 16px | 16px | 16px | **Regular 400** | `normal` | not specified |
| Navigation & Button text | 16px | 16px | 16px | **Bold 700** | `normal` | not specified |
| Label / caption / helper / input | not specified by Typography Guide | — | — | Claude-provisional (§5 #9) | `normal` | not specified |

**Important corrections:**

- **H1 weight is 400 (Regular), not 700.** Practical Phase 2B target font for H1 is **Poppins Regular / 400** (Hero Regular files are unavailable; if provided later, H1 may be re-evaluated). See §5 #14, §6.4.
- **H2–H5 weights are 700 (Bold).** This matches Poppins Bold (the practical Phase 2B target for these roles).
- **Mobile H1 size = 40px (designer-confirmed post-Phase 1, §5 #16).** Earlier "inferred ~40px" placeholder is replaced.
- **Line-height = `normal` in production (designer-confirmed post-Phase 1, §5 #6).** The earlier "1.5 × font-size" interpretation is withdrawn. No Phase 2 line-height migration to 1.5× is planned.
- **Letter-spacing is not specified** by the Typography Guide. `-0.05em` is the *technical translation* used to repair invalid `letter-spacing: -5%` declarations in five files (see §5 #7); it is **not** a universal tracking system.
- **WCAG 2.2 SC 1.4.12 text spacing** must still be supported: components must not clip or break when users override line-height, paragraph spacing, letter-spacing, or word spacing. Avoid fixed-height text containers that would clip resized text.

**Fonts (practical Phase 2B target — Poppins everywhere; Hero Regular optional future):**

- **Poppins Regular / 400** — H1, hero titles, primary page headings, campaign headlines, key brand statements (covers the roles Hero Regular would have filled if files were available).
- **Poppins Bold / 700** — H2 / H3 / H4 / H5, subheadings, section titles, feature titles, navigation, important CTAs, newsletter emphasis.
- **Poppins Regular / 400** — body copy, descriptions, product details, form labels, captions, helper text, supporting content.

**Hero Regular is no longer a Phase 2B blocker.** If Hero Regular files are provided later, H1 / hero / campaign headings can be re-evaluated and migrated in a separate brand-refinement phase. See §5 #14.

The earlier *"Proxima Nova everywhere, Times New Roman Italic on `.newsletter-strip__title em`"* phrasing is **superseded**. The current `.newsletter-strip__title em` Times New Roman usage is treated as legacy; Phase 2B target = Poppins Bold (see §5 #15).

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

- **Line-height:** **`normal` in production** (designer-confirmed post-Phase 1, §5 #6). The earlier "1.5 × font-size" interpretation is withdrawn. WCAG 2.2 SC 1.4.12 text spacing still applies: components must not clip or break when users override line-height, paragraph spacing, letter-spacing, or word spacing.
- **Letter-spacing:** **not specified** by the Typography Guide. `-0.05em` is **only** the technical translation used to fix invalid `letter-spacing: -5%` declarations in five files (§2.8, §5 #7). Do not roll out `-0.05em` as a universal tracking system.

### 4.4 UI rules

Card radius 24px; card padding 15 / 12 / 10 (D/T/M); CTA radius 16px; CTA padding 10 or 15px; screen padding 110 / 50 / 20 (D/T/M — flagged as legacy/page-inset, see §5 #18 and §8.5).

### 4.5 Color accessibility rules (from the Color Palette Guide)

- **Do not rely on color alone** to convey state, status, or interactivity. Pair color with icons, text, thickness, underline, or another non-color cue.
- **Normal text must meet WCAG contrast minimum** (AA: 4.5:1 for normal text, 3:1 for large text and non-text UI).
- **White text on pastel pinks fails contrast.** Do not use white text on `#EFB5BE` (Soft Pink), `#E199A4` (Muted Rose), or `#FBDDE2` (Light Blush) buttons or panels without an explicit designer override that passes contrast testing.
- **Pink button backgrounds use dark text `#333333`** (designer-confirmed post-Phase 1, §5 #18). Verify per CTA in Phase 2 that contrast passes WCAG AA against the specific pink fill being used.
- **Pale pink borders on white** can fail non-text contrast (3:1 minimum); use darker rose or dark outlines where the border conveys structure or state.
- **Interactive states must not be communicated by pink alone.** Hover / focus / active / disabled must add at least one non-color cue (icon, text label, outline thickness, underline, or shape change).
- **Phase 2 CTA migration uses confirmed tokens (§5 #1, §5 #2, §5 #18):** fill = `#EFB5BE`, hover = `#FBDDE2`, text = `#333333`, soft background = `#FBDDE2`. Verify contrast per CTA. Do not assume any existing white-on-pink CTA text is accessible — migrate to the dark-text token.
- **Accent / badge color `#D81B60` (new — §5 #1, §6.3 `brand-pink-accent`)**:
  - Use **white text** on `#D81B60` for badges / accent surfaces. WCAG AA passes for normal text at approximately **4.95:1**, and AAA passes for non-text UI / graphical objects (3:1 threshold).
  - Black text on `#D81B60` is **borderline** at approximately 4.24:1 — do not use as the default; reserve for special cases that have been contrast-checked.
  - **Do not use `#D81B60` as body text color.**
  - Use `#D81B60` only for accent / badge / status-like emphasis, not as the default CTA fill (which remains `#EFB5BE`).

---

## 5. Missing / unclear designer values

Status legend: 🔴 **blocker** (Phase 1 must skip) · 🟡 **provisional** (Claude-suggested, designer-allowed for planning, tunable in Phase 2) · 🟢 **resolved** (designer-confirmed).

| # | Topic | Status | Detail |
|---|---|---|---|
| 1 | **Pink interactive role mapping** | 🟢 Fully resolved | Designer-confirmed: **CTA fill = `#EFB5BE`** (`brand-pink-soft` / Primary / Soft Pink); **CTA hover = `#FBDDE2`** (`brand-pink-light-blush`); **CTA text = `#333333`** (§5 #18); **soft background = `#FBDDE2`** (`brand-pink-light-blush` — same token, contextual use); **accent / badge = `#D81B60`** (new `brand-pink-accent` token proposed in §6.3). Use **white text** on `#D81B60` for WCAG AA (~4.95:1); do not use as body text (§4.5). Phase 2B CTA / accent migration may proceed using these five values. Pink hex replacement happens per component family, not globally. |
| 2 | **Primary CTA hover color** | 🟢 Resolved post-Phase 1 | **Hover = `#FBDDE2`** (Light Blush). Maps to the existing `brand-pink-light-blush` token. No new `cta-hover` slug required; component CSS may consume `var(--noyona-color-pink-light-blush)` (or the WP preset directly) for the `:hover` state in Phase 2. |
| 3 | **Status hex values** | 🟡 Provisional | Designer said "use standard e-commerce greens/yellows/reds and adjust later." Provisional tokens proposed in §6.3 are clearly marked *not designer-final*. **No global replacement in Phase 1.** |
| 4 | **Input background** | 🟢 Resolved | `#FFFFFF` (designer-corrected). `#525252` is for muted text / placeholder / helper / info — never an input background. |
| 5 | **Borders** | 🟢 Resolved as contextual | Border role mapping is **contextual** — some borders are pink, some `#525252`, some components have no border. Use `#000000` only as a strong outline where explicitly needed. **No global border replacement in Phase 1.** |
| 6 | **Line-height (production = `normal`)** | 🟢 Resolved post-Phase 1 | **Production line-height = `normal`** (designer-confirmed). The earlier "1.5 × font-size" interpretation from the Typography Guide is withdrawn. **No Phase 2 line-height migration to 1.5× is planned.** Accessibility constraint preserved: components must still tolerate **WCAG 2.2 SC 1.4.12 user text-spacing overrides** without clipping or breaking. Avoid fixed-height text containers that would clip text when users increase line-height, paragraph spacing, letter-spacing, or word spacing. |
| 7 | **Letter-spacing `-5%` → `-0.05em` (bug-fix scope only)** | 🟢 Resolved for the 5 invalid declarations only · 🔴 Universal tracking not approved | `-0.05em` is the **technical translation** for the existing **invalid** `letter-spacing: -5%` declarations cataloged in §2.8 (5 locations). The Typography Guide does **not** specify universal letter-spacing. Phase 1 fixes only those 5 declarations. **Do not roll out `-0.05em` as a global tracking rule.** Broader tracking rules remain Phase 2 / designer-confirmation work. |
| 8 | **H4 / H5 sizes** | 🟢 Resolved by Typography Guide | Designer Typography Guide values now used (not earlier provisional): H4 = 32 / 28 / 24, **Bold 700**; H5 = 24 / 22 / 20, **Bold 700**. See §3 and §6.2. H6 skipped (unused in markup). |
| 9 | **Label / button / caption / helper / input weights** | 🟡 Provisional (Typography Guide silent) | The Typography Guide specifies sizes/weights for headings, body, and nav/button only. For label / caption / helper / input it is silent. Claude-provisional weights remain: label 600, button 700 (matches nav/button 700), caption/badge 600, helper/small 400, input 400. |
| 10 | **CTA radius 16px** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed **16px applies to all buttons** (including current pills). But the site has 124 × `999px` pills; flipping globally in Phase 1 would change the visual identity site-wide. **Migrate in a controlled Phase 2** component-by-component, with QA per family. |
| 11 | **Card radius 24px + padding 15/12/10 (D/T/M)** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed site-wide. Migrate per component in Phase 2 — not forced globally in Phase 1 — because current cards vary (18/14/12/8px) and many have padding tied to internal grid math. |
| 12 | **Breakpoints and legacy screen padding** | 🟢 Breakpoints resolved · 🟡 screen-padding values pending confirmation | Standard breakpoints are adopted: desktop ≥ 1024px, tablet 768–1023px, mobile ≤ 767px. The prior `110 / 50 / 20` screen-padding values are retained only as **legacy large-section / page-inset tokens** pending designer confirmation. They are **not from the Layout Grid Specification**, whose universal grid outer margins are `24 / 24 / 16`. See §5 #19 and §8.5. |
| 13 | **Minimum text size 16px** | 🟢 Resolved + 🟡 deferred to Phase 2 | Designer confirmed 16px is the floor even on mobile. The 200+ existing 12–15px declarations cataloged in §2.6 are **flagged as Phase 2 cleanup**, not changed in Phase 1. |
| 14 | **Font-family direction & asset audit** | 🟢 Poppins registered (Phase 2A) · 🟡 Hero Regular files missing (optional future, NOT a Phase 2B blocker) | **Phase 2A (font registration cleanup) is complete** — see `docs/typography-color-token-phase-2a-font-registration.md`: (a) Poppins TTF files confirmed present in `assets/fonts/` (Regular, Italic, Medium, SemiBold, Bold, BoldItalic, ExtraBold, Black, Light + additional weights); (b) `OFL.txt` license committed; (c) `@font-face` rules for `font-family: "Poppins"` registered in `assets/css/fonts.css` covering 300 / 400 / 400 italic / 500 / 600 / 700 / 700 italic / 800 / 900; (d) the existing `inc/enqueue.php` enqueue path already loads `fonts.css` on every front-end page (verified during audit); (e) fallback stack = `"Poppins", system-ui, -apple-system, "Segoe UI", sans-serif`. The `poppins` slug is added to `theme.json` additively (not yet assigned to any element style). The previously-broken Proxima Nova `@font-face` rules in `fonts.css` are now **temporary compatibility aliases** pointing at the corresponding-weight Poppins TTF — this restores real webfont rendering for existing `proxima-nova` consumers without requiring component migration yet. **Hero Regular files remain missing**, but **this no longer blocks the next typography migration.** Practical Phase 2B target: **Poppins Regular / 400** for H1 / hero / page / campaign / key-brand-statement headings. If Hero Regular files are provided later, treat the H1 / hero / campaign migration as a separate optional brand-refinement phase — not as a current dependency. Existing `proxima-nova` and `noto-serif-semicondensed` slugs in `theme.json` must remain during migration so current consumers keep working. **Component font-family migration (per-component swap from `proxima-nova` → `poppins`, and H1 from `noto-serif-semicondensed` → `poppins` weight 400) is the next Phase 2B sub-step.** |
| 15 | **Newsletter emphasis (`.newsletter-strip__title em`)** | 🟢 Resolved post-Phase 1 | **Target = Poppins Bold.** The Times New Roman literal at `blocks/newsletter-strip/style.css:36` remains as **legacy / current implementation only** until Phase 2 migration. **No `times-italic` token is added to `theme.json`.** Migration is gated on §5 #14 font asset registration (`poppins` family with Bold weight must load from `assets/fonts/` and be registered in `assets/css/fonts.css`). |
| 16 | **Mobile H1 size** | 🟢 Resolved post-Phase 1 | **Mobile H1 = 40px** (designer-confirmed). The earlier "~40px inferred" placeholder is replaced. §3 and §6.2 use this confirmed value. |
| 17 | **Color slug naming vs. Color Palette Guide** | 🟢 Resolved by Color Palette Guide | The Color Palette Guide names `#EFB5BE` as **Primary (Soft Pink)**, `#E199A4` as **Secondary (Muted Rose)**, `#FBDDE2` as **Secondary (Light Blush)**. The earlier proposed slug `brand-pink-primary: #E199A4` is **wrong** and has been corrected in §6.3 to `brand-pink-soft` (Primary, `#EFB5BE`), `brand-pink-muted-rose` (Secondary, `#E199A4`), and `brand-pink-light-blush` (Secondary, `#FBDDE2`). Interactive role mapping (CTA fill / CTA hover / soft bg / accent) is a separate question (§5 #1). |
| 18 | **CTA text color on pink fills** | 🟢 Resolved post-Phase 1 | **CTA text = `#333333`** (designer-confirmed). Applies to pink CTA fills (`#EFB5BE` fill, `#FBDDE2` hover). Existing white-on-pink CTAs must be migrated to `#333333` in Phase 2 — do not preserve white. Verify per CTA that contrast passes WCAG AA against the specific pink fill in use. (§4.5, §5 #1, §5 #2, §16.) |
| 19 | **Screen-padding scales coexist** | 🟢 Resolved post-Phase 1 | Both scales coexist by design: **`110 / 50 / 20` = marketing / large-section / page-inset sections**; **`24 / 24 / 16` = universal layout grid outer margin** (Layout Grid Specification). Each layout chooses the appropriate scale in Phase 2. Both sets of variables are kept (§7, §8.5, §8.6). |

---

## 6. Proposed `theme.json` tokens *(not yet applied)*

### 6.1 Font families

**Current `theme.json` slugs after Phase 2A:**

```json
{ "fontFamily": "\"Proxima Nova\", system-ui, -apple-system, \"Segoe UI\", sans-serif", "name": "Proxima Nova",            "slug": "proxima-nova" },
{ "fontFamily": "\"Noto Serif SemiCondensed\", \"Noto Serif\", \"Times New Roman\", serif", "name": "Noto Serif SemiCondensed", "slug": "noto-serif-semicondensed" },
{ "fontFamily": "\"Poppins\", system-ui, -apple-system, \"Segoe UI\", sans-serif",      "name": "Poppins",                 "slug": "poppins" }
```

**Optional future token (NOT added — files missing):**

```json
{
  "fontFamily": "\"Hero Regular\", serif",
  "name": "Hero Regular",
  "slug": "hero-regular"
}
```

**Notes:**

- The `poppins` slug exists after Phase 2A and is available for Phase 2B element-style migration: H1 / H2 / H3 / body and other element styles may now consume `var(--wp--preset--font-family--poppins)`.
- **`hero-regular` is NOT added** because Hero Regular font files are not present in `assets/fonts/`. Adding a `hero-regular` slug that points at a font with no `@font-face` registration would silently fall back to the serif system stack. If Hero Regular files are provided later, the slug can be added in an optional brand-refinement phase.
- The existing `proxima-nova` and `noto-serif-semicondensed` slugs **must remain in `theme.json`** during Phase 2B migration for backward compatibility — current block CSS still references `proxima-nova`, and the H1 element style still references `noto-serif-semicondensed`. They retire in Phase 3 once every consumer has migrated.
- The Phase 2A **temporary compatibility aliases** in `assets/css/fonts.css` (`font-family: "Proxima Nova"` blocks whose `src:` points at Poppins TTFs) likewise retire in Phase 3.
- **No `times-italic` token is proposed.** The designer typography document does not list Times New Roman; the existing `.newsletter-strip__title em` usage is legacy and its target style is **Poppins Bold** (§5 #15).

### 6.2 Font sizes (add new slugs; keep existing for backward compatibility)

> Sizes/weights are taken directly from the designer **Typography Guide** (§3). The earlier "tightened lower-bound" provisional values (H1 `clamp(48px, 6vw, 96px)`, H2 `clamp(32px, 4.2vw, 55px)`, H3 `clamp(20px, 2vw, 24px)`, H4 `clamp(18px, 1.6vw, 22px)`, H5 `clamp(16px, 1.2vw, 18px)`, all at heading weights of 700/600) are **withdrawn** — they did not match the Typography Guide. Existing `h-1` / `h-2` / `h-3` / `body` slugs in `theme.json` keep their current values during migration so nothing breaks, but the proposed new values below are the migration target.

| Proposed slug | Value (D / T / M) | Weight | Notes |
|---|---|---|---|
| `h-1` (revise target; keep current slug working) | `clamp(40px, 5vw, 64px)` | **Regular 400** | Designer Typography Guide: 64 / 48 / **40** (mobile confirmed §5 #16). H1 is **Hero Regular**, not Bold. |
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

- **Line-height** for all tokens is **`normal`** (designer-confirmed post-Phase 1, §5 #6). The earlier "1.5 × font-size" interpretation is withdrawn. No Phase 2 line-height migration is planned. WCAG 2.2 SC 1.4.12 text-spacing override behavior must still be tolerated.
- **Letter-spacing** is **not specified** by the Typography Guide. Do not bake `-0.05em` into these tokens. The `-0.05em` fix is scoped to the 5 invalid declarations in §2.8 only (§5 #7).
- **Minimum text size 16px** is approved. All 12 / 13 / 14 / 15px declarations cataloged in §2.6 are flagged for Phase 2 cleanup — not changed in Phase 1.
- The previously-proposed `button` slug (`clamp(18px, 1.6vw, 24px)` weight 700) is **replaced** by the designer-confirmed `nav-button` slug at **16px Bold 700**. Earlier CTA size assumptions of 18–24px are no longer the designer target.
- Existing `h-1` / `h-2` / `h-3` / `body` slugs in `theme.json` are not deleted in Phase 1 — they keep their current values until Phase 2 component migration. Adding the **new** tokens here is additive; **assigning them to elements** is Phase 2 work.

### 6.3 Color palette (additive — slugs aligned to the Color Palette Guide)

```jsonc
// === Designer Color Palette Guide ===
{ "slug": "brand-pink-soft",         "name": "Brand Pink Soft / Primary",          "color": "#EFB5BE" },  // Color Palette Guide: PRIMARY (Soft Pink) · CTA fill
{ "slug": "brand-pink-muted-rose",   "name": "Brand Pink Muted Rose / Secondary",  "color": "#E199A4" },  // Color Palette Guide: SECONDARY (Muted Rose)
{ "slug": "brand-pink-light-blush",  "name": "Brand Pink Light Blush / Secondary", "color": "#FBDDE2" },  // Color Palette Guide: SECONDARY (Light Blush) · CTA hover · Soft background
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

**Proposed additive token (NOT yet added to `theme.json`; documentation only — Phase 2B will add):**

```jsonc
{ "slug": "brand-pink-accent",       "name": "Brand Pink Accent / Badge",          "color": "#D81B60" }   // Accent / badge surfaces — use WHITE text for WCAG AA (~4.95:1)
```

**Usage rules for `#D81B60`** (see §4.5):

- Use for **accent / badge / status-like emphasis** where stronger contrast is needed.
- Use **white text** on `#D81B60` (passes WCAG AA at ~4.95:1; passes AAA for non-text UI at 3:1).
- Black text on `#D81B60` is **borderline** at ~4.24:1 — do not default to it; reserve for cases that have been contrast-checked.
- **Do not use as default CTA fill** (CTA fill remains `#EFB5BE`).
- **Do not use as body text color.**

**Important corrections (vs. earlier revisions):**

- **The earlier `brand-pink-primary: #E199A4` slug is withdrawn.** Per the Color Palette Guide (§4.1, §5 #17), `#E199A4` is **Secondary (Muted Rose)**, not the primary color. The primary color is `#EFB5BE` (Soft Pink). The slug for `#E199A4` is now `brand-pink-muted-rose`.
- Slug names above are aligned to the **palette role** (Primary/Secondary) named in the Color Palette Guide. The **interactive role mapping is now fully resolved** (§5 #1, §5 #2, §5 #18): **CTA fill = `brand-pink-soft` (`#EFB5BE`)**; **CTA hover = `brand-pink-light-blush` (`#FBDDE2`)**; **CTA text = `#333333`**; **soft background = `brand-pink-light-blush` (`#FBDDE2`, same token used contextually)**; **accent / badge = `brand-pink-accent` (`#D81B60` — new token proposed below)**. No standalone `cta-fill` / `cta-hover` slug is required because the existing palette tokens map directly.
- **CTA text color on pink fills = `#333333`** (designer-confirmed post-Phase 1, §5 #18). Migrate existing white-on-pink CTAs to `#333333` in Phase 2. Do not preserve white text on pink fills.
- `border-strong` is **available** but **must not be globally applied** to all borders — borders are contextual (some pink, some `#525252`, some none).
- Status tokens are explicitly **provisional** with `(provisional)` in the slug name so they don't get mistaken for final values.
- The existing `ink = #333333` slug stays in place so any current `var(--wp--preset--color--ink)` references keep working.

### 6.4 H1 element font-family — Phase 2B target = Poppins Regular / 400

Earlier revisions of this plan proposed flipping `styles.elements.h1.typography.fontFamily` from Noto Serif SemiCondensed to Proxima Nova in Phase 1 (withdrawn). A subsequent revision said H1 must wait for Hero Regular files. **That is also withdrawn** because Hero Regular files have not been provided.

**Phase 1 instruction (still applies):** Phase 1 did NOT change `styles.elements.h1.typography.fontFamily`. H1 currently references `noto-serif-semicondensed` in `theme.json`.

**Phase 2B target:** migrate `styles.elements.h1.typography.fontFamily` from `var(--wp--preset--font-family--noto-serif-semicondensed)` to `var(--wp--preset--font-family--poppins)` at weight **400 (Regular)**. This is the practical Phase 2B target for H1 / hero / page / campaign / key-brand-statement headings.

**Optional future brand refinement (NOT a current dependency):** if Hero Regular font files are provided later, the H1 / hero / campaign role may be re-migrated from `poppins` to `hero-regular` in a separate brand-refinement phase. That is optional and not on the current Phase 2 roadmap.

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
  /* Designer-confirmed production line-height = `normal` (§5 #6). No line-height migration
     planned. WCAG 2.2 SC 1.4.12 still applies: components must tolerate user text-spacing
     overrides without clipping. */

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

  /* CTA / interactive role mapping (fully resolved):
       fill            = var(--noyona-color-pink-soft)         (#EFB5BE)
       hover           = var(--noyona-color-pink-light-blush)  (#FBDDE2)
       text            = #333333 (matches existing `ink` slug)
       soft background = var(--noyona-color-pink-light-blush)  (#FBDDE2; same token, contextual use)
       accent / badge  = var(--noyona-color-pink-accent)       (#D81B60 — NEW, see proposal below)
     No standalone --noyona-color-cta-hover token is added — Phase 2B consumers
     reference the palette tokens directly. */

  /* === PROPOSED additive shorthand (NOT yet implemented; Phase 2B will add to :root
         alongside the new brand-pink-accent slug in theme.json) ===
       --noyona-color-pink-accent: var(--wp--preset--color--brand-pink-accent, #D81B60);
     Usage rules:
       - White text on #D81B60 for WCAG AA (~4.95:1) on badges/accents.
       - Black text on #D81B60 is borderline (~4.24:1) — do not default to it.
       - Do NOT use as body text color.
       - Do NOT use as default CTA fill (CTA fill remains #EFB5BE). */
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

— are **large-section / page-inset tokens** for marketing sections, hero insets, and home-page bands. **They are not from the Layout Grid Specification** — that document specifies `24 / 24 / 16` outer margins for the universal grid. Per the post-Phase 1 decision (§5 #19), **both scales coexist by design**: keep `110 / 50 / 20` for marketing / large-section / page-inset contexts; use `24 / 24 / 16` for the universal layout grid outer margin. Each layout chooses the appropriate scale in Phase 2.

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

- ~~Whether the existing `110 / 50 / 20` screen padding values are still desired for large marketing sections, or whether they should be replaced by the `24 / 24 / 16` grid margin baseline.~~ **Resolved post-Phase 1 (§5 #19):** both scales coexist — `110 / 50 / 20` for marketing/page-inset, `24 / 24 / 16` for the universal grid. Each layout picks the appropriate one.
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

### 10.2 Practical Phase 2B target (Poppins everywhere)

| Family / weight | Used for |
|---|---|
| **Poppins Regular / 400** | H1, hero titles, primary page headings, campaign headlines, key brand statements, body copy, descriptions, product details, form labels, captions, helper text, supporting content |
| **Poppins Bold / 700** | H2 / H3 / H4 / H5, subheadings, section titles, feature titles, navigation, important CTAs, newsletter emphasis |

**Optional future:** if Hero Regular font files are provided later, H1 / hero / page / campaign / key-brand-statement headings may be re-migrated to `hero-regular` in a separate brand-refinement phase. Not a Phase 2B dependency.

### 10.3 Migration recommendation

- **Do not** convert everything to Proxima Nova. The earlier "Proxima Nova everywhere" direction is **superseded**.
- **Phase 2A is done** — Poppins is registered, the `poppins` slug exists in `theme.json`, and the broken Proxima `@font-face` rules are now compatibility aliases pointing at Poppins TTFs. See `docs/typography-color-token-phase-2a-font-registration.md`.
- **Phase 2B target:** migrate per role to Poppins weights as above. H1 element-style → `poppins` weight 400; H2 / H3 element-styles + nav / CTA / newsletter components → `poppins` weight 700; body + supporting components → `poppins` weight 400.
- The Apple system stack and `"Proxima Nova Light"` literals migrate to the appropriate `poppins` weight in Phase 2B.
- `.newsletter-strip__title em` migrates to **Poppins Bold** in Phase 2B (§5 #15).

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
   - **Do NOT bake `line-height` or `letter-spacing` into the new font-size tokens.** Production line-height = `normal` (§5 #6, designer-confirmed) — no 1.5× rollout is planned. Letter-spacing is not specified by the Typography Guide — the `-0.05em` is scoped to the 5-file bug fix only (§5 #7).
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
   - ❌ **No line-height migration.** Production line-height = `normal` (§5 #6, designer-confirmed). No Phase 2 line-height migration to 1.5× is planned. Do not strip existing component line-height values unless a specific component bug requires it. Components must still tolerate WCAG 2.2 SC 1.4.12 user text-spacing overrides.
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
- ❌ Do not touch Noto Serif usages in hero/discover/mosaic blocks yet — Phase 2B conversion to **Poppins** (Regular 400 for hero/campaign headings; Bold 700 for structural typography) per the practical Phase 2B target. (§10)
- ❌ Do not touch social brand colors, map colors, admin-only styles, or invoice/email styles (out of scope per brief).
- ❌ Do not touch `assets/css/footer.css:441` Georgia (email/invoice context) until invoice review is requested.

**Font-family (post-Phase 2A — Poppins-everywhere Phase 2B direction — §3, §5 #14, §6.4, §10):**

- ❌ **Do not globally switch Proxima Nova or Noto Serif SemiCondensed to Poppins in Phase 1.** Phase 2B does the per-component migration.
- ❌ **Do not flip H1 to Proxima Nova.** The earlier "flip H1 to Proxima Nova" Phase 1 instruction is withdrawn (§6.4). H1's Phase 2B target is `poppins` weight 400.
- ❌ **Do not remove `proxima-nova` or `noto-serif-semicondensed` tokens** from `theme.json` until Phase 3. They keep current consumers working during Phase 2B migration.
- ❌ **Do not remove the Proxima Nova compatibility aliases in `assets/css/fonts.css` yet.** They retire in Phase 3 after every consumer has migrated to `poppins`.
- ❌ **Do not add a `hero-regular` token.** Hero Regular font files are not on disk; adding the slug would silently fall back to a serif system stack. If files are provided later, the slug can be added as an optional brand-refinement phase.
- ❌ **Do not add a `times-italic` token.** Times New Roman is not in the designer typography document. Newsletter emphasis target is Poppins Bold.
- ❌ **Do not change the `.newsletter-strip__title em` font in Phase 1.** Phase 2B target = Poppins Bold (§5 #15).

**Layout grid (new — §8):**

- ❌ **Do not refactor layouts into the new 12 / 8 / 4 grid in Phase 1.** Adding the variables to `:root` is the maximum allowed scope.
- ❌ **Do not change product grid column counts yet.**
- ❌ **Do not change hero / banner layout yet.**
- ❌ **Do not change page / container padding yet.**
- ❌ **Do not replace existing container widths yet.**
- ❌ **Do not introduce fixed px column widths** — they can fail WCAG 2.2 SC 1.4.10 Reflow.
- ❌ **Do not create horizontal overflow** at any viewport ≥ 320px.
- ❌ **Do not apply the new grid globally** until Phase 2 layout migration (see §16).
- ❌ Do not retire `--noyona-screen-pad-*` — its role is now confirmed (§5 #19) as the marketing / large-section / page-inset scale, distinct from the universal grid margin (`24 / 24 / 16`). Both coexist by design.

**Typography rendering (Typography Guide — §3, §5 #6, §5 #7):**

- ❌ **Do not roll out a global `line-height: 1.5` migration.** Production line-height is `normal` (§5 #6, designer-confirmed post-Phase 1). The earlier "1.5 × font-size" interpretation is withdrawn — no Phase 2 line-height migration is planned.
- ❌ **Do not strip existing `line-height` values from component CSS in Phase 1.** Component-level line-height fine-tuning may continue in Phase 2 per the existing values; the global default stays `normal`.
- ⚠ **Components must still tolerate WCAG 2.2 SC 1.4.12 text-spacing overrides** without clipping or breaking when users increase line-height / paragraph spacing / letter-spacing / word spacing. Avoid fixed-height text containers.
- ❌ **Do not roll out `letter-spacing: -0.05em` as a universal tracking rule.** Apply it *only* to the 5 invalid `letter-spacing: -5%` declarations cataloged in §2.8.
- ❌ **Do not assume the Typography Guide approves any specific letter-spacing.** It does not.
- ❌ **Do not change H1 to weight 700.** The Typography Guide specifies H1 = Regular **400** (Hero Regular).
- ❌ **Do not scale body above 16px.** The Typography Guide specifies body = 16/16/16. The earlier `clamp(16px, 1.4vw, 20px)` body token is being revised in §6.2.

**Color accessibility (Color Palette Guide — §4.5, §5 #18):**

- ❌ **Do not preserve white-on-pink CTA text.** Designer-confirmed CTA text = `#333333` (§5 #18). Migrate existing white-on-pink CTAs to `#333333` in Phase 2B. Verify per CTA that contrast passes WCAG AA.
- ❌ **Do not migrate CTA colors in Phase 1.** The fill (`#EFB5BE`) / hover (`#FBDDE2`) / text (`#333333`) / soft background (`#FBDDE2`) / accent (`#D81B60`) tokens are confirmed but consumption is Phase 2B.
- ❌ **Do not rename `#E199A4` as the primary color** — per the Color Palette Guide it is Secondary (Muted Rose). Primary is `#EFB5BE` (Soft Pink). (§5 #17)
- ❌ **Do not communicate state by color alone.** Pair color with icon / text / outline / shape change.
- ❌ **Do not apply pale pink as a border on white** without verifying non-text contrast (3:1 minimum).
- ❌ **Do not use black text on `#D81B60`** as the default — contrast is borderline (~4.24:1). Use **white text** on `#D81B60` for WCAG AA (~4.95:1).
- ❌ **Do not use `#D81B60` as body text color.** Reserve for accent / badge / status-like emphasis only.
- ❌ **Do not use `#D81B60` as the default CTA fill.** CTA fill remains `#EFB5BE`.
- ❌ **Do not add the `brand-pink-accent` token to `theme.json` in this documentation pass.** It is a Phase 2B-or-later additive implementation step.

**Image, icon, and touch-target layout (Image & Icon Layout Guidelines — §17):**

- ❌ **Do not change hero crop / hero asset behavior in Phase 1.** No swap from desktop-shrink to mobile-specific crops.
- ❌ **Do not change category / collection card column counts in Phase 1.**
- ❌ **Do not change product carousel / zoom / swipe behavior in Phase 1.**
- ❌ **Do not change icon visual sizes or tap-target areas in Phase 1.**
- ❌ **Do not introduce new image asset sizes (e.g. 2000×2000) in Phase 1.** All image/icon migration is Phase 2 per §17.

---

## 16. Suggested Phase 2 / Phase 3 rollout

### Phase 2 — Component migration

**Phase 2 pre-flight #1 — Font asset audit / registration:**

Status: ✅ **Complete (Phase 2A — see `docs/typography-color-token-phase-2a-font-registration.md`).**

What Phase 2A did:

1. ✅ Poppins TTF files confirmed present in `assets/fonts/` (Regular, Italic, Medium, SemiBold, Bold, BoldItalic, ExtraBold, Black, Light — plus additional weights available).
2. ✅ `OFL.txt` license committed in `assets/fonts/`.
3. ✅ `@font-face` declarations for `font-family: "Poppins"` registered in `assets/css/fonts.css` covering 300 / 400 / 400 italic / 500 / 600 / 700 / 700 italic / 800 / 900.
4. ✅ The existing `inc/enqueue.php` already loads `fonts.css` on every front-end page (verified during audit; no enqueue change needed).
5. ✅ Fallback stack confirmed: `"Poppins", system-ui, -apple-system, "Segoe UI", sans-serif` (matches the new `poppins` slug in `theme.json`).
6. 🔴 **Hero Regular files still missing.** No `@font-face` rules added; cannot register what isn't on disk. Still unresolved (§5 #14).
7. ✅ **Proxima Nova removal hazard closed.** The broken Proxima `@font-face` rules in `fonts.css` were rewritten as temporary compatibility aliases pointing at the Poppins TTFs, so existing `proxima-nova` consumers now render in real Poppins instead of falling back to system sans-serif.

**Phase 2 pre-flight #2 — Pink role mapping (fully resolved):**

The pink interactive role mapping is now confirmed (§5 #1, §5 #2, §5 #18, §6.3):

| Interactive role | Token | Hex |
|---|---|---|
| CTA fill | `brand-pink-soft` / `--noyona-color-pink-soft` | `#EFB5BE` |
| CTA hover | `brand-pink-light-blush` / `--noyona-color-pink-light-blush` | `#FBDDE2` |
| CTA text | (existing `ink` slug) or literal `#333333` | `#333333` |
| Soft background | `brand-pink-light-blush` / `--noyona-color-pink-light-blush` (same token; contextual use) | `#FBDDE2` |
| Accent / badge | `brand-pink-accent` / `--noyona-color-pink-accent` (**NEW** token — Phase 2B adds to `theme.json`; use **white text** for WCAG AA ~4.95:1) | `#D81B60` |

No standalone `cta-fill` / `cta-hover` slugs are added. Phase 2B consumers reference the palette tokens directly. The new `brand-pink-accent` slug is documented in §6.3 and will be added to `theme.json` additively as part of Phase 2B implementation.

### Phase 2B — Global typography element-style migration to Poppins

> **Scope:** Migrate `theme.json` element styles and low-risk typography consumers to the `poppins` slug. **Does NOT include** CTA color / radius / card / layout / image / icon changes — those are separate later sub-steps.

**Pre-requisites (all complete):**

- ✅ Phase 1 token plumbing (font-size, color, radius, screen-pad, layout-grid variables).
- ✅ Phase 2A font registration (Poppins `@font-face` rules + `poppins` slug + Proxima → Poppins compatibility aliases).
- ✅ Designer decisions resolved: line-height = `normal`, mobile H1 = 40px, pink role mapping, screen-padding scales, newsletter target.

**Phase 2B migrations:**

1. **`theme.json` element styles:**
   - `h1.typography.fontFamily` → `var(--wp--preset--font-family--poppins)`, `fontWeight: "400"` (Poppins Regular).
   - `h2.typography.fontFamily` → `var(--wp--preset--font-family--poppins)`, `fontWeight: "700"` (Poppins Bold).
   - `h3.typography.fontFamily` → `var(--wp--preset--font-family--poppins)`, `fontWeight: "700"` (Poppins Bold).
   - Body `styles.typography.fontFamily` → `var(--wp--preset--font-family--poppins)`, `fontWeight: "400"` (Poppins Regular).
2. **Low-risk typography consumers** (component CSS still referencing `var(--wp--preset--font-family--proxima-nova)` or `font-family: "Proxima Nova"` literal): swap each to the Poppins slug. Expected visual delta = ~none because Phase 2A compatibility aliases already render those consumers as Poppins.
3. **Newsletter emphasis:** `blocks/newsletter-strip/style.css:36` migrates from `Times New Roman` to `var(--wp--preset--font-family--poppins)` Bold.
4. **Apple system stack and `"Proxima Nova Light"` literals** in `blocks/inquiry/style.css`, `blocks/contact/style.css`, `blocks/blogs-view/style.css`: replace with the Poppins slug at the appropriate weight.

**What Phase 2B does NOT include:**

- ❌ No CTA fill / hover / text-color migration. (Separate sub-step.)
- ❌ No CTA radius change.
- ❌ No card radius / padding change.
- ❌ No layout grid application.
- ❌ No image / icon / tap-target change.
- ❌ No removal of `proxima-nova` slug or compatibility aliases (those are Phase 3).
- ❌ No `hero-regular` token addition (files missing).
- ❌ No `brand-pink-accent` token addition is required for Phase 2B typography migration; it may be added in the same window or deferred to a later Phase 2 sub-step.

Migrate one component family per PR, lowest risk → highest visibility:

1. **Account section** (worst typography fragmentation; `inc/shortcodes.php` + `style.css:2748+`, 3145+, etc.) — also bumps any sub-16px text to the 16px floor.
2. **Auth pages** (login/register/lost-password) — already touched; finish token-izing pinks, swap CTA pills → 16px, adopt card radius 24px + padding 15/12/10.
3. **Shop / category filter** (`h-5` token adoption: "Stock Status / Price / Star Rating") + ratchet 14px chip text to 16px.
4. **PDP** (`blocks/pdp-*`, `single-product.css`) — flip remaining pink hexes, adopt CTA 16px, card 24px.
5. **Checkout** (`woocommerce/checkout/*` + `inc/woocommerce-checkout.php`).
6. **Cart, mini-cart, header search**.
7. **Home page sections** (hero, mosaic, discover, newsletter, brand carousel, video reviews) — last because most visually sensitive; this is also where the legacy Noto Serif / Times New Roman / Apple-stack usages migrate to **Poppins** (Regular 400 for hero/campaign headings; Bold 700 for structural typography and newsletter emphasis) per block.

For each component:
- Replace `font-size` literals with `var(--wp--preset--font-size--*)` tokens **using the Typography Guide-aligned values from §6.2** (e.g. body = 16px Regular, nav/button = 16px Bold, H1 = 40/48/64 Regular 400). Lift anything below 16px to the floor.
- Keep production line-height as `normal`. Do not migrate to 1.5×. During component QA, verify the component tolerates WCAG 2.2 SC 1.4.12 user text-spacing overrides without clipping or breaking.
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

### Phase 2 — CTA contrast / accessibility migration (uses confirmed tokens)

Confirmed post-Phase 1 (§5 #1, §5 #2, §5 #18):

- **CTA fill:** `#EFB5BE`
- **CTA hover:** `#FBDDE2`
- **CTA text:** `#333333`

**Migration steps per component family:**

1. Inventory every CTA / interactive surface that uses pink (`#EFB5BE`, `#E199A4`, `#FBDDE2`, plus the off-brand pinks cataloged in §2.5).
2. Migrate fill → `var(--noyona-color-pink-soft)` (`#EFB5BE`), hover → `var(--noyona-color-pink-light-blush)` (`#FBDDE2`), text → `#333333` (or the `ink` slug).
3. Migrate radius to `var(--noyona-radius-cta)` (16px) — replaces `999px` pills.
4. Verify foreground/background contrast against WCAG AA (4.5:1 normal text, 3:1 large text and non-text UI). The confirmed dark-text-on-soft-pink combination should pass; confirm per CTA.
5. Add a non-color cue (icon, underline, outline change) for every interactive state (hover / focus / active / disabled) — color alone is insufficient.

Do not retire white-on-pink CTAs site-wide in a single PR. Migrate per component family.

**Line-height:** no migration. Production line-height = `normal` (§5 #6). Component-level line-height stays as-is unless a specific component issue requires fine-tuning.

### Phase 2B — Typography font-family migration (Poppins everywhere) — detail

**Status:** Phase 2A complete (Poppins registered, compatibility aliases in place, `poppins` slug exists). Phase 2B is the per-consumer migration to the `poppins` slug.

**Role mapping for the migration (Poppins everywhere; Hero Regular optional future):**

| Element / role | Phase 2B target | Weight |
|---|---|---|
| H1, hero titles, primary page headings, campaign headlines, key brand statements | `poppins` | **400 (Regular)** |
| H2, H3, H4, H5, section titles, feature titles, navigation, important CTAs, newsletter emphasis | `poppins` | **700 (Bold)** |
| Body copy, descriptions, product details, form labels, captions, helper text, supporting content | `poppins` | **400 (Regular)** |

**Migration order (one component or section at a time):**

1. ✅ Add `poppins` font-family token to `theme.json` — done in Phase 2A.
2. **Migrate `theme.json` element styles** (`h1`, `h2`, `h3`, body `styles.typography`) to `var(--wp--preset--font-family--poppins)` with the weights above. Visual delta for H2 / H3 / body should be ~none (compatibility aliases already render them as Poppins). H1 will flip from Noto Serif SemiCondensed to Poppins Regular — visible brand change.
3. **Migrate structural typography** (component CSS for body, nav, CTAs) from `var(--wp--preset--font-family--proxima-nova)` → `var(--wp--preset--font-family--poppins)`. Apply Bold weight where role is nav / CTA / heading; Regular for body.
4. **Migrate H4 / H5 component CSS** to `poppins` Bold.
5. **Address bypass cases:** `apple-system` stack in `blocks/inquiry/style.css`, `blocks/contact/style.css`; `"Proxima Nova Light"` literal in `blocks/blogs-view/style.css`. Replace with `poppins` slug at the appropriate weight.
6. **Migrate `.newsletter-strip__title em`** from Times New Roman to `poppins` Bold per §5 #15.
7. **Verify per section:** weight, size, line-height (= `normal`) render as expected; no FOUT / FOIT regressions; fallback stack behaves acceptably on slow networks.
8. **Optional future brand-refinement phase:** if Hero Regular font files are provided later, re-evaluate H1 / hero / page / campaign / key-brand-statement headings and migrate them to `hero-regular`. Not a Phase 2B dependency.
9. Once every consumer has migrated, retire `proxima-nova` and `noto-serif-semicondensed` tokens and the Proxima Nova compatibility aliases in `assets/css/fonts.css` (Phase 3 cleanup).

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
- **Remove the Proxima Nova compatibility aliases from `assets/css/fonts.css`** (the `font-family: "Proxima Nova"` and `font-family: "Proxima Nova Light"` blocks pointing at Poppins TTFs added in Phase 2A) once every consumer has migrated to the `poppins` slug.
- Remove the `proxima-nova` font-family slug from `theme.json` once no consumer remains (i.e. once all body / nav / CTA / heading usage has migrated to `poppins`).
- Remove the `noto-serif-semicondensed` font-family slug from `theme.json` once no consumer remains (i.e. once H1 / hero / discover / mosaic blocks have migrated to `poppins`). Also drop the corresponding Google Fonts `@import` in `inc/enqueue.php` if no consumer remains.
- Remove the legacy Times New Roman literal from `blocks/newsletter-strip/style.css:36` once Phase 2B replaces it with `poppins` Bold.
- Delete the legacy `ink` palette slug if no consumers remain.
- Convert the footer's two overlapping rule sets (line 3-308 vs. 405-765) into one source of truth.
- Replace the `(provisional)` status tokens with designer-final hexes when they arrive.
- Final type/color audit using the same script as `typograph_report.text`; expect the pink count to collapse from 35+ to 4 (`brand-pink-soft`, `brand-pink-muted-rose`, `brand-pink-light-blush`, `brand-pink-accent`), near-blacks from 134 references to 0 (all `--text-main`), grays from 124 to 0 (all `--text-muted`).

### Optional future — Hero Regular brand refinement (NOT on the current roadmap)

If Hero Regular font files are provided and approved later:

1. Add `Hero-Regular.*` font files to `assets/fonts/` and the corresponding `@font-face` declarations to `assets/css/fonts.css`.
2. Add the `hero-regular` font-family slug to `theme.json` (additive).
3. Re-migrate H1 / hero / page / campaign / key-brand-statement headings from `poppins` (weight 400) to `hero-regular`.
4. Treat this as a controlled brand-refinement PR with per-component QA. **Not a Phase 2B or Phase 3 dependency.**

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

## Appendix A — Approved hex → CSS var quick map (aligned to Color Palette Guide + interactive role mapping)

| Hex | Designer role / interactive use | Token | CSS var |
|---|---|---|---|
| `#EFB5BE` | **Primary (Soft Pink) · CTA fill** | `brand-pink-soft` | `var(--wp--preset--color--brand-pink-soft)` |
| `#E199A4` | **Secondary (Muted Rose)** | `brand-pink-muted-rose` | `var(--wp--preset--color--brand-pink-muted-rose)` |
| `#FBDDE2` | **Secondary (Light Blush) · CTA hover · Soft background** | `brand-pink-light-blush` | `var(--wp--preset--color--brand-pink-light-blush)` |
| `#D81B60` | **Accent / badge (white text required, WCAG AA ~4.95:1)** — NEW, proposed §6.3 | `brand-pink-accent` | `var(--wp--preset--color--brand-pink-accent)` |
| `#333333` | CTA text (on pink fills) — matches existing `ink` slug | `ink` (or literal `#333333`) | `var(--wp--preset--color--ink)` |
| `#000000` | Text main / strong border | `text-main` / `border-strong` | `var(--wp--preset--color--text-main)` / `--border-strong` |
| `#525252` | Text muted / placeholder / status-info | `text-muted` / `placeholder` / `status-info` | `var(--wp--preset--color--text-muted)` |
| `#FFFFFF` | White / input bg / accent text | `white` / `input-bg` | `var(--wp--preset--color--white)` / `--input-bg` |

> The earlier `brand-pink-primary: #E199A4` mapping is withdrawn (§4.1, §5 #17). `#E199A4` is **Secondary (Muted Rose)** per the Color Palette Guide.

## Appendix B — Files inventoried for this report

- `theme.json`
- `style.css`
- `assets/css/header.css`, `assets/css/footer.css`, `assets/css/single-product.css`
- All `blocks/*/style.css` (~35 files)
- All `templates/*.html` and `parts/*.html`
- `inc/helpers.php`, `inc/shortcodes.php`, `inc/theme-setup.php`, `inc/woocommerce-pdp.php`, `inc/woocommerce-checkout.php`
- `typograph_report.text` (prior audit, cross-referenced)
