# Images & Icons — Noyona Child Theme

Source of truth for hero banners, category/collection cards, product
images, icons, and touch targets. Newest designer update wins. Agents
must enforce these rules and may not invent new ones.

## Hero banners

- **Desktop**: 16:9 or 21:9 wide aspect.
- **Mobile**: must not simply shrink the desktop hero.
- Mobile should use 1:1 or 4:5 layout/crop when needed.
- Image clarity and text readability must be preserved at every
  breakpoint.

## Category / collection cards

| Breakpoint | Columns |
|---|---|
| Desktop | 4–6 |
| Tablet | 3 |
| Mobile | 2, **or** 1.5-column carousel |

- Card grids must be responsive — do not freeze a desktop column count
  on mobile.

## Product images

- Desktop: thumbnails + hover zoom allowed.
- Tablet / Mobile: swipeable carousel where applicable.
- Tablet / Mobile: pinch zoom where applicable.
- Product images must remain high-resolution enough to support zoom
  (do not downscale source assets).

## Icons and touch targets

| Context | Icon size | Target size |
|---|---|---|
| Desktop header icon | 24px | 32px |
| Tablet header icon | 24px | 44px |
| Mobile header icon | 24px | 48px |
| Footer icons | 18–20px | 32–44px |

- Touch targets must be accessible — never below the values above on
  the matching breakpoint.
- Avoid cramped icon rows and mis-tap risk on mobile.

## What counts as a violation

- Mobile hero that is the desktop hero scaled down without a 1:1 / 4:5
  crop alternative.
- Card grids that keep desktop column counts on tablet or mobile.
- Product images locked to a fixed small size that prevents zoom.
- Header icon target below 32px desktop / 44px tablet / 48px mobile.
- Footer icon target below 32px.
- Icons larger or smaller than the table above without justification.

## Governed CSS properties and markup

This doc only governs the following when fixing image / icon
findings:

- Hero `<picture>` / `<img srcset>` source selection and aspect crop
  per breakpoint
- Image / icon `width`, `height`, `aspect-ratio`, `object-fit`,
  `object-position`
- Card grid `grid-template-columns` **only** to enforce the 4–6 / 3 /
  2 column rule (any other grid concern belongs to `layout-grid.md`)
- Icon SVG `width` / `height` (the icon glyph size)
- Wrapping link / button hit-area sizing: `min-width`, `min-height`,
  `padding` (only to bring a tap target up to spec)
- Carousel / zoom interaction CSS where it affects mobile/tablet
  product images
- `srcset` / `sizes` attributes in `render.php`

**Not governed by this doc** (must not be changed when fixing an
image / icon finding, unless a different project doc requires it):

- `font-*`, `color`, `background`, `border-color`, `box-shadow color`
- `text-align`, `letter-spacing`, `line-height`
- `transform`, `transition`, `animation`
- `position`, `z-index`
- Component-internal text / chrome unrelated to the image or icon

## Scope of enforcement

- `assets/css/**`
- `blocks/**/style.css`
- `blocks/**/render.php` (icon markup, hero markup, srcset)
- `parts/**`, `templates/**`
- `inc/**`, `woocommerce/**`
- `assets/images/**` (asset availability only, no edits)

## Conflict policy

If older docs, comments, or commits suggest different aspect ratios,
column counts, or target sizes, the rules above win. Report the
conflict — do not silently follow the older rule.
