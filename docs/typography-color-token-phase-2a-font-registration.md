# Typography & Color Token — Phase 2A: Font Registration Cleanup

> **Status:** Phase 2A shipped. Local font registration cleanup only. **Component font-family migration has NOT started.** No component CSS edited. No element-style `fontFamily` changed. No Hero Regular work (files still missing).

Source plan: [`docs/typography-color-token-implementation-plan.md`](./typography-color-token-implementation-plan.md) §5 #14, §6.1, §16 Phase 2 pre-flight #1.
Predecessor: [`docs/typography-color-token-phase-1-implementation.md`](./typography-color-token-phase-1-implementation.md).

---

## 1. Summary

Phase 2A fixes the broken local font registration without starting component migration.

**Problem (pre-Phase 2A):** `assets/css/fonts.css` had 6 `@font-face` rules pointing at Proxima Nova OTF/TTF files that no longer exist in `assets/fonts/`. The site silently fell back to system sans-serif for every consumer of `font-family: "Proxima Nova"` (body, H2, H3, nav, CTAs).

**Fix in this step (additive + targeted bridge):**

1. **`assets/css/fonts.css` rewritten** so that:
   - Every `font-family: "Proxima Nova"` `@font-face` now points at the equivalent-weight Poppins TTF file. These rules are **temporary compatibility aliases** that keep existing `proxima-nova` consumers rendering as real webfonts (Poppins) immediately, without requiring component migration. The "Proxima Nova" family name is preserved so existing CSS keeps working.
   - A separate set of `font-family: "Poppins"` `@font-face` rules registers Poppins for direct consumption (Regular, Italic, Medium, SemiBold, Bold, BoldItalic, ExtraBold, Black, Light).
   - No `@font-face` rule references a missing file anywhere.
2. **`theme.json` gets a new additive `poppins` font-family slug** (`"Poppins", system-ui, …`). Existing `proxima-nova` and `noto-serif-semicondensed` slugs are preserved. **The new slug is NOT assigned to any element style** — that's the next sub-step of Phase 2.

Visual delta: pages that consume `proxima-nova` now render in **real Poppins** instead of system sans-serif. That is the intended correction — it restores webfont rendering that has been silently broken since Proxima Nova files were removed.

Component font-family migration (changing `font-family: "Proxima Nova"` → `var(--wp--preset--font-family--poppins)` in block CSS and theme.json element styles) is **NOT done here**.

---

## 2. Files changed

| File | Change |
|---|---|
| `assets/css/fonts.css` | Rewritten: 6 broken Proxima `@font-face` rules → 6 compatibility-alias rules pointing at Poppins TTF files + 9 real `font-family: "Poppins"` `@font-face` rules. |
| `theme.json` | Added one new font-family slug: `poppins`. Existing `proxima-nova` and `noto-serif-semicondensed` slugs preserved. No element-style change. |
| `docs/typography-color-token-implementation-plan.md` | Updated §5 #14 status to reflect Poppins registration complete. (Post-Phase 2A decision: Hero Regular is reframed as an **optional future brand-refinement**, not a Phase 2B blocker — see plan §5 #14, §6.1, §6.4, §16 "Optional future".) |
| `docs/typography-color-token-phase-1-implementation.md` | Updated §9.2 "Still blocked" to reflect Phase 2A is shipped. (Post-Phase 2A decision: Hero Regular reframed as optional future, not a Phase 2B blocker.) |
| `docs/typography-color-token-phase-2a-font-registration.md` | This report (new). |

No other files were touched. No component CSS, no PHP, no JS, no HTML, no font-binary changes.

---

## 3. Poppins files used (already present in `assets/fonts/`)

All Poppins TTF files used by Phase 2A were already committed in `assets/fonts/` from a prior step (audit confirmed). Files referenced by `fonts.css` now:

- `Poppins-Light.ttf` (300 / normal)
- `Poppins-Regular.ttf` (400 / normal)
- `Poppins-Italic.ttf` (400 / italic)
- `Poppins-Medium.ttf` (500 / normal)
- `Poppins-SemiBold.ttf` (600 / normal)
- `Poppins-Bold.ttf` (700 / normal)
- `Poppins-BoldItalic.ttf` (700 / italic)
- `Poppins-ExtraBold.ttf` (800 / normal)
- `Poppins-Black.ttf` (900 / normal)

Additional Poppins TTF variants (`Thin`, `ThinItalic`, `ExtraLight`, `ExtraLightItalic`, `LightItalic`, `MediumItalic`, `SemiBoldItalic`, `BlackItalic`) remain in `assets/fonts/` but are not yet registered in `fonts.css`. They can be added if/when designer requires them.

