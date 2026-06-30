# Seo & Social

Seo & Social is a headless WordPress plugin that turns WordPress into a small SEO, social, schema, FAQ, and LLMs.txt content source for modern frontends.

It is built for projects where WordPress manages editorial data, but the public website is rendered by a frontend such as Next.js, Astro, Nuxt, Remix, or a custom React app. The plugin exposes structured REST API data only. It does not render frontend meta tags, Open Graph tags, JSON-LD, FAQ UI, sitemap files, or `/llms.txt` directly.

## Project Purpose

This repository is meant to be both a usable WordPress plugin and a portfolio-quality example of a production-minded headless CMS integration.

The implementation focuses on:

- Clear separation between CMS data ownership and frontend rendering.
- A stable REST contract for SEO and social metadata.
- Safe admin workflows for global settings, per-content overrides, FAQ data, and LLMs.txt source content.
- Conservative security defaults for public endpoints, role access, data deletion, and generated media.
- A clean plugin ZIP build that excludes repository-only documentation and development files.


## Seo & Social Plugin Walkthrough
https://github.com/user-attachments/assets/0b1adc56-9d54-4366-b7a4-8fd6f281fb83


## Screenshots

### General Settings
<img width="2518" height="2892" alt="General-Settings" src="https://github.com/user-attachments/assets/2d6ebe96-bbba-4c38-9f8b-c9928c6debdd" />


### Social Settings

<img width="4738" height="2514" alt="General-Setting-Social" src="https://github.com/user-attachments/assets/1c31f2c7-bba7-4e61-b874-970da821c898" />


### Global SEO Settings

<img width="4702" height="2928" alt="General-Setting-SEO" src="https://github.com/user-attachments/assets/f64389dc-f27f-457d-a72d-972e87e8f708" />


### Page-Specific SEO & FAQ 

<img width="5936" height="2784" alt="Page-Specifc-SEO-FAQ" src="https://github.com/user-attachments/assets/a963bc5d-ddc8-40c9-a881-6e2fdff43d02" />





## What The Plugin Does

- Stores global social/contact links.
- Stores global SEO defaults.
- Stores organization/schema data.
- Stores a default robots value, empty by default.
- Adds optional 1200x630 WebP OG image generation while keeping original media untouched.
- Adds per-content SEO override meta boxes for enabled post types.
- Adds per-content FAQ meta boxes for enabled post types.
- Exposes global data through a configurable REST endpoint.
- Exposes per-content `seo_overrides`, `seo_resolved`, and `faq_items` fields.
- Exposes LLMs.txt source content as JSON, including a ready-to-serve `rendered_txt` string.
- Provides manual admin actions for regenerating OG images, deleting generated WebP images, and deleting all plugin data.
- Leaves saved data intact on uninstall by design.

## Architecture

The plugin is intentionally headless:

- WordPress stores content and settings.
- WordPress exposes structured REST data.
- The frontend decides how to render metadata, Open Graph tags, JSON-LD, FAQ UI, sitemap files, and `/llms.txt`.

Global settings are stored in one WordPress option array. Per-content SEO and FAQ values are stored as post meta. Generated OG WebP files are derived files in uploads and are tied back to the selected source attachment.

## Access Model

Administrators can access the global Seo & Social admin pages by default.

Editors do not see the global plugin menu by default. They can still use SEO and FAQ meta boxes on content they are already allowed to edit, when those post types are enabled in plugin settings.

Custom projects can extend global plugin access through trusted developer filters, but the public default is Administrator-only.

## REST API

Default global settings endpoint:

```text
/wp-json/headless-seo/v1/site-settings
```

Default LLMs.txt JSON endpoint:

```text
/wp-json/headless-seo/v1/llms
```

Default per-content REST fields:

```text
seo_overrides
seo_resolved
faq_items
```

`seo_overrides` contains only the values saved on the current page, post, or CPT item.

`seo_resolved` contains the final SEO payload after local overrides are merged over global defaults. Frontends should usually render metadata from `seo_resolved`.

`faq_items` contains enabled FAQ rows for that content item.

The global endpoint namespace/path and the `seo_overrides` / `faq_items` field names can be changed from Settings. `seo_resolved` is reserved and always keeps that name.

## Frontend Usage

A typical frontend integration:

1. Fetch global settings from `/wp-json/headless-seo/v1/site-settings`.
2. Fetch the page, post, or CPT item from the WordPress REST API.
3. Use `seo_resolved` to render title, description, canonical URL, robots, OG image, and schema-related page data.
4. Use `faq_items` to render FAQ UI and optional FAQPage JSON-LD.
5. Use global social and organization data for layout, footer links, contact blocks, and Organization JSON-LD.
6. Use `/wp-json/headless-seo/v1/llms` to build and serve the frontend-owned `/llms.txt`.
7. Generate sitemap files in the frontend, because the frontend owns the final public URL structure.

More frontend notes live in:

```text
how-to-use-on-FrontEnd-recomandation.md
```

## Local Development

Install dependencies only when needed:

```bash
composer install
```

Run PHP syntax checks:

```bash
npm run syntax:php
```

Run all local validation checks:

```bash
npm run validate
```

Run WordPress Coding Standards when Composer dependencies are installed:

```bash
composer lint:php
```

Build the upload ZIP:

```bash
npm run build:zip
```

The ZIP is created at:

```text
dist/seo-and-social.zip
```

The upload ZIP contains only the runtime `seo-and-social/` plugin files. Repository docs, scripts, generated manifests, local workflow files, `.git`, `vendor/`, and `dist/` are excluded.


## WordPress Installation

1. Build or download `seo-and-social.zip`.
2. In WordPress, go to `Plugins -> Add New Plugin -> Upload Plugin`.
3. Upload and activate `Seo & Social`.
4. Open `Seo & Social` in wp-admin as an Administrator.
5. Configure Settings first, then fill Social, SEO, and LLMs.txt fields.
6. Edit pages/posts/CPT items to add local SEO overrides or FAQ rows.

## Security Notes

- Global plugin pages are Administrator-only by default.
- Public settings output can be disabled from Settings.
- Public plugin endpoints use a lightweight unauthenticated rate limit.
- Proxy IP headers are ignored unless enabled through trusted developer filters.
- Settings and meta saves use WordPress nonces and capability checks.
- Custom JSON fields must validate before they are exposed in API output.
- Generated WebP deletion is restricted to plugin-generated files in the uploads directory.
- Uninstall is intentionally non-destructive; data deletion is a manual Administrator action.

See [SECURITY.md](SECURITY.md) for the full policy.

## Forking Or Adapting

Good places to customize:

- Plugin labels and text domain.
- Default REST namespace and endpoint path.
- Allowed access roles through filters.
- Frontend field mapping in your application.
- Schema property presets and validation rules.
- CI/release workflow details for your own publishing process.

Avoid changing the public REST shape casually once a frontend depends on it. If a breaking change is needed, prefer adding a new field or endpoint version first.

## License

Seo & Social is licensed under GPL-2.0-or-later. See [LICENSE](LICENSE).
