# Beautiful Recent Posts revival

## Scope

Build a local 5.0.0-beta.1 candidate. Preserve the BRP_Widget class, brp_widget ID and the four original settings so saved widgets keep working. Keep the external published release unchanged. The local beta readme and plugin headers both identify 5.0.0-beta.1; no WordPress.org or GitHub release is published.

## Direction

Keep the existing product name. Proposed identity: a compact editorial list mark, deep teal, paper cream, ink typography and a small warm accent. Supply editable SVG sources and WordPress directory PNG sizes, including a retina banner. This is a standalone product identity, not a change to the GT website brand.

A cosmetic-only refresh leaves block-theme users without a native block. A full page-builder or recommendation engine would add unrelated complexity. Choose one shared renderer with a classic widget, dynamic block and shortcode.

## Features

- List and card layouts, responsive to narrow containers.
- Category, date/modified/title order, 1-20 posts, optional current-post exclusion.
- Optional thumbnails, date, author, comments and a bounded plain-text excerpt.
- Native responsive images; semantic dates and keyboard focus styles.
- Existing page-link button, with only public destinations rendered.
- Native block inspector and server preview; no frontend JavaScript.

## Compatibility and safety

Retain PHP 7.4 syntax compatibility; test current PHP 8.5 explicitly. Minimum WordPress 6.6 supports block API v3. Use WordPress-provided JavaScript packages with no bundled React or jQuery. Test development tools on Node 24 LTS and 26 Current in CI. Sanitize and bound settings at every entry point. Query only published, non-password-protected posts. Never change the caller's global post.

## Verification

Run PHP lint and real WordPress integration tests for legacy settings, hostile input, query behavior, rendering and block registration. Check editor JavaScript with Node, exercise block editor controls in a local browser, and inspect desktop/mobile/RTL/no-image layouts. Keep validation claims tied to actual runtimes. Package an installable prerelease ZIP with development files excluded. No production deployment or public release is implied.
