# Agent Notes

These notes are intended for AI coding agents or contributors working on a fork of this repository.

- The WordPress plugin slug is `seo-and-social`.
- The plugin display name is `Seo & Social`.
- Keep v1 source strings in English, wrapped with WordPress i18n helpers where practical.
- Use the literal text domain `'seo-and-social'` in WordPress i18n calls so WPCS can validate translations.
- Local-only workflow files are ignored in `.gitignore`, especially `TO-DO.md` and `scripts/install-local.mjs`.
- Keep the plugin headless: expose structured REST data, but do not render frontend SEO tags, Open Graph tags, JSON-LD, or FAQ UI.
- Use structured arrays for dynamic rows such as social links and schema properties.
- Release zip builder: `scripts/build-zip.mjs`.
- PHP coding standard config: `phpcs.xml.dist`.
- Commit `composer.lock` so PHPCS/WPCS tooling is reproducible across forks and CI.
- Use `npm run build:zip` to create `dist/seo-and-social.zip`.
- The upload zip should contain only the `seo-and-social/` plugin runtime files.
- Do not include repo docs, scripts, generated manifests, `.git`, `vendor/`, `dist/`, or local config in the WordPress upload zip.
- Run `npm run validate` before handoff.
- Run `composer lint:php` when PHPCS dependencies are installed.
- Run `msgfmt --check --verbose -o /tmp/seo-and-social-ro_RO.mo seo-and-social/languages/seo-and-social-ro_RO.po` after translation changes.
- CI workflow: `.github/workflows/ci.yml`.
- Release workflow: `.github/workflows/release.yml`.
- CI and release workflows should run PHP syntax checks, local PHP regression tests, PHPCS, translation checks, and the ZIP build before publishing artifacts.
