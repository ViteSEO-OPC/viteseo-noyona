# Typography & Color Token — Phase 1 Implementation Report

> **Status:** Phase 1 shipped. Token plumbing + isolated CSS bug fix only. **Phase 2 has NOT been started.** No component refactor, no font-family migration, no layout-grid application, no CTA migration.

Source plan: [`docs/typography-color-token-implementation-plan.md`](./typography-color-token-implementation-plan.md) (§14 "Recommended safe Phase 1").

---

## 1. Summary

Phase 1 ships three things, all additive:

1. **`theme.json`** — new font-size slugs (`h-4`, `h-5`, `nav-button`, `label`, `caption`, `helper`, `input`) and new color palette slugs aligned to the designer Color Palette Guide naming (`brand-pink-soft` = Primary, `brand-pink-muted-rose` = Secondary, `brand-pink-light-blush` = Secondary, plus `text-main` / `text-muted` / `white` / `input-bg` / `placeholder` / `border-strong` / `status-info` / provisional `status-success` / `status-warning` / `status-error`). All existing slugs preserved.
2. **`style.css`** — a new `:root` block of Phase 1 token plumbing (radius scale, card-padding scale, legacy screen-padding tokens, letter-spacing bug-fix var, minimum text size, brand color shorthands, provisional status shorthands, layout grid baseline). All existing `:root` variables preserved.
3. **CSS bug fix** — 5 invalid `letter-spacing: -5%` declarations replaced with `letter-spacing: -0.05em` in `blocks/hero-banner/style.css`, `blocks/brand-carousel/style.css`, and `assets/css/header.css`. This is the **only expected visual delta** — those rules were silently dropped by browsers before this fix.

No component CSS was modified beyond the 5 letter-spacing locations. No font-family migration. No layout-grid application. No CTA / card / pink / border / text-color refactor.

---

## 2. Files changed

| File | Change |
|---|---|
| `theme.json` | Added 7 font-size slugs and 13 color palette slugs (all additive — existing slugs preserved). |
| `style.css` | Added a new `:root` block of Phase 1 token plumbing after the existing `:root` block (existing block untouched). |
| `blocks/hero-banner/style.css` | 2 × `letter-spacing: -5%` → `letter-spacing: -0.05em` (lines 81, 141). |
| `blocks/brand-carousel/style.css` | 1 × `letter-spacing: -5%` → `letter-spacing: -0.05em` (line 67). |
| `assets/css/header.css` | 2 × `letter-spacing: -5%` → `letter-spacing: -0.05em` (lines 300, 386). |
| `docs/typography-color-token-phase-1-implementation.md` | This report (new). |

No other files were modified. No code files outside this list were touched.

---

## 3. Exact `theme.json` font-size tokens added

All new tokens are additive — existing `body`, `h-3`, `h-2`, `h-1` slugs were not modified.

| Slug | Size | Designer target (per Typography Guide) |
|---|---|---|
| `h-4` | `clamp(24px, 2.5vw, 32px)` | H4 = 32 / 28 / 24, Bold 700 |
| `h-5` | `clamp(20px, 1.9vw, 24px)` | H5 = 24 / 22 / 20, Bold 700 |
| `nav-button` | `16px` | Navigation / button text = 16px, Bold 700 |
| `label` | `16px` | Provisional weight 600 (Typography Guide silent) |
| `caption` | `16px` | Provisional weight 600 (Typography Guide silent) |
| `helper` | `16px` | Provisional weight 400 (Typography Guide silent) |
| `input` | `16px` | Provisional weight 400 (Typography Guide silent) |

**Weights are not encoded into the size tokens.** `theme.json` `fontSizes` entries only carry `slug` / `name` / `size`. Weight is applied per-element style or per-component CSS in Phase 2, when consumers opt in.

**`line-height` is not baked into the size tokens.** Designer Typography Guide line-height (1.5 × font-size) rollout is Phase 2.

