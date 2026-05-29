# Color — Noyona Child Theme

Source of truth for color across the Noyona child theme. Newest designer
update wins. Agents must enforce these rules and may not invent new ones.

## Approved palette

| Role | Hex |
|---|---|
| Main brand pink (Normal CTA fill) | `#EFB5BE` |
| Secondary pink | `#E199A4` |
| Soft pink surface / background / Normal CTA hover / Primary CTA hover | `#FBDDE2` |
| Accent / badge / sale label / cart count / active pill / strong purchase CTA | `#D81B60` |
| Dark text / Normal CTA text | `#333333` |
| White | `#FFFFFF` |
| Black | `#000000` |

No other brand colors are approved. Status colors and neutrals already
defined in `theme.json` (text-muted, status-*, etc.) are tolerated only
where they were intentionally added; new shades may not be introduced.

## CTA rules

- **Normal CTA**
  - Fill: `#EFB5BE`
  - Text: `#333333`
  - Hover: `#FBDDE2`
- **Primary CTA hover**: `#FBDDE2`
- **Soft pink surfaces / backgrounds**: `#FBDDE2`
- Pink CTA buttons must always use `#333333` text for accessibility.
- **Never** put white text on soft pink (`#EFB5BE` or `#FBDDE2`) CTAs.
- Do not invent unrelated hover colors. Only the values above are valid.
- Purchase CTAs default to the Normal CTA style.
- If a purchase CTA requires stronger emphasis, use `#D81B60` fill with
  `#FFFFFF` text. This is the only place white text on pink is allowed.

## Accent usage (`#D81B60`)

Reserved for:

- Badges
- Sale labels
- Cart count
- Active pills
- Strong purchase-critical CTAs only

Do not use `#D81B60` for general backgrounds, body text, or normal CTAs.

## Debug rule (property-level)

- `#ff0000` (or `red`, `rgb(255,0,0)`) is allowed **only** as a
  temporary local violation marker during a visual-debug phase.
- It is not a brand color and must not ship.
- **Only the violating color property may be replaced with `#ff0000`.**
  Do not turn entire components red unless every color property on the
  component is the violation.
- Example — given:

  ```css
  .blog-slide h2 {
    color: #ff4d6d;
    text-align: left;
    font-size: 44px;
  }
  ```

  the debug-mark step changes **only `color`**, leaving `text-align`
  and `font-size` untouched:

  ```css
  .blog-slide h2 {
    color: #ff0000;
    text-align: left;
    font-size: 44px;
  }
  ```

- Final QA must remove or resolve every `#ff0000` debug style before
  approval.

## Governed CSS properties

This doc only governs the following properties. Agents may **only**
modify these when fixing color findings:

- `color` (text color)
- `background`, `background-color`, `background-image` (where a brand
  color is specified)
- `border-color` (only where a brand color is specified — generic
  neutral borders fall under the P3 "functional neutral" band)
- `outline-color`
- `fill`, `stroke` (SVG brand color usage)
- CSS custom properties that resolve to a color value
- WordPress `var(--wp--preset--color--*)` references
- Inline `style="color: …"` / `style="background: …"` in PHP / HTML

**Not governed by this doc** (must not be changed when fixing a color
finding, unless a different project doc requires it):

- `text-align`, `font-*`, `letter-spacing`, `line-height`
- `width`, `height`, `padding`, `margin`, `gap`
- `border-width`, `border-style`, `border-radius`
- `box-shadow` size / blur / spread (the **color stop** inside a shadow
  is governed by this doc; the geometry is not)
- `opacity`, `transform`, `transition`, `animation`
- `position`, `top` / `right` / `bottom` / `left`, `z-index`
- `display`, `flex-*`, `grid-*`
- Any other property that is not a color value

## Scope of enforcement

- `style.css`
- `theme.json` palette + styles
- `assets/css/**`
- `blocks/**/style.css`
- `blocks/**/render.php` (inline color, badges, pills, cart count)
- `parts/**`, `templates/**`
- `inc/**`, `woocommerce/**` (any PHP that emits markup or inline style)

## Conflict policy

If older docs, comments, or commits suggest a different palette or CTA
treatment, the rules above win. Report the conflict — do not silently
follow the older rule.