`OFL.txt` (Open Font License) is committed in `assets/fonts/` alongside the font files.

---

## 4. Proxima Nova compatibility alias — explanation

`assets/css/fonts.css` keeps six `@font-face` blocks with `font-family: "Proxima Nova"` (and one `"Proxima Nova Light"`). Each `src:` points at the appropriate-weight **Poppins** TTF instead of the deleted Proxima file:

| `font-family` | weight | style | `src` | Effective font |
|---|---|---|---|---|
| `"Proxima Nova"` | 400 | normal | `../fonts/Poppins-Regular.ttf` | Poppins Regular |
| `"Proxima Nova"` | 600 | normal | `../fonts/Poppins-SemiBold.ttf` | Poppins SemiBold |
| `"Proxima Nova"` | 700 | normal | `../fonts/Poppins-Bold.ttf` | Poppins Bold |
| `"Proxima Nova"` | 800 | normal | `../fonts/Poppins-ExtraBold.ttf` | Poppins ExtraBold |
| `"Proxima Nova"` | 900 | normal | `../fonts/Poppins-Black.ttf` | Poppins Black |
| `"Proxima Nova Light"` | 300 | normal | `../fonts/Poppins-Light.ttf` | Poppins Light |

**Why a compatibility alias instead of an immediate rename / removal:**

- The `proxima-nova` slug in `theme.json` and many component CSS files reference `font-family: "Proxima Nova"` directly. Renaming the family to `"Poppins"` in those declarations is component migration work that the prompt explicitly forbids in Phase 2A.
- Deleting the `"Proxima Nova"` `@font-face` rules entirely would degrade those consumers back to the system-stack fallback declared in the `proxima-nova` slug — the same broken state we're fixing.
- The alias is **the gentlest possible bridge**: real webfont rendering is restored immediately, no component touches, and per-component migration to the `poppins` slug stays a separate, low-risk PR that should be a no-op visual change.

The alias rules are **temporary**. They are documented to be deleted once every consumer has migrated from `proxima-nova` → `poppins` (Phase 3 cleanup).

---

## 5. `theme.json` slug added

One additive entry in `settings.typography.fontFamilies`:

```json
{
  "fontFamily": "\"Poppins\", system-ui, -apple-system, \"Segoe UI\", sans-serif",
  "name": "Poppins",
  "slug": "poppins"
}
```

Effects:

- WordPress exposes `var(--wp--preset--font-family--poppins)` for consumers.
- The Poppins font-family is now available in the block editor's font picker.
- **No element style references the new slug.** `h1`, `h2`, `h3`, and the body `typography.fontFamily` rule still reference their existing slugs (`noto-serif-semicondensed` for H1, `proxima-nova` for H2 / H3 / body). Element-style migration is the next Phase 2 sub-step.

The existing `proxima-nova` and `noto-serif-semicondensed` slugs were **not** modified or removed.