**`letter-spacing` is not baked into the size tokens.** Typography Guide does not specify letter-spacing.

---

## 4. Exact `theme.json` color tokens added

All new tokens are additive — existing `ink = #333333` slug was preserved.

| Slug | Name | Color | Designer source |
|---|---|---|---|
| `brand-pink-soft` | Brand Pink Soft / Primary | `#EFB5BE` | Color Palette Guide: **Primary** |
| `brand-pink-muted-rose` | Brand Pink Muted Rose / Secondary | `#E199A4` | Color Palette Guide: **Secondary** |
| `brand-pink-light-blush` | Brand Pink Light Blush / Secondary | `#FBDDE2` | Color Palette Guide: **Secondary** |
| `text-main` | Text Main | `#000000` | Color Palette Guide |
| `text-muted` | Text Muted | `#525252` | Color Palette Guide |
| `white` | White | `#FFFFFF` | Color Palette Guide |
| `input-bg` | Input Background | `#FFFFFF` | Designer-confirmed |
| `placeholder` | Placeholder | `#525252` | Designer-confirmed |
| `border-strong` | Border Strong | `#000000` | Available; borders are contextual — do not globally apply |
| `status-info` | Status Info | `#525252` | Designer-confirmed |
| `status-success` | Status Success (provisional) | `#E6F4EA` | **Provisional — not designer-final** |
| `status-warning` | Status Warning (provisional) | `#FFF8E1` | **Provisional — not designer-final** |
| `status-error` | Status Error (provisional) | `#FDEAEA` | **Provisional — not designer-final** |

**Old wrong slug `brand-pink-primary: #E199A4` was NOT added.** Per the Color Palette Guide, `#E199A4` is **Secondary (Muted Rose)**, not the primary color. `#EFB5BE` is the Primary (Soft Pink).

**CTA hover token NOT added.** Pending designer mapping.

---

## 5. Font-family tokens added

**None.** No `hero-regular`, `poppins`, or `times-italic` tokens were added in Phase 1.

### Asset availability finding (relevant to Phase 2 unblock)

A repo scan was performed. `assets/css/fonts.css` registers `@font-face` declarations **only for Proxima Nova variants** (Regular, Semibold, Bold, ExtraBold, Black) and "Proxima Nova Light". There are **no `@font-face` declarations for Hero Regular or Poppins**.

Working-tree observations (not part of this implementation):

- Poppins TTF files appear to be **present but unstaged** under `assets/fonts/` (untracked in git). They are not yet referenced by any `@font-face` rule.
- Hero Regular font files are **not present** in `assets/fonts/`.
- Proxima Nova font files are marked for deletion in the working tree (also not part of this implementation).

**Consequence for Phase 1:** Adding `hero-regular` or `poppins` font-family tokens to `theme.json` now would either silently fall back to system fonts (because no `@font-face` rule loads them) or risk a Flash Of Invisible Text / regression. Per the implementation plan §5 #14 (Hero Regular / Poppins font asset availability blocker) and §6.1 (gating), font-family token additions are deferred until:

1. Font files are committed to `assets/fonts/`.
2. `@font-face` declarations are added to `assets/css/fonts.css` (or equivalent).
3. The font loader (`wp_enqueue_style` / `assets/css/fonts.css` registration in `inc/theme-setup.php` or `functions.php`) is confirmed to load them.
4. Fallback stacks are confirmed with the designer.

None of these steps were performed in Phase 1.

---

## 6. Exact `style.css` variables added

A new `:root` block was added **after** the existing `:root` block. The existing block (`--page-gutter-left`, `--page-gutter-right`, `--page-gutter-inline`, `--content-max`, `--toc-top`, `--section-mt`, `--footer-bg`, `--footer-text`, `--footer-btn-bg`) was **not modified**.

The new `:root` block adds:

