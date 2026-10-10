# Design language

This documents the visual language used across Blueline's UI so future additions fit in without a fresh design pass each time. It's inspired by Microsoft's [Fluent 2](https://fluent2.microsoft.design), implemented as bespoke CSS custom properties in `assets/styles/` - there is no `@fluentui` package dependency.

## Colour

Defined in `assets/styles/all.css` `:root`:

| Token | Value | Usage |
| --- | --- | --- |
| `--header-background` | `#002856` | Primary accent: header bar, active states, and focus outlines |
| `--background` | `#fff` | Page background |
| `--title-background` | `#f3f3f3` | Section headers, hover backgrounds, sticky category headings |
| `--title-background-lightened` | `#f9f9f9` | Full-width band beside the section header / search cards at `≥1201px` |
| `--title-background-overlay` | `rgba(243,243,243,.95)` | Search bar background (sits over content while scrolling) |
| `--light-grey` | `#e0ded9` | Borders, dividers |
| `--light-grey-lightened` | `#ebeae7` | Lighter list-item dividers |
| Link | `#29404c` | Unvisited link text |
| Visited link | `#4c2940` | Visited link text |
| Active link | `#404c29` | Active link text |
| Link underline | `#a1c4d0` | Default link decoration |

The palette is intentionally small: one accent colour (deep blue), greys, and the link-state colours above. New UI should reuse these tokens and colours rather than introducing new ones. These link colours are currently literal CSS values, not custom properties.

Header links are an exception to the general link palette: links in `#top` use `--light-grey`, turn white on hover, and use a white keyboard-focus outline so they remain legible on the blue header background.

Note that the bell-line diagram colours (`workingBellColor`, `huntBellColor` in `assets/js/helpers/GridOptionsBuilder.js`) are a separate data-visualisation palette used to distinguish bells in drawn method lines/grids. They are not part of the UI colour system and must not be merged with or reused as UI tokens.

## Typography

- Base body text: 16px, `line-height: 1.25em`, `--sans-stack` (system font stack).
- Section body text: 14px.
- Section `h2`: 18px, `h3`: 12px.
- Page/section `<header>` title (`h1`): 28px; subtitle (`h2`): 20px.
- Headings (`h1`/`h2`) and fieldset legends use `--serif-stack` for a touch of editorial contrast; body copy stays sans-serif.

## Spacing

- `--content-x-padding` / `--content-y-padding` are the base spacing units (14px/12px on small screens), widened to 20px/16px at `≥1201px` for extra breathing room on larger screens. Most padding/margin values in `layout.css`/`components.css` are expressed as `calc()` multiples of these two tokens - prefer extending that pattern over hardcoding new pixel values.

## Corner radius and elevation

Fluent-inspired scales, introduced as tokens rather than one-off values:

- `--radius-sm` (4px), `--radius-md` (6px), `--radius-lg` (8px) - use `sm` for small controls (checkboxes, dropdown items), `md` for standard inputs/buttons/search field, `lg` for larger overlapping surfaces (the header/search overlap card).
- Use shadows sparingly: reserve them for the settings dialog and dropdown form elements. Do not add shadows to header overlap cards or other surfaces.
- `--shadow-2`, `--shadow-4`, `--shadow-8` are available for those permitted uses, with increasing elevation.

## Motion

- `--animation-speed` (100ms, reduced to 1ms under `prefers-reduced-motion`) drives existing hover/transition timing.
- `--easing-standard` (`cubic-bezier(0.33, 0, 0.67, 1)`) is available for new transitions (e.g. the tab underline, header overlap reveal) - a Fluent-style "easy ease" curve.
- View Transitions are used for tab switches (`tab-left`/`tab-right`) and breadcrumb changes; reuse `assets/js/ui/TabBar.js`'s pattern for any new transition-based UI rather than inventing a new mechanism.

## Icons

No icon font/library is used. Icons are vendored individually as local `.svg` files in `assets/images/` (e.g. `search.svg`, `filter.svg`, `settings.svg`, `external.svg`, `star.svg`, `star-outline.svg`), applied via CSS `background-image` or an inline `<img>`.

When Material Design Icons are needed, follow the same convention: copy only the specific glyph(s) required from the Material Design Icons project into `assets/images/` as standalone `.svg` files (Apache-2.0 licensed) - do not add an `@mdi/*` npm/importmap dependency for a handful of glyphs.

## Layout patterns

### Centralised content column with full-width breakout

