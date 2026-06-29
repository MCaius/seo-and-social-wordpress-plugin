# Changelog

All notable changes to Seo & Social will be documented in this file.

The format is based on Keep a Changelog, and this project uses semantic versioning.

## [1.0.0] - 2026-06-29

### Added

- Initial public version of the Seo & Social WordPress plugin.
- Headless REST API endpoint for global social and SEO settings.
- Public LLMs.txt JSON endpoint with rendered text output for frontend-owned `/llms.txt`.
- Per-content SEO override fields for enabled post types.
- Per-content FAQ fields with collapsible admin UI and basic editor support.
- `seo_overrides`, `seo_resolved`, and `faq_items` REST fields.
- Global Social, SEO, LLMs.txt, and Settings admin tabs.
- Administrator-only global plugin admin pages by default.
- Editor access to per-content SEO/FAQ meta boxes on content they can edit.
- Optional public REST endpoint with lightweight unauthenticated rate limiting.
- Configurable REST namespace and field names.
- Default robots setting with empty/fallback behavior.
- Optional 1200x630 WebP OG image generation with original image fallback.
- Regenerate generated OG images admin action.
- Delete generated WebP OG images admin action.
- Manual delete-all-plugin-data admin action.
- Non-destructive `uninstall.php`.
- Romanian translation files.
- WordPress-style `readme.txt`.
- GitHub-oriented `README.md`, `SECURITY.md`, GPL license, CI workflow, release workflow, and ZIP builder.

### Security

- Added nonce and capability checks for settings, meta saves, and admin maintenance actions.
- Sanitized and escaped plugin settings, post meta, REST output, and request-derived values.
- Restricted generated WebP deletion to plugin-generated files inside the uploads directory.
- Disabled automatic data deletion on uninstall.

### Developer

- Added PHP syntax checks.
- Added WordPress Coding Standards configuration.
- Added regression tests for settings saves, REST permissions, access capability handling, and LLMs output.
- Added translation validation with `msgfmt`.
- Added deterministic plugin ZIP build script.