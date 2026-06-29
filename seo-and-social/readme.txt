=== Seo & Social ===
Contributors: MCaius
Tags: headless, seo, social, schema, rest-api, faq, llms
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Headless SEO, social, schema, FAQ, and LLMs.txt CMS fields exposed through WordPress REST API.

== Description ==

Seo & Social is a WordPress plugin for headless websites. It gives administrators a WordPress-native UI for global social links, SEO defaults, organization schema data, OG image optimization, and LLMs.txt source content. It also adds per-content SEO and FAQ meta boxes for enabled post types.

The plugin does not render frontend meta tags, Open Graph tags, JSON-LD, FAQ UI, sitemap files, or llms.txt files directly. Instead, it exposes structured REST API data that a frontend such as Next.js, Astro, Nuxt, or React can consume and render on the public domain.

Core ideas:

* WordPress owns CMS-managed SEO and social data.
* The frontend owns public HTML, metadata rendering, schema rendering, sitemap generation, and public /llms.txt serving.
* Per-content `seo_overrides` stay separate from final `seo_resolved` output.
* Original media files remain untouched when OG WebP optimization is enabled.

== Features ==

* Global social/contact fields.
* Global SEO defaults.
* Global organization/schema data.
* Default robots setting.
* Optional generated WebP OG image copies at 1200x630.
* Per-content SEO overrides.
* Per-content FAQ items.
* Public REST settings endpoint with lightweight unauthenticated rate limiting.
* LLMs.txt JSON endpoint with rendered text payload for frontend use.
* Admin-only global plugin settings by default.
* Manual delete-all-data action for administrators.
* Inert uninstall handler that does not delete data automatically.

== Installation ==

1. Upload the `seo-and-social` folder to `/wp-content/plugins/`.
2. Activate `Seo & Social` from the WordPress Plugins screen.
3. Open `Seo & Social` in the WordPress admin.
4. Configure global Social, SEO, LLMs.txt, and Settings fields.
5. Enable the desired post types for SEO and FAQ meta boxes.
6. Connect your frontend to the REST API endpoints.

== REST API ==

Default global settings endpoint:

`/wp-json/headless-seo/v1/site-settings`

Default LLMs.txt JSON endpoint:

`/wp-json/headless-seo/v1/llms`

Default per-content fields:

* `seo_overrides`
* `seo_resolved`
* `faq_items`

`seo_overrides` contains only local fields saved on a page, post, or custom post type item.

`seo_resolved` contains the final SEO payload after local overrides are merged with global plugin defaults.

== Frontend Usage ==

The frontend should usually:

1. Fetch global settings from `/wp-json/headless-seo/v1/site-settings`.
2. Fetch page/post/CPT content from the WordPress REST API.
3. Render metadata from `seo_resolved`.
4. Render FAQ UI and FAQPage JSON-LD from `faq_items`.
5. Render global organization schema from global SEO settings.
6. Generate sitemap files in the frontend using final frontend URLs.
7. Serve `/llms.txt` from the frontend using the `rendered_txt` value from `/wp-json/headless-seo/v1/llms`.

== Frequently Asked Questions ==

= Does this plugin render SEO tags in WordPress? =

No. This plugin is designed for headless projects. It exposes CMS-managed data through REST API; the frontend renders the public HTML and metadata.

= Does this plugin generate sitemap.xml? =

No. Sitemap generation should happen in the frontend because the frontend owns the final public URL structure.

= Does this plugin serve /llms.txt directly? =

No. It exposes JSON data and a `rendered_txt` string. The frontend should serve the public `/llms.txt` file from its own domain.

= Does uninstall delete plugin data? =

No. Uninstall is intentionally non-destructive. Administrators can use the manual `Delete all plugin data` action before uninstalling if they intentionally want to remove saved data.

= Can editors change global settings? =

No, not by default. Global plugin pages are administrator-only by default. Editors can still use per-content SEO and FAQ meta boxes when they can edit that content.

== Screenshots ==

1. <Screenshot placeholder: Seo & Social admin overview with How to use accordion and tabs>
2. <Screenshot placeholder: SEO defaults tab showing global metadata, default robots, and OG image fields>
3. <Screenshot placeholder: Per-content SEO meta box on a page or custom post type>
4. <Screenshot placeholder: FAQ meta box with collapsible FAQ rows and editor controls>
5. <Screenshot placeholder: LLMs.txt tab showing structured fields and rendered preview>
6. <Screenshot placeholder: Example JSON response from /wp-json/headless-seo/v1/site-settings>
7. <Screenshot placeholder: Example JSON response from /wp-json/headless-seo/v1/llms>

== Changelog ==

= 0.1.0 =
* Initial public portfolio release.
