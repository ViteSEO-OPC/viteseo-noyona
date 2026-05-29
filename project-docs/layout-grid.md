# Layout & Grid — Noyona Child Theme

Source of truth for responsive grid, gutters, and horizontal container
spacing. Newest designer update wins. Agents must enforce these rules
and may not invent new ones.

> **Designer update (current):** the old 110 / 50 / 20 marketing
> section padding is **retired**. Horizontal container/page spacing is
> now a single system: **24px desktop, 24px tablet, 16px mobile**. Any
> earlier guidance that called for 110 / 50 / 20 is obsolete.

## Grid

| Breakpoint | Columns | Gutters | Horizontal container padding |
|---|---|---|---|
| Desktop | 12 | 24px | 24px |
| Tablet | 8 | 16px | 24px |
| Mobile | 4 | 16px | 16px |

- Use fluid / stretch columns.
- Use CSS Grid (or equivalent responsive CSS).
- No fixed pixel widths on top-level page containers.

## Horizontal container / page spacing

This is the space between page/section content and the screen edges.

| Breakpoint | Horizontal padding |
|---|---|
| Desktop | 24px |
| Tablet | 24px |
| Mobile | 16px |

- This **supersedes** the retired 110 / 50 / 20 marketing padding rule.
- **Implementation note:** this spacing may be applied with whatever
  property the component already uses — it does **not** have to be a
  `margin`. Acceptable mechanisms include:
  - `padding-inline`
  - `padding-left` / `padding-right`
  - a shared container gutter variable (e.g.
    `var(--page-gutter-left)` / `var(--page-gutter-right)`)
  - outer wrapper / container padding
- Enforce the correct **values** (24 / 24 / 16), not a specific
  property name. A block using `padding-inline: var(--page-gutter-*)`
  is compliant if the variable resolves to 24 / 24 / 16.
- Preserve centered `max-width` container behavior where it exists —
  correct the outer horizontal spacing values without removing the
  `max-width` / centering.
- Component-internal padding (card insets, tile padding, button
  padding) is **not** governed by this rule — leave it alone.

## Accessibility / reflow

- No horizontal scrolling at 320px viewport width.
- Respect WCAG 1.4.10 reflow.
- Containers must allow text to wrap; do not force `nowrap` on
  full-width text rows.
- Touch targets are owned by `image-icon.md` but layout must not
  shrink targets below the values defined there.

## What counts as a violation

- A fixed `width: <NNNpx>` on a container that should be fluid.
- Gutter / gap values other than 24px (desktop) and 16px (tablet /
  mobile) on grid containers — unless documented as an intentional
  component override.
- Horizontal container/page padding other than 24px (desktop /
  tablet) and 16px (mobile) on top-level page/section containers —
  regardless of whether it is applied via `padding-inline`,
  `padding-left/right`, a gutter variable, or wrapper padding.
- Re-introducing the retired 110 / 50 / 20 marketing padding values.
- Anything that causes horizontal scroll at 320px.

## Governed CSS properties

This doc only governs the following properties. Agents may **only**
modify these when fixing layout findings:

- Grid container properties: `display: grid`, `grid-template-columns`,
  `grid-template-rows`, `grid-auto-flow`
- Flex container properties **when used as a grid substitute** for
  responsive layout: `display: flex`, `flex-wrap`, `flex-direction`
- `gap`, `column-gap`, `row-gap`, `grid-gap`
- Outer page-level horizontal spacing applied via any of:
  `padding-inline`, `padding-left` / `padding-right`, a container
  gutter variable, or outer wrapper padding — corrected to the
  24 / 24 / 16 values above
- Outer page-level `padding-block` / vertical wrapper padding (only
  where it acts as page/section spacing)
- Container `max-width`, `width`, `min-width` (only the cases that
  conflict with fluid / reflow rules)

**Not governed by this doc** (must not be changed when fixing a
layout finding, unless a different project doc requires it):

- `font-*`, `color`, `background`, `border-*`, `box-shadow`
- `text-align`, `letter-spacing`, `line-height`
- `transform`, `transition`, `animation`
- `position`, `top` / `right` / `bottom` / `left`, `z-index`, except
  when an absolute-positioned element causes 320px overflow
- Component-internal spacing inside cards / tiles / inline groups
  that doesn't affect the outer grid

## Scope of enforcement

- `style.css`
- `assets/css/**`
- `blocks/**/style.css`
- `parts/**`, `templates/**`
- `woocommerce/**`
- `theme.json` (`settings.layout`)

## Conflict policy

If older docs, comments, or commits suggest different grid or padding
rules, the rules above win. Report the conflict — do not silently
follow the older rule.