| Category | Variables |
|---|---|
| Radius scale | `--noyona-radius-card: 24px`, `--noyona-radius-cta: 16px`, `--noyona-radius-input: 12px` |
| Card padding | `--noyona-card-pad-desktop: 15px`, `--noyona-card-pad-tablet: 12px`, `--noyona-card-pad-mobile: 10px` |
| Legacy screen padding (pending designer confirmation; NOT the universal grid outer margin) | `--noyona-screen-pad-desktop: 110px`, `--noyona-screen-pad-tablet: 50px`, `--noyona-screen-pad-mobile: 20px`, `--noyona-screen-pad: clamp(20px, 6vw, 110px)` |
| Typography helpers | `--noyona-letter-spacing-bugfix: -0.05em` (scoped to the 5 bug-fix sites only — NOT a universal tracking system) |
| Minimum text size | `--noyona-text-min: 16px` |
| Brand color shorthands | `--noyona-color-pink-soft`, `--noyona-color-pink-muted-rose`, `--noyona-color-pink-light-blush`, `--noyona-color-text-main`, `--noyona-color-text-muted`, `--noyona-color-white`, `--noyona-color-input-bg`, `--noyona-color-placeholder`, `--noyona-color-border-strong` — each references the `--wp--preset--color--*` token with a hard-coded hex fallback |
| Provisional status color shorthands | `--noyona-color-status-info`, `--noyona-color-status-success`, `--noyona-color-status-warning`, `--noyona-color-status-error` |
| Layout grid baseline (NOT applied to any container) | `--noyona-grid-columns-desktop: 12`, `--noyona-grid-columns-tablet: 8`, `--noyona-grid-columns-mobile: 4`, `--noyona-grid-gutter-desktop: 24px`, `--noyona-grid-gutter-tablet: 16px`, `--noyona-grid-gutter-mobile: 16px`, `--noyona-grid-margin-desktop: 24px`, `--noyona-grid-margin-tablet: 24px`, `--noyona-grid-margin-mobile: 16px` |

**CTA hover token intentionally NOT defined** (comment in `:root` records this).

**None of these variables are consumed elsewhere in Phase 1.** Consumers opt in per component in Phase 2.

---

## 7. Exact letter-spacing fixes

5 invalid `letter-spacing: -5%` declarations were replaced with `letter-spacing: -0.05em`.

| File | Line(s) before | Description |
|---|---|---|
| `blocks/hero-banner/style.css` | 81, 141 | Hero title and accent-line typography |
| `blocks/brand-carousel/style.css` | 67 | Brand carousel slide label |
| `assets/css/header.css` | 300, 386 | Header nav-link and (likely) related nav element |

The plain literal `-0.05em` was used (not `var(--noyona-letter-spacing-bugfix)`), which is the safest exact fix — it doesn't depend on the new token plumbing.

A repo-wide `grep -R "letter-spacing: -5%" .` now returns **zero results**.

**Expected visual delta:** the affected text will now render with the designed −5 % tracking. Browsers previously dropped the invalid percentage declaration entirely, so these rules paint for the first time.

---

## 8. Things intentionally NOT changed

Per Phase 1 hard-stop rules:

- ❌ No component refactor.
- ❌ No application of the new tokens to components.
- ❌ No font-family migration. Global H1 font-family unchanged. No flip to Proxima Nova, no flip to Hero Regular or Poppins.
- ❌ No `hero-regular`, `poppins`, or `times-italic` tokens added.
- ❌ No change to existing `h-1`, `h-2`, `h-3`, or `body` token values.
- ❌ No global line-height change. No rollout of `line-height: 1.5`.
- ❌ No universal `letter-spacing: -0.05em` rollout (scoped to the 5 invalid declarations only).
- ❌ No pink hex replacement.
- ❌ No CTA hover token defined.
- ❌ No CTA text color change.
- ❌ No CTA / button border-radius change. 124 × `999px` pills remain pills.
- ❌ No card radius or padding change.
- ❌ No layout grid applied to any container or component. No `.noyona-grid` class created.
- ❌ No product grid column change.
- ❌ No hero / banner / carousel / image / icon / tap-target change beyond the letter-spacing fix.
- ❌ No screen / container padding rollout.
- ❌ No removal of legacy `proxima-nova`, `noto-serif-semicondensed`, or `ink` tokens.
- ❌ No removal of the duplicate `body` / `h1` / `h2` / `h3` declarations in `style.css:111-137`.
- ❌ No touch to footer duplicate rules, admin styles, invoice/email, map, or social brand colors.
- ❌ No change to newsletter typography.

