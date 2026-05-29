# Typography — Noyona Child Theme

Source of truth for fonts, sizes, weights, and line-height. Newest
designer update wins. Agents must enforce these rules and may not
invent new ones.

> **Designer update (current):** Hero Regular is **not** required.
> H1 and main page headings use **Poppins Regular**. Any earlier
> guidance that called for Hero Regular is obsolete.

## Font families

- **Poppins Regular** — used for:
  - H1 / main page headings
  - Hero titles
  - Campaign headlines
  - Key brand statements
  - Body text
  - Descriptions
  - Product details
  - Form labels
  - Captions
  - Helper text
- **Poppins Bold** — used for:
  - H2–H5
  - Subheadings
  - Section titles
  - Feature titles
  - Navigation
  - Buttons
  - Newsletter emphasis

There is no third active font family. Hero Regular is **not** part of
the current spec — do not register it, do not load font files for it,
do not flag its absence.

## Font assets and loading

- Poppins is a free Google Font.
- Poppins font files live in `assets/fonts/`.
- Fonts must be loaded/declared globally through `style.css` and
  registered in `theme.json`.
- Do **not** create a separate `fonts.css` unless it becomes necessary.
- **Proxima Nova has been removed.** No file, declaration, fallback
  list, or comment may reference Proxima Nova.

## Font sizes

### Desktop
| Token | Size |
|---|---|
| H1 | 64px |
| H2 | 48px |
| H3 | 36px |
| H4 | 32px |
| H5 | 24px |
| Body | 16px |
| Navigation / Button | 16px |

### Tablet
| Token | Size |
|---|---|
| H1 | 48px |
| H2 | 40px |
| H3 | 32px |
| H4 | 28px |
| H5 | 22px |
| Body | 16px |
| Navigation / Button | 16px |

### Mobile
| Token | Size |
|---|---|
| H1 | 40px |
| H2 | 32px |
| H3 | 28px |
| H4 | 24px |
| H5 | 20px |
| Body | 16px |
| Navigation / Button | 16px |

Existing `clamp()` definitions in `theme.json` are acceptable if their
min/max values match these breakpoints. Otherwise they must be revised.

## Line-height

- **Current rule: `line-height: normal;`.**
- Do **not** apply a global `1.5` (or 1.5x) line-height.
- Older docs that recommend 1.5x are outdated.
- Do **not** preserve `1.5` just because it "looks readable".
- A non-normal line-height is acceptable only when explicitly
  documented in `project-docs/` or approved by the designer for a
  specific component (the override must be scoped to that component).
- QA must fail any forced global `1.5` line-height.

## Implementation rules

- Typography must be responsive across desktop / tablet / mobile.
- Avoid fixed-height text containers that clip text.
- Headings, buttons, and nav labels must not overflow on mobile.
- Maintain the approved font family, size, and weight pairings.

## Heading-like selectors (component overrides)

Many components ship custom selectors that visually function as
H1–H5 even though the markup may not use a real `<h1>`–`<h5>` tag,
or the component adds a rule that overrides the global heading
style. These selectors must follow the same typography spec as the
matching heading level.

Common naming patterns (non-exhaustive) — treat any selector that
matches one of these patterns as a heading candidate:

- `*-title`, `*__title`, `.title`
- `*-heading`, `*__heading`, `.heading`, `.wp-block-heading`
- `*-headline`, `*__headline`, `.headline`
- `*-subtitle`, `*__subtitle`, `.subtitle`
- `*-section-title`, `*__section-title`
- `*-block-title`, `*__block-title`
- `*-hero-title`, `*__hero-title`
- `*-card-title`, `*__card-title`
- `*-product-title`, `*__product-title`
- `*-reviews-title`, `*__reviews-title`
- `*-banner-title`, `*__banner-title`
- `*-eyebrow` (used like a label / subhead)

For each heading-like selector:

1. Decide which heading role it plays based on its visual size and
   semantic position (H1 if it is the main page headline, H2 if it
   is a section title, etc.).
2. Compare its `font-family`, `font-size`, `font-weight`,
   `line-height`, and `letter-spacing` against the spec for that
   role.
3. If a typography-governed property conflicts, fix only that
   property. Preserve every unrelated property (color, padding,
   margin, text-align, etc.) per the property-level governance
   rules elsewhere in this doc.

If the visual role of a selector is ambiguous, report it as a
question instead of guessing.

## Governed CSS properties

This doc only governs the following properties. Agents may **only**
modify these when fixing typography findings:

- `font-family`
- `font-size`
- `font-weight`
- `font-style` (only where a doc rule specifies italic / normal)
- `line-height`
- `letter-spacing`

**Not governed by this doc** (must not be changed when fixing a
typography finding, unless a different project doc requires it):

- `color`, `background`, `border-color`
- `text-align`, `text-transform`, `text-decoration`
- `width`, `height`, `padding`, `margin`, `gap`
- `display`, `flex-*`, `grid-*`
- `position`, `top` / `right` / `bottom` / `left`, `z-index`
- `opacity`, `transform`, `transition`, `animation`
- `border-radius`, `box-shadow` geometry

## Scope of enforcement

- `style.css`
- `theme.json`
- `assets/css/**`
- `assets/fonts/**` (Poppins files present)
- `blocks/**/style.css`
- `parts/**`, `templates/**`
- `inc/**` (any PHP that enqueues fonts or emits inline typography)
- `woocommerce/**`

## Conflict policy

If older docs, comments, or commits reference Proxima Nova, Hero
Regular, 1.5x line-height, or different sizes, the rules above win.
Report the conflict — do not silently follow the older rule.
