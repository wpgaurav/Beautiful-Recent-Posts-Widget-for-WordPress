=== Beautiful Recent Posts Widget ===
Contributors: gauravtiwari
Donate link: https://buymeacoffee.com/gauravtiwari
Tags: recent posts, widget, thumbnails, block, sidebar
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 5.0.0-beta.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Give your next great read a place to shine. Recent posts with thumbnails, list and card layouts, a native block, and a classic widget.

== Description ==

Beautiful Recent Posts makes it easy to help readers find another story. Add it to a sidebar, a page, or a block-theme template. Its typography and colors inherit from your theme.

* Native Beautiful Recent Posts block with a live preview.
* Classic widget that preserves existing widget settings.
* Shortcode for content and template integrations.
* Editorial list and responsive card layouts.
* Circle, rounded, or square list thumbnails.
* Category filtering and newest, recently updated, or alphabetical ordering.
* Display 1–20 posts; optionally exclude the current post.
* Optional date, author, comments, and 5–60 word excerpts.
* WordPress responsive images, lazy loading, and semantic dates.
* Optional button linking to a published page.
* Keyboard focus styles and support for right-to-left layouts.
* No frontend JavaScript, external fonts, tracking, or remote service requests.

This is a development beta. Test it on a staging site before upgrading a production site.

== Installation ==

1. Upload the plugin ZIP through Plugins → Add New → Upload Plugin.
2. Activate Beautiful Recent Posts Widget.
3. Insert the Beautiful Recent Posts block in the editor or Site Editor. For classic themes, use Appearance → Widgets.
4. Choose your layout, category, and display details.
5. Optionally regenerate existing thumbnails for the 85px and 170px square crops. New uploads generate these automatically.

== Frequently Asked Questions ==

= Will my existing widgets survive the update? =

Yes. The original widget ID, title, post count, button label, and destination are retained. Dates, comments, and circular images stay enabled by default. The styling is refreshed, so review any custom CSS on staging.

= Can I use it without a widget area? =

Yes. Insert the native block in a page, post, or Site Editor template. Or use [beautiful_recent_posts totalnews="4" layout="cards" show_excerpt="true"].

= How do I filter posts? =

Select a category in the widget or block. For shortcodes, use a category ID: [beautiful_recent_posts category="12" exclude_current="true"]. Category filtering includes child categories.

= Does it show private or password-protected posts? =

No. It displays published, non-password-protected posts only. The optional button also requires a public, non-password-protected page.

= Does it load JavaScript on my site? =

No. The editor uses JavaScript supplied by WordPress; the public post lists are server-rendered HTML and CSS. Active classic widgets enqueue a small shared stylesheet before the page head; blocks and shortcodes load it when rendered.

= What does the date mean when I sort by recently updated? =

Sorting uses the last-modified date. The displayed date remains the original publication date.

== Changelog ==

= 5.0.0-beta.1 =
* New teal editorial branding, SVG icon, and standard/retina directory banners.
* Added a native block, shortcode, list/card layouts, category and ordering controls.
* Added optional author, excerpt, thumbnail shape, metadata, and current-post exclusion.
* Preserved saved classic widget IDs and original settings.
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

== Upgrade Notice ==

= 5.0.0-beta.1 =
Development beta. Requires WordPress 6.6+ and PHP 7.4+. Existing widgets are retained; review the refreshed styling on staging.