---

## 9. Remaining blockers before Phase 2

Phase 2 component migration is gated on the following clarifications (from the implementation plan):

1. **Pink interactive role mapping** (§5 #1) — Color Palette Guide names `#EFB5BE` / `#E199A4` / `#FBDDE2` as Primary / Secondary / Secondary, but the interactive mapping (CTA fill / CTA hover / soft bg / accent / badge) is still pending.
2. **Primary CTA hover color** (§5 #2) — no hex provided.
3. **Hero Regular / Poppins font asset availability** (§5 #14) — see §5 of this report; assets are not yet loaded.
4. **Mobile H1 exact size** (§5 #16) — designer mobile table malformed; line-height implies ~40px, awaiting confirmation.
5. **Line-height 1.5 vs prior "`normal`" note** (§5 #6) — Typography Guide says 1.5×, prior project note said `normal`; needs re-confirmation before global rollout. WCAG 2.2 SC 1.4.12 text-spacing must be preserved.
6. **CTA text color / accessibility decision** (§5 #18) — Color Palette Guide implies dark text on pink fills (white-on-pink fails contrast); existing white-on-pink CTAs need contrast audit before remediation.
7. **Newsletter emphasis target** (§5 #15) — `.newsletter-strip__title em` should migrate to Hero Regular, Poppins Italic, or another designer-approved style; pending.
8. **`110 / 50 / 20` screen padding confirmation** (§5 #19, §8.5) — these values are not from the Layout Grid Specification; awaiting designer decision whether to retain (marketing/page-inset) or replace with the universal grid margins (24 / 24 / 16).

---

## 10. Verification steps run

The following commands were run and their results captured below.

### 10.1 `git status --short`

Expected: only the 6 files in §2 modified (plus any unrelated working-tree state pre-existing before this implementation).

### 10.2 `python3 -m json.tool theme.json`

Expected: parses cleanly, no syntax error. The updated `theme.json` is valid JSON.

### 10.3 `grep -R "letter-spacing: -5%" .`

Expected: **zero results** after the bug fix.

### 10.4 `git diff --stat`

Expected: 6 files changed corresponding to §2. The font-file additions / Proxima Nova deletions in the working tree are unrelated to this implementation and were already present before Phase 1 started.

### 10.5 `git diff -- <touched files>`

Used to verify the actual changes match the documented scope above.

See the implementation transcript / PR description for raw output.

---

## 11. Expected visual delta

- **Token additions:** none. New `theme.json` slugs and new `:root` variables are not consumed by any component in Phase 1, so the rendered site is identical to before.
- **CSS bug fix:** the 5 sites that had `letter-spacing: -5%` will render with `-0.05em` tracking for the first time, because browsers previously dropped the invalid percentage value. This is the **only** expected visible change. Affected locations:
  - Hero banner title / accent line (2 sites)
  - Brand carousel slide label (1 site)
  - Header nav-link (2 sites)

Spot-check these areas in a browser; the rest of the site should be visually identical to today.

---

## 12. Reversibility

Phase 1 is fully reversible by reverting these files to their prior state:

- `theme.json`
- `style.css`
- `blocks/hero-banner/style.css`
- `blocks/brand-carousel/style.css`
- `assets/css/header.css`
- `docs/typography-color-token-phase-1-implementation.md` (delete)

No data migration, no schema change, no runtime side effects to undo.
