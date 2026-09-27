=== Beautiful Recent Posts Widget ===
Contributors: gauravtiwari
Donate link: https://buymeacoffee.com/gauravtiwari
Tags: recent posts, widget, thumbnails, block, sidebar
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 5.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show recent posts with thumbnails, category filters, excerpts, and list or card layouts. Use a native block, classic widget, or shortcode.

== Description ==

Beautiful Recent Posts helps readers find another story on your WordPress site. Add recent posts to a sidebar, page, or block-theme template, choose which details to show, and let the layout inherit your theme's typography and colors.

= What you can do =

* **Choose where posts appear:** insert the Beautiful Recent Posts block in the Block Editor or Site Editor, add the classic widget to a widget area, or use a shortcode.
* **Pick a layout:** use an editorial list for compact spaces or responsive cards for a wider section. List thumbnails can be circular, rounded, or square.
* **Choose the stories:** display 1–20 posts, filter by category, sort by publication date, last update, or title, and optionally exclude the current post.
* **Control the details:** show or hide featured images, publication dates, authors, comment counts, and excerpts. Set excerpt length from 5–60 words.
* **Add a next step:** display an optional button with your own label, linking to a published page.
* **Keep existing widgets:** the original WordPress.org plugin filename, widget ID, title, post count, button text, and destination are preserved. Activation from the GitHub-only 4.x filename migrates automatically.

There is no separate settings dashboard, account requirement, or license key. Configure each widget or block where you use it. The block provides a live preview and sidebar controls; shortcode attributes use the same settings.

The public post lists use server-rendered HTML and a small stylesheet, with no frontend JavaScript or external fonts. Featured images use WordPress's responsive image markup and lazy loading. Keyboard focus styles and logical spacing support accessible navigation and right-to-left layouts.

Development happens in the open on [GitHub](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress), where bug reports and pull requests are welcome.

= Links =

