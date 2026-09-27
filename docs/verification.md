# Verification — 5.0.0-beta.1

Observed locally on 27 September 2026. This is a development beta; no remote release or production deployment was performed.

## Executed checks

| Check | Result |
| --- | --- |
| WordPress 7.1.2 / PHP 8.5.10 | 36 integration assertions passed |
| WordPress 7.1.2 / PHP 8.4.25 (Studio) | Same 36 assertions passed |
| Clean installation from ZIP, WordPress 7.1.2 / PHP 8.5.10 | Activated successfully; same 36 assertions passed |
| Node 24.21.0 and Node 26.10.0 | All four editor behavior tests passed |
| Installed Node 26.8.2 | Syntax check and four tests passed |
| WordPress PHP Coding Standards | Runtime PHP files passed with no findings |
| Plugin Check 2.1.0 on packaged runtime | No errors; one query performance warning, below |
| SVG validation | Icon and banner passed strict validation |
| Browser rendering | Osmium 0.4.2, desktop and 390px phone viewport; no horizontal overflow |
| Editor | Native API v3 block inserted in saved content; inspector selection, list/card preview, title changes, save and reload verified in Chrome |
| Classic widget | Original four-setting instance rendered inside an Osmium page via a local QA shortcode adapter |
| Images | Native srcset/sizes preserved, all demo images loaded; missing-image story remains readable |
| Theme inheritance | Dark container and RTL flow visually checked; narrow excerpts expand below thumbnails |

The integration suite covers settings migration/defaults, bounds and sanitization, checkbox saves, category filters, current-post exclusion, password/private/draft exclusion, responsive image attribute preservation, quote-format thumbnails, global post preservation, recursive excerpts, metadata toggles, empty results, safe button destinations, shortcode booleans, block registration and matching PHP/block defaults.

## Remaining boundaries

- The initial local run did not execute PHP 7.4/8.2/8.3 or minimum WordPress 6.6. The subsequent GitHub automation run passed the full configured matrix; see below.
- Plugin Check flags `post__not_in` as a performance advisory. It contains only one current-post ID, is opt-in, and each query is capped at 20 published posts with pagination totals disabled. There is no unbounded exclusion list.
- The installed WP-CLI 2.12.0 dependency `react/promise` emits a PHP 8.5 case-semicolon deprecation before plugin execution. No plugin-origin warning/deprecation was raised by the integration handler.
- WordPress's editor logged a `global-styles-css-custom-properties-inline-css` iframe warning. No plugin JavaScript errors were observed. The in-app browser left the blob iframe blank; Chrome rendered it and was used for the actual editor checks.
- Screenshot fixtures are synthetic, on a local noindex site. RTL captures demonstrate directionality with English sample content, not a translated locale audit. Dark captures demonstrate inherited container colors, not a new theme switcher.
- Source symlink QA and installed-package QA are separate sites. Neither is evidence of a public release.

## Local outputs

- Main QA site: `/Users/gauravtiwari/Studio/beautiful-recent-posts-qa`, port 8932. Kept available for review.
- Clean ZIP test site: `/Users/gauravtiwari/Studio/beautiful-recent-posts-package-qa`, port 8933. Can be restarted in Studio.
- Screenshot catalog: [screenshots/README.md](screenshots/README.md).
- Build: `python3 scripts/package.py`. Fixed ZIP timestamps and file permissions make identical source bytes produce the same archive.
- Directory artwork sizes follow the [WordPress plugin assets requirements](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).
- Current-version references: [WordPress](https://wordpress.org/download/), [PHP releases](https://www.php.net/releases/), [Node releases](https://nodejs.org/en/about/previous-releases).

## GitHub automation follow-up

The [release dry run](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress/actions/runs/36320481308) passed Node 24/26 and all six WordPress jobs: PHP 7.4/8.2/8.3/8.4/8.5 on latest WordPress, plus PHP 7.4 on WordPress 6.6. Each WordPress job installed the packaged ZIP before running the 36 integration assertions. The 18 Python release-tool tests and four JavaScript behavior tests also passed. Actionlint passed locally.

The release bundle was downloaded and its checksum, runtime files and directory artwork independently verified against the source commit. Both publishing jobs were skipped; no tag, release or SVN commit was created. See [the release guide](releasing.md).
