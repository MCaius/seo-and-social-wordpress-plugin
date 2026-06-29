# Seo & Social Frontend Usage Recommendations

This file is for developers using the Seo & Social WordPress plugin from a headless frontend.

The WordPress plugin is the CMS and API source. The frontend should render the final public HTML, metadata, schema, FAQ UI, sitemap, and `llms.txt` files from the API data.

## Main API Endpoints

Default global settings endpoint:

```text
/wp-json/headless-seo/v1/site-settings
```

Default LLMs.txt endpoint:

```text
/wp-json/headless-seo/v1/llms
```

Default WordPress content endpoints still come from WordPress core, for example:

```text
/wp-json/wp/v2/pages/PAGE_ID
/wp-json/wp/v2/posts/POST_ID
/wp-json/wp/v2/YOUR_CPT_REST_BASE/POST_ID
```

The plugin adds REST fields to enabled post types:

```text
seo_overrides
seo_resolved
faq_items
```

`seo_overrides` and `faq_items` can be renamed in plugin settings. `seo_resolved` is reserved and always uses that field name.

## Recommended SEO Flow

Use `seo_resolved` for frontend metadata.

`seo_overrides` means only values saved directly on that page, post, or CPT item. Empty override fields mean "use the global fallback".

`seo_resolved` is the final merged SEO payload:

1. Local page/post/CPT override.
2. Global Seo & Social default.
3. Empty value when neither exists.

Typical frontend logic:

```ts
const seo = page.seo_resolved;

return {
  title: seo.seo_title || fallbackTitle,
  description: seo.seo_description || fallbackDescription,
  canonical: seo.canonical_url || currentUrl,
  robots: seo.robots || undefined,
  openGraphImage: seo.og_image_url || undefined,
};
```

If `seo_resolved.robots` is empty, do not render a robots meta tag. Normal search engine behavior without a robots meta tag is effectively `index,follow`.

## Global Site Settings

Use `/wp-json/headless-seo/v1/site-settings` for global data:

- `social`: email, phone/WhatsApp, social URLs, extra social links.
- `seo`: site name, global metadata defaults, organization fields, schema data, default OG image, default robots.

The frontend can use this endpoint for:

- site-wide footer/header contact links;
- global structured data;
- metadata fallbacks;
- organization JSON-LD;
- shared SEO defaults.

## Per-Content SEO Fields

`seo_overrides` includes:

- `seo_title`
- `seo_description`
- `og_image_id`
- `og_image_url`
- `og_image_original_url`
- `og_image_optimized`
- `canonical_url`
- `robots`
- `schema_type`
- `custom_schema_json`

Use it when you need to inspect what is saved locally on that content item.

`seo_resolved` includes the final values the frontend should usually render:

- `seo_title`
- `seo_description`
- `og_image_id`
- `og_image_url`
- `og_image_original_url`
- `og_image_optimized`
- `canonical_url`
- `robots`
- `schema_type`
- `custom_schema_json`
- `source`

`source` explains whether selected fields came from `override` or `global`.

## OG Image Handling

Prefer:

```text
seo_resolved.og_image_url
```

This will point to the optimized WebP image when available, or the original image fallback when optimization is unavailable or deleted.

Use:

```text
seo_resolved.og_image_original_url
```

when you need the original media URL.

Use:

```text
seo_resolved.og_image_optimized
```

when you need metadata about the generated WebP file:

- `url`
- `width`
- `height`
- `mime`

Frontend recommendation:

```ts
const imageUrl = seo.og_image_url;
const imageWidth = seo.og_image_optimized?.width ?? 1200;
const imageHeight = seo.og_image_optimized?.height ?? 630;
```

## FAQ Items

`faq_items` is exposed on enabled post types.

Each item contains:

- `question`
- `answer`
- `position`

Only enabled FAQ items with question and answer are exposed.

The frontend can use this for:

- visible FAQ accordions;
- FAQPage JSON-LD;
- search/help UI.

If FAQ answers allow basic HTML in plugin settings, render carefully with your framework's safe HTML mechanism and only from trusted WordPress editors.

## Schema Recommendations

Global schema settings live under:

```text
siteSettings.seo
```

Per-content schema override lives under:

```text
page.seo_resolved
```

Recommended frontend approach:

- render organization/local business schema from global settings;
- render page-level schema from `seo_resolved.schema_type`;
- parse `custom_schema_json` only if it is present;
- use FAQ items to build FAQPage schema only when the page has public FAQ items.

Invalid JSON is removed by the plugin before public output, so the frontend should not receive broken custom schema JSON from the API.

## LLMs.txt

The plugin does not serve the public `/llms.txt` file directly. The frontend should serve it from its own public domain.

Fetch:

```text
/wp-json/headless-seo/v1/llms
```

The endpoint returns:

- `enabled`
- `site_summary`
- `recommended_pages`
- `ignored_sections`
- `custom_content`
- `rendered_txt`

Recommended frontend behavior:

```ts
const llms = await fetchWordPressJson("/wp-json/headless-seo/v1/llms");

if (!llms.enabled) {
  return notFound();
}

return new Response(llms.rendered_txt, {
  headers: {
    "content-type": "text/plain; charset=utf-8",
  },
});
```

Use `rendered_txt` as the source of truth for the public text response. Use the structured fields if you want custom formatting or validation.

## Sitemap

Generate sitemap files in the frontend, not in this plugin.

Reason: the frontend owns the final public URLs, route structure, canonical policy, locales, and static/dynamic paths.

The plugin can help with sitemap decisions through:

- content status from WordPress;
- canonical URL override;
- `seo_resolved.robots`;
- modified dates from WordPress core REST fields.

Recommended rule:

- include public frontend routes;
- skip content with `seo_resolved.robots` containing `noindex`;
- use frontend URLs, not WordPress admin/API URLs.

## Permissions And Public Access

When `Enable public REST endpoint` is enabled, public frontend builds can fetch:

```text
/wp-json/headless-seo/v1/site-settings
/wp-json/headless-seo/v1/llms
```

The plugin applies a lightweight unauthenticated rate limit.

When disabled, these endpoints require an authenticated administrator.

## Practical Frontend Contract

For most frontends:

1. Fetch global settings once per build/request cycle.
2. Fetch the content item.
3. Render metadata from `content.seo_resolved`.
4. Render FAQ UI/schema from `content.faq_items`.
5. Render social/contact UI from `siteSettings.social`.
6. Render organization schema from `siteSettings.seo`.
7. Serve `/llms.txt` from the LLMs JSON endpoint's `rendered_txt`.
8. Generate sitemap in the frontend using final frontend URLs.