The `hero-regular` slug was **not** added — Hero Regular font files are still missing (§5 #14 of the plan).

---

## 6. Things intentionally NOT changed

- ❌ No component CSS edited. No block stylesheet, no `style.css`, no `header.css`, no `footer.css`.
- ❌ No `theme.json` element-style `fontFamily` change. H1 still references `noto-serif-semicondensed`; H2 / H3 / body still reference `proxima-nova`.
- ❌ No removal of the `proxima-nova` slug.
- ❌ No removal of the `noto-serif-semicondensed` slug.
- ❌ No `hero-regular` slug added.
- ❌ No Hero Regular `@font-face` rules added.
- ❌ No newsletter typography migration.
- ❌ No line-height, letter-spacing, font-size, color, CTA, card-radius, layout-grid, or image/icon change.
- ❌ No deletion of font files in `assets/fonts/`.
- ❌ No PHP / JS / HTML touched. The existing enqueue at `inc/enqueue.php` already loads `assets/css/fonts.css` on every front-end page; no enqueue change needed.

---

## 7. Verification results

### 7.1 `git status --short`

Phase 2A files modified/added: `assets/css/fonts.css`, `theme.json`, `docs/typography-color-token-implementation-plan.md`, `docs/typography-color-token-phase-1-implementation.md`, `docs/typography-color-token-phase-2a-font-registration.md`. No other code files touched by Phase 2A.

### 7.2 `python3 -m json.tool theme.json`

`theme.json` parses cleanly as valid JSON. The new `poppins` slug is well-formed.

### 7.3 `grep -R "proximanova_" assets/css/fonts.css`

**Zero results.** All references to deleted `proximanova_*.{ttf,otf}` files have been removed from `fonts.css`.

### 7.4 `grep "font-family: \"Poppins\"" assets/css/fonts.css`

9 results — Poppins is registered at weights 300, 400 (normal + italic), 500, 600, 700 (normal + italic), 800, 900.

### 7.5 `grep "font-family: \"Proxima Nova\"" assets/css/fonts.css`

6 results — all 5 `"Proxima Nova"` weight variants + 1 `"Proxima Nova Light"`, each now pointing at a Poppins TTF (compatibility alias).

### 7.6 No component CSS changed

`git diff --stat` confirms changes only in `assets/css/fonts.css`, `theme.json`, and the three doc files. No `blocks/*`, no `style.css`, no `inc/*`, no `templates/*`.

### 7.7 No Hero Regular references added

`grep -i "hero[-_ ]regular\|hero-regular" assets/css/fonts.css theme.json` returns nothing.

---

## 8. Expected visual delta

This step **does** produce a visible change, by design: previously-broken `proxima-nova` consumers now render in **real Poppins** instead of system sans-serif. That is the recovery from the silent regression that existed since Proxima Nova files were deleted.

Spot-check after deployment:

- Body text, H2 headings, H3 headings, nav, CTAs, account section, PDP body copy — should now render as Poppins (rounded, geometric sans), not the OS default sans-serif.
- H1 still renders in Noto Serif SemiCondensed (loaded externally via Google Fonts in `inc/enqueue.php`). No change for H1.
- Newsletter `.newsletter-strip__title em` still renders in Times New Roman (literal). No change yet; migration to Poppins Bold is later (§5 #15 of the plan).

If a visitor previously had Proxima Nova installed locally, their experience changes from "real Proxima Nova" to "real Poppins" — this matches the post-Phase 1 designer direction (Hero Regular + Poppins) and is the intended outcome.

---

## 9. Remaining work after Phase 2A

| # | Topic | Status |
|---|---|---|
| Plan §16 (Phase 2B) | **Component font-family migration** (`proxima-nova` → `poppins`, H1 → `poppins` 400) | ⏭ Next Phase 2B sub-step. Replace `font-family: var(--wp--preset--font-family--proxima-nova)` / `font-family: "Proxima Nova"` references in component CSS and `theme.json` element styles with the `poppins` slug. Includes migrating `styles.elements.h1.typography.fontFamily` from `noto-serif-semicondensed` → `poppins` at weight 400 (Poppins Regular). Visual delta should be ~none for body / H2 / H3 thanks to Phase 2A compatibility aliases; H1 will visibly change from Noto Serif to Poppins Regular. |
| Plan §5 #15 | **Newsletter emphasis migration to Poppins Bold** | ⏭ Phase 2B sub-step. Migrate `blocks/newsletter-strip/style.css:36` from Times New Roman literal to `var(--wp--preset--font-family--poppins)` Bold. |
| Plan §6.3 | **`brand-pink-accent` token (`#D81B60`) for accent / badge** | ⏭ Documentation-only proposal post-Phase 2A. Phase 2B-or-later additive `theme.json` change. Use white text for WCAG AA (~4.95:1); do not use as body or default CTA fill. |
| Plan §5 #14 | **Hero Regular asset availability** | 🟡 **NOT a blocker for Phase 2B.** Hero Regular files are still missing, but the practical Phase 2B target for H1 / hero / page / campaign / key-brand-statement headings is **Poppins Regular / 400**, not Hero Regular. If Hero Regular files are provided later, the H1 / hero role may be re-migrated to `hero-regular` in a separate **optional brand-refinement phase** (plan §6.1, §6.4, §16 "Optional future"). |

All other previously-resolved decisions (line-height = `normal`, mobile H1 = 40px, CTA fill / hover / text, **soft background = `#FBDDE2`**, **accent / badge = `#D81B60` with white text**, screen-padding scales coexistence, pink color slug naming) remain stable.

---

## 10. Reversibility

Phase 2A is fully reversible by reverting:

- `assets/css/fonts.css` (back to the previous, broken-but-original Proxima-only set of `@font-face` declarations).
- `theme.json` (remove the `poppins` slug).
- The three doc files.

No font binaries were added or removed by Phase 2A. No component CSS was touched. No element-style assignments were touched. Revert is a clean three-file diff.