At `≥1201px`, `#content` is capped at `--content-max-width` (1200px), centred with `margin: 0 auto`, and given light `border-left`/`border-right` (`--light-grey`). Up to `1200px` content remains edge-to-edge.

Elements that need more room than the column offers (currently only the method-line diagram, `#method_line`) use `.line-breakout` at `≥1201px`. It is centred on the viewport with `left: 50%` and `transform: translateX(-50%)`; `width: max-content` lets it grow to fit, while `min-width: 100%` and `max-width: 100vw` keep it between the content-column width and viewport width. Its flex layout centres short lines and wraps content that reaches the viewport limit. `MethodView.js` toggles `.wide` when the rendered line exceeds the content column; `layout.css` uses `body:has(#method_line.wide)` to hide the column borders while that wide line is displayed. Keep the measurement and border behavior in sync if changing this layout.

When adding new wide content (tables, diagrams, etc.), prefer reusing `.line-breakout` over inventing a new breakout mechanism.

### Header / search overlap card

- `#top` (the blue header bar) grows taller via `--header-bar-height` (66px at `≥1201px`, otherwise equal to `--header-height`). The logo/breadcrumb and settings button keep their `--header-height`-driven size. At wide sizes `#top` uses flex alignment to keep them at the top corners; from `1720px`, it centers them vertically and adds horizontal inset padding.
- When running as an installed desktop app with window controls overlay (`display-mode: window-controls-overlay`, enabled via `display_override` in the manifest), `#top` acts as the titlebar: `--header-height` is raised to at least 44px and `env(titlebar-area-height)` (so the small-screen bar is taller than a standard titlebar; the 66px wide bar is kept), `#top` keeps its normal horizontal padding but is padded further if needed to stay clear of the OS window controls, and it is a drag region (`app-region: drag`) with links/buttons set to `no-drag`.
- A single centred "card", matching the content column's width, visually overlaps the bottom of the tall bar by `--header-overlap` (32px):
  - On listing/search pages, the card is `#search`.
  - On method view pages, the card is the method `<header>` (title + tabs) - the same overlap slot, not stacked with search.
- Both cards sit above `#top` (`z-index: 44` vs `#top`'s `43`) so they paint over the blue background in the overlap band.
- Both cards are `border-box` and exactly `--content-max-width` wide, positioned with the same `max(0px, (100% - max) / 2)` offset as the `body::before`/`::after` column lines, so their 1px borders land on the same pixel as those lines. Keep them in sync.
- `#content`'s top padding accounts for the overlap (`var(--header-bar-height) + var(--search-box-height) - var(--header-overlap)` when searchable, or the header's own `margin-top: calc(-1 * var(--header-overlap))` on method pages) so page content sits correctly below the card either way.
- Up to `1200px`, none of this applies - the header/search stack exactly as before.

Any new page type that needs a header-overlapping card should reuse `--header-bar-height`/`--header-overlap`/`--content-max-width` rather than hardcoding new offsets.

### Search filters

The expanded search filters use two equal, fluid columns on wide screens, each capped at 445px with a 16px column gap. At `≤1200px` they collapse to one centred column capped at the same width. Keep the `minmax(0, …)` constraints: they allow filter controls and Choices.js selections to shrink without forcing the search card wider than its available space. Choices.js selected items scroll horizontally inside the control; preserve that local scrolling when adjusting the grid.

## Components

- **Tabs** (`.tabBar`, driven by `assets/js/ui/TabBar.js`): a flat Fluent-tablist style - no border box around each tab, just an underline indicator (`border-bottom`) in `--header-background` on the active tab. Hovered tabs get a `--light-grey-lightened` fill and the active tab a subtly lighter `--title-background-lightened` fill. External tabs (e.g. Practice) use the same text styling and are distinguished only by the trailing external-link icon. Only CSS governs the look; the JS contract (`id="tab_<content>"`, `data-fragment`, `data-external`) must be preserved by any future restyle.
- **Pagination** (`.paging`/`.pagingLinks`, `templates/Macros/paging.html.twig`): always centred (`text-align: center`), no separate small/large-screen layout.
- **Forms**: text/number inputs, selects, and Choices.js dropdowns share `--radius-sm`/`--radius-md` and `--shadow-2`; buttons (`input[type=submit/reset]`) use `--radius-md`/`--shadow-2`. Keyboard focus is indicated on native controls with `:focus-visible`; search and Choices.js controls use a `:focus-within` outline because their inner input outlines are suppressed.
