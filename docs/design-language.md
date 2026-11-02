# Design language

**Purpose:** Give contributors a concise guide to Blueline's visual principles and shared UI tokens.

**Audience:** Contributors changing templates, styles or interactive UI.

## Principles

- Extend existing components and styles before introducing new visual patterns.
- Reuse the shared tokens below; avoid one-off colours, spacing and effects where an existing token or style fits.
- Give the interface a clear visual hierarchy so important information and primary actions are easy to find.
- Keep interactions predictable and consistent across the application, with clear feedback for loading, success, errors and selected states.
- Let layouts adapt to their available space: use fluid sizing and wrapping where appropriate, and introduce breakpoints when the content needs a meaningful change in arrangement.
- Check UI changes at narrow and wide viewport sizes; avoid fixed dimensions that cause clipping, overflow or unnecessary horizontal scrolling.
- Keep text readable, use sufficient contrast and don't rely on colour alone to communicate information.
- Use semantic controls, preserve keyboard access and provide visible focus.
- Keep visual treatment restrained so it supports the content and task. Use subtle, fluid motion to clarify interactions, not as decoration, and respect reduced-motion preferences.
- Reuse the individual SVG icons in `assets/images/`. Add more if needed rather than using inline SVGs.
- The bell colours used in method diagrams are a separate visualisation palette, not general UI colours.

## Shared tokens

The values are defined in [`all.css`](../assets/styles/all.css). Refer to that file for current values rather than duplicating them here.

| Purpose | Tokens |
| --- | --- |
| Colour and surfaces | `--background`, `--header-background`, `--title-background`, `--title-background-lightened`, `--title-background-overlay`, `--light-grey`, `--light-grey-lightened` |
| Typography | `--sans-stack`, `--serif-stack` |
| Layout and spacing | `--content-max-width`, `--content-x-padding`, `--content-y-padding` |
| Corners and elevation | `--radius-sm`, `--radius-md`, `--radius-lg`, `--shadow-2`, `--shadow-4`, `--shadow-8` |
| Motion | `--animation-speed`, `--easing-standard` |

Use the existing link styles rather than adding new link colours. `--animation-speed` already responds to `prefers-reduced-motion`; new motion should preserve that behavior.

## Where implementation details live

This guide describes shared design intent, not the exact layout or behavior of every component. Check the relevant Twig template, stylesheet and JavaScript when changing a specific UI pattern; keep detailed implementation constraints alongside the code they describe.

For general frontend and styling workflows, see the [development guide](development-deployment.md). For application-wide architecture and conventions, see [architecture and workflows](architecture-and-workflows.md).
