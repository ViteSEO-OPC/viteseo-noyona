# Typography & Color Token — Phase 2B Slice 1a: theme.json body + H2 → Poppins

> **Status:** Smallest atomic typography migration slice shipped. **theme.json only.** No CSS file edited. H1 and H3 explicitly NOT touched in this slice (separate Slice 1b / 1c).

Source plan: [`docs/typography-color-token-implementation-plan.md`](./typography-color-token-implementation-plan.md) §6.4, §16 Phase 2B.
Predecessor reports: [`docs/typography-color-token-phase-1-implementation.md`](./typography-color-token-phase-1-implementation.md), [`docs/typography-color-token-phase-2a-font-registration.md`](./typography-color-token-phase-2a-font-registration.md).

---

## 1. Summary

Phase 2B Slice 1a is the smallest possible typography migration slice. It changes two `theme.json` fields:

1. `styles.typography.fontFamily` (the global body) — `proxima-nova` → `poppins`.
2. `styles.elements.h2.typography.fontFamily` — `proxima-nova` → `poppins`. Also removes the explicit `lineHeight: "1.1"` so H2 inherits the production-target `normal` line-height (§5 #6).

That is the entire slice. **No CSS file was touched.** **H1 and H3 were not touched.** **No size token, color token, weight (other than H2's existing 700, kept), letter-spacing, CTA, card, layout, or image change.**

Because Phase 2A `assets/css/fonts.css` already aliases `font-family: "Proxima Nova"` to the Poppins TTF files, the body and H2 visual rendering was already Poppins before this slice. Swapping the `theme.json` slug from `proxima-nova` → `poppins` removes one layer of indirection without changing the visible glyphs.

The only intentional visible delta is on H2 line-height: removing `1.1` lets the browser use `normal` (~ 1.2 in most fonts), which slightly increases vertical spacing on H2.

---

## 2. Files changed

| File | Change |
|---|---|
| `theme.json` | `styles.typography.fontFamily` and `styles.elements.h2.typography.fontFamily` switched from the `proxima-nova` slug to the `poppins` slug. Explicit H2 `lineHeight: "1.1"` removed. |
| `docs/typography-color-token-phase-2b-slice-1a.md` | This report (new). |

No other files were touched. **No** `style.css`, **no** `assets/css/*.css`, **no** `blocks/*/style.css`, **no** PHP / JS / HTML / template / font / image / icon changes.

---

## 3. Exact `theme.json` body change

**Before** (top-level `styles.typography`):

```json
"typography": {
  "fontSize": "var(--wp--preset--font-size--body)",
  "fontWeight": "400",
  "fontFamily": "var(--wp--preset--font-family--proxima-nova)"
},
```

**After**:

```json
"typography": {
  "fontSize": "var(--wp--preset--font-size--body)",
  "fontWeight": "400",
  "fontFamily": "var(--wp--preset--font-family--poppins)"
},
```

Only the `fontFamily` line changed. `fontSize` (still `var(--wp--preset--font-size--body)`) and `fontWeight` ("400") are preserved. **No `lineHeight` was added.** Production line-height is `normal`, which is the CSS default when no `lineHeight` is set — so omitting the property is the correct way to express "normal" here.

---

## 4. Exact `theme.json` H2 change

**Before** (`styles.elements.h2`):

```json
"h2": {
  "color": { "text": "inherit" },
  "typography": {
    "fontSize": "var(--wp--preset--font-size--h-2)",
    "fontWeight": "700",
    "lineHeight": "1.1",
    "fontFamily": "var(--wp--preset--font-family--proxima-nova)"
  }
},
```

**After**:

```json
"h2": {
  "color": { "text": "inherit" },
  "typography": {
    "fontSize": "var(--wp--preset--font-size--h-2)",
    "fontWeight": "700",
    "fontFamily": "var(--wp--preset--font-family--poppins)"
  }
},
```

Two changes only:

- `fontFamily`: `proxima-nova` → `poppins`.
- `lineHeight: "1.1"` removed entirely so H2 falls back to the production target of `normal` (§5 #6, §3, §6.2). **Not replaced with `"1.5"` or any other explicit value.**

Preserved: `color: inherit`, `fontSize` (`var(--wp--preset--font-size--h-2)`), `fontWeight` (700).

---

## 5. Confirmations

### 5.1 H1 was not touched

The `styles.elements.h1` block in `theme.json` is unchanged. H1 still references `var(--wp--preset--font-family--noto-serif-semicondensed)` at `fontWeight: "700"` with `lineHeight: "1.05"`. H1 migration to Poppins Regular 400 is **Slice 1c**, a separate PR.

### 5.2 H3 was not touched

The `styles.elements.h3` block in `theme.json` is unchanged. H3 still references `var(--wp--preset--font-family--proxima-nova)` at `fontWeight: "600"` with `lineHeight: "1.15"`. The H3 weight bump (600 → 700) and font-family swap to Poppins is **Slice 1b**, a separate PR.

### 5.3 No component CSS was touched

`git diff --stat` confirms only `theme.json` and the new report file changed. No `style.css`, no `assets/css/*.css`, no `blocks/*/style.css`, no PHP / JS / HTML / template files. The duplicate body/h1/h2/h3 block in `style.css:179-208` is intentionally left alone in this slice (Phase 2B Slice 6 or Phase 3 cleanup).

### 5.4 `line-height: 1.5` was NOT introduced

No new `lineHeight` property was added anywhere. The H2 `lineHeight: "1.1"` was removed (now resolves to `normal`). The production target is `normal`, which the plan §5 #6 documents as designer-confirmed.

### 5.5 No size / color / CTA / card / layout / image / icon changes

Confirmed by `git diff`. Only two `fontFamily` swaps and one `lineHeight` removal in `theme.json`.

---

## 6. Expected visual delta

| Surface | Expected change |
|---|---|
| Body text (everywhere) | **None expected.** The Phase 2A compatibility alias in `assets/css/fonts.css` already mapped `font-family: "Proxima Nova"` → Poppins TTF files. Body was already rendering in Poppins; this slice removes one layer of indirection without changing the rendered glyphs. |
| H2 font family (everywhere) | **None expected**, same reason. |
| H2 line-height | **Subtle increase.** H2 was using `line-height: 1.1`; now resolves to `normal` (~1.2 in most fonts). Tall H2 paragraphs gain slightly more vertical breathing room. Single-line H2s should look essentially the same. |
| Anything else | None. No size / weight (other than the unchanged 700 on H2) / color / radius / layout / image change. |

Note: component CSS in many places sets its own `font-family` on `h2` selectors directly. Those will continue to win on cascade specificity over the `theme.json` element style, so the actual on-page H2 rendering remains driven by component CSS until the broader Slice 2 component sweep migrates those rules too. Slice 1a therefore primarily affects H2 elements that **don't** have a component-level `font-family` override (e.g. WordPress block-rendered headings without a class).

---

## 7. QA checklist

Spot-check after deploy (cache-bust theme version if needed):

- [ ] **Home page**: body paragraphs, hero subtitle, and any block-rendered H2 headings (not styled by component CSS) render correctly. No layout collapse from H2 line-height change.
- [ ] **PDP** (product detail page): body description text, related-products H2, reviews H2 render correctly.
- [ ] **Product archive / category**: body filter text, product card titles (if rendered as H2), section H2s render correctly. Filter labels still readable.
- [ ] **Account page** (login, register, dashboard, orders, addresses): body labels, form text, account section H2s. Watch for any input-label overlap that might be sensitive to H2 line-height.
- [ ] **Cart / checkout**: body line items, totals, checkout step H2s, terms text. No conversion-path regression.
- [ ] **Generic blocks** (any page that includes default `core/heading` H2 blocks without a custom class): verify H2 line spacing looks acceptable.
- [ ] **Editor sidebar** (block editor): "Poppins" appears as the default body font and as the H2 default. (Cosmetic editor check; no impact on front end.)
- [ ] **Cache**: hard-refresh after `wp_get_theme()->get('Version')` cache-busts (or bump theme version). Confirm Network shows updated `theme.json` / `wp-global-styles` output.

If H2 line-height feels too loose anywhere, the fix is per-component (set an explicit `line-height` in that component's CSS), not a global rollback.

---

## 8. Next recommended slices

1. **Phase 2B Slice 1b — H3 migration.** `styles.elements.h3.typography`: `fontFamily` `proxima-nova` → `poppins`; `fontWeight` `600` → `700`; remove `lineHeight: "1.15"`. Subtle weight bump (SemiBold → Bold) is the visible delta.
2. **Phase 2B Slice 1c — H1 migration.** `styles.elements.h1.typography`: `fontFamily` `noto-serif-semicondensed` → `poppins`; `fontWeight` `700` → `400`; remove `lineHeight: "1.05"`. This is the **visible brand change** (Noto Serif Bold → Poppins Regular) — designer-confirmed direction (§3, §6.4).
3. **Phase 2B Slice 2 — low-risk component CSS sweep.** Per-component swap of `var(--wp--preset--font-family--proxima-nova)` → `var(--wp--preset--font-family--poppins)` in `style.css`, `assets/css/footer.css` (excluding the email/invoice block at line 441), and non-conversion-critical `blocks/*/style.css` files. Visual delta ~none thanks to Phase 2A compatibility aliases.
4. **Phase 2B Slice 3 — conversion-critical components.** Header, cart, checkout, PDP, single-product — one per PR with conversion-path verification.
5. **Phase 2B Slice 4 — Noto-Serif / Apple-stack / "Proxima Nova Light" one-offs.** `blocks/hero-banner/`, `blocks/discover-face-banner/`, `blocks/mosaic-grid/`, `blocks/blogs-view/`, `blocks/contact/`, `blocks/inquiry/`. Per-site review.
6. **Phase 2B Slice 5 — newsletter `em` Times-New-Roman migration.** `blocks/newsletter-strip/style.css:34-37` → Poppins Bold Italic.
7. **Phase 2B Slice 6 (or defer to Phase 3) — duplicate `style.css:179-208` body/h1/h2/h3 block cleanup.**

---

## 9. Reversibility

Phase 2B Slice 1a is fully reversible by reverting `theme.json` and deleting this report. No CSS / font / asset / PHP / JS / HTML change exists to undo.

Specifically, the revert is: restore `fontFamily` to `var(--wp--preset--font-family--proxima-nova)` on body and H2, and restore `lineHeight: "1.1"` on H2.
