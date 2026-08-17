# Changelog

All notable changes to Seo & Social will be documented in this file.

The format is based on Keep a Changelog, and this project uses semantic versioning.

## [1.1.0] - 2026-08-17

### Added

- WordPress PHPUnit integration coverage for access control, privileged admin handlers, settings and metadata persistence, REST contracts, output escaping, LLMs data, OG image behavior, uninstall policy, and the headless frontend boundary.
- Playwright admin end-to-end coverage using plugin-owned `data-testid` selectors and disposable fixtures.
- Deterministic ZIP verification with manifest, checksum, path, file-type, and extraction checks.
- Clean packaged-plugin activation and version smoke tests.
- WordPress Plugin Check and a compatibility matrix covering WordPress 6.0, the latest WordPress release, PHP 8.0, and PHP 8.4.
- Repeatable manual QA scenarios, a completed 24-scenario baseline report, issue templates, and development/testing documentation.

### Fixed

- Dynamic FAQ rows now receive unique editor IDs and initialize independent TinyMCE instances.

### Changed

- CI verification now runs for pull requests targeting `main`, pushes to `main`, and manual dispatches, with superseded runs cancelled automatically.
- Release publishing reuses the complete verification workflow and publishes only the verified plugin package and manifest.
- Legacy standalone PHP regression scripts were replaced by the WordPress PHPUnit integration suite.

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