* [Documentation and Shortcode Reference](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress#use-it) - settings, examples, and styling options.
* [Changelog](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress/releases) - release notes and downloadable packages.
* [Feature Requests and Bug Reports](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress/issues) - report a problem or suggest an improvement.
* [WordPress.org Support](https://wordpress.org/support/plugin/beautiful-recent-posts-widget/) - get help with the plugin.
* [Community](https://gauravtiwari.org/portal/) - ask questions and share your WordPress work.
* [More WordPress Plugins](https://gauravtiwari.org/wordpress-plugins/) - other plugins by Gaurav Tiwari.

== Installation ==

1. In WordPress, open Plugins > Add New, search for Beautiful Recent Posts Widget, and install it. You can also upload the release ZIP.
2. Activate Beautiful Recent Posts Widget through the Plugins screen.
3. Open a post, page, or Site Editor template and insert the Beautiful Recent Posts block. For classic themes with widget areas, open Appearance > Widgets and add Beautiful Recent Posts.
4. Choose the layout, category, post count, and details to display. Optionally add a button linking to a published page.
5. If older featured images need the plugin's 85px and 170px square crops, regenerate their thumbnails. New uploads generate these sizes automatically while the plugin is active.

== Frequently Asked Questions ==

= Will my existing widgets survive the update? =

Yes. The original widget ID and saved title, post count, button label, and destination are retained. Dates, comments, and circular thumbnails stay enabled by default. The styling is refreshed, so review any custom CSS on a staging site before updating production.

= Does it work with block themes and the Site Editor? =

Yes. Insert the native Beautiful Recent Posts block in a post, page, or Site Editor template. Classic themes can use the widget in their registered widget areas. The block and widget use the same renderer and settings.

= Can I use a shortcode? =

Yes. For four cards with excerpts, use `[beautiful_recent_posts totalnews="4" layout="cards" show_excerpt="true"]`. For a specific category while excluding the current post, use `[beautiful_recent_posts category="12" exclude_current="true"]`. Replace 12 with your category ID. See the [shortcode reference](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress#use-it) for all supported attributes.

= Can I filter or reorder the posts? =

Yes. Select a category and choose newest first, recently updated, or title A–Z. Category filtering includes child categories. The block lists the first 100 categories and pages alphabetically; Advanced selection accepts IDs for items beyond those results. The displayed date remains the original publication date even when sorting by last update.

= What happens when a post has no featured image? =

The title and enabled details still display without an empty image placeholder. Quote and aside post formats keep the original widget's behavior of omitting thumbnails. Existing images may need their thumbnails regenerated for the plugin's square crops.

= Does it show private or password-protected posts? =

No. The list includes only published posts without a password. The optional button also requires a public, non-password-protected page. Custom post types are not included.

= Can I match the list to my theme? =

Yes. Typography and colors inherit from your theme, and you can choose the layout and list thumbnail shape in the controls. Developers can adjust spacing, thumbnail size, and focus color through the documented CSS variables. The screenshots show the plugin with the Osmium theme and sample content; your theme supplies its own typography and colors.

= Does it load JavaScript on the frontend? =

No. Public post lists use HTML and CSS. JavaScript runs only in the Block Editor and uses the packages supplied by WordPress. Active classic widgets enqueue the shared stylesheet before the page head; blocks and shortcodes load it when rendered.

= What happens when I deactivate the plugin? =

Widget and block output stop displaying. Shortcodes remain in your content and may appear as plain text until the plugin is reactivated. Your posts, featured images, and saved widget settings are not deleted.

= Is there a paid version or an account requirement? =

No. Beautiful Recent Posts Widget is free and open source. No account, license key, or external service is required.

== Screenshots ==

1. The Beautiful Recent Posts block with its live preview and controls for title, post count, category, ordering, layout, and thumbnail shape in the Osmium editor.
2. A responsive three-card reading list with featured images, publication dates, and excerpts. Typography and colors come from the Osmium theme.
3. The classic widget beside an article, with circular thumbnails, comment links, and an All stories button.
4. The reading list and stacked cards at a mobile viewport, with excerpts moving below thumbnails in narrow spaces.
5. A complete Osmium page with an editorial list, a story without a featured image, wide cards, and a text-only shortcode list.
6. The classic widget below the article column on mobile, with circular thumbnails and its All stories button.
7. Post lists inheriting a dark container's colors and a right-to-left layout. The RTL example uses English sample text.

== External Services ==

Beautiful Recent Posts Widget does not contact external services or collect telemetry. Post queries and rendering run on your WordPress installation. Editor controls, styles, and images are served by WordPress; the plugin does not load remote fonts or third-party scripts.

== Upgrade Notice ==

= 5.0.0 =
Requires WordPress 6.6 or later and PHP 7.4 or later. Adds the native block, shortcode, filters, layouts, and display controls. Existing widgets are retained; review the refreshed styling on staging.

== Changelog ==

The complete release history is available on the [Beautiful Recent Posts changelog](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress/releases).

= 5.0.0 =
* New teal editorial branding, SVG icon, and standard/retina directory banners.
* Added a native block, shortcode, list/card layouts, category and ordering controls.
* Added optional author, excerpt, thumbnail shape, metadata, and current-post exclusion.
* Preserved the original WordPress.org plugin filename, saved classic widget IDs and original settings.
* Migrates activation from the GitHub-only 4.x filename on single-site and multisite installations.
* Replaced unused data-retina markup with native responsive images.
* Bounded and sanitized settings; excluded private and password-protected content.
* Added PHP integration tests, JavaScript checks, CI, and reproducible ZIP packaging.
* Raised minimum requirements to WordPress 6.6 and PHP 7.4.
* Standardized the translation domain to beautiful-recent-posts-widget for directory language packs.

= 4.1 =
* Updated WordPress coding standards, escaping, and sanitization.
* Added conditional CSS loading and retina image sizes.

= 4.0 =
* Major code refactoring and WordPress 5.0+ compatibility.

= 1.1 =
* Minified CSS and performance improvements.

= 1.0 =
* Initial release.
