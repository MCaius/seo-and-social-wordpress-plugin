# Development and Testing

This guide describes the local WordPress environments, automated test suites,
manual QA records, package checks, and release gates used by Seo & Social.

## Prerequisites

- Docker Desktop or another Docker-compatible runtime.
- Node.js 20 or newer and npm.
- PHP 8.0 or newer and Composer.
- Gettext for translation validation.
- Chromium for Playwright.

Install the project dependencies and browser:

```bash
composer install
npm ci
npx playwright install chromium
```

## Local WordPress environments

Each test purpose has an isolated `wp-env` configuration and port.

| Purpose | Configuration | URL |
| --- | --- | --- |
| Manual development | `.wp-env.json` | `http://localhost:8888` |
| PHPUnit integration | `.wp-env.phpunit.json` | `http://localhost:8892` |
| Playwright admin E2E | `.wp-env.e2e.json` | `http://localhost:8893` |
| Packaged ZIP smoke test | `.wp-env.package.json` | `http://localhost:8890` |
| Compatibility smoke test | `.wp-env.compat.json` | `http://localhost:8894` |

The configurations enable `WP_DEBUG` and `WP_DEBUG_LOG` and disable
`WP_DEBUG_DISPLAY`.

Start and stop the manual development environment:

```bash
npm run env:start -- --update
npm run env:stop
```

Destroy an environment when a clean database and new containers are required:

```bash
npm run env:destroy
```

Never use production or personal site data in these environments. Keep local
credentials and sensitive evidence outside Git.

## Useful local WordPress commands

Install the locked Node dependencies and start the manual development
environment:

```bash
npm ci
npm run env:start
```

Inspect the plugin, WordPress, and PHP versions running inside `wp-env`:

```bash
npx wp-env --config=.wp-env.json run cli wp plugin status seo-and-social
npx wp-env --config=.wp-env.json run cli wp core version
npx wp-env --config=.wp-env.json run cli php -v
```

### Quickly create test users

These fixed credentials are only for the disposable local `wp-env`
environment. Never reuse them on a public, shared, staging, or production site.

```bash
npx wp-env --config=.wp-env.json run cli wp user create qa-editor qa-editor@example.test --role=editor --user_pass='QaTest-2026!'
npx wp-env --config=.wp-env.json run cli wp user create qa-author qa-author@example.test --role=author --user_pass='QaTest-2026!'
npx wp-env --config=.wp-env.json run cli wp user create qa-contributor qa-contributor@example.test --role=contributor --user_pass='QaTest-2026!'
npx wp-env --config=.wp-env.json run cli wp user create qa-subscriber qa-subscriber@example.test --role=subscriber --user_pass='QaTest-2026!'
```

List the available users and their roles:

```bash
npx wp-env --config=.wp-env.json run cli wp user list --fields=ID,user_login,user_email,roles
```

## Static checks and dependency audits

```bash
npm audit --audit-level=high
composer audit --locked
npm run syntax:php
composer lint:php
msgfmt --check --verbose -o /tmp/seo-and-social-ro_RO.mo seo-and-social/languages/seo-and-social-ro_RO.po
```

## PHPUnit integration suite

The PHPUnit suite runs against an isolated WordPress installation. It covers
permissions, privileged handlers, settings and metadata persistence, REST
contracts, output escaping, LLMs data, OG image behavior, uninstall policy, and
the headless frontend boundary.

```bash
npm run env:phpunit:start -- --update
npm run test:php
npm run env:phpunit:stop
```

Stop the environment after a failed run as well. Use
`npm run env:phpunit:stop` before retrying when the containers are still active.

## Playwright admin E2E suite

The Playwright suite uses Chromium, disposable fixtures, and `data-testid`
selectors owned by the plugin. The suite runs with four local workers and two CI
workers; state-changing groups can remain serial inside their own files.

Start the environment, create or reset the fixtures, run the suite, and stop it:

```bash
npm run env:e2e:start -- --update
npm run e2e:fixtures
npm run test:e2e
npm run env:e2e:stop
```

Run with a visible browser or open the most recent HTML report:

```bash
npm run test:e2e:headed
npm run test:e2e:report
```

Run only the selector-policy check:

```bash
npm run test:e2e:selectors
```

Generated `test-results/`, `playwright-report/`, authentication state, traces,
screenshots, and videos must remain outside Git.

The same complete suite runs as a separate CI job. Failure reports, traces,
screenshots, and videos are retained as short-lived workflow artifacts only when
the CI run fails.

## Manual QA

Use these documents together:

- `docs/qa/test-strategy.md` defines scope, risks, evidence, and result states.
- `docs/qa/manual-scenarios.md` contains the repeatable manual scenarios.
- `docs/qa/reports/test-foundation-baseline.md` records execution results,
  findings, linked issues, and follow-up retests.

Use only `Pass`, `Fail`, `Partial`, `Blocked`, or `Not run`. Record a separate
finding when a scenario passes but exposes a non-blocking observation. Remove
sensitive data from screenshots and logs before attaching evidence.

## Packaged-plugin verification

Build the release archive and verify its paths, runtime-only file list,
manifest, size, checksum, and extractability:

```bash
npm run build:zip
npm run verify:zip
```

Start the isolated environment from the extracted package and confirm the
plugin is active with the expected version:

```bash
npm run env:package:destroy
npm run env:package:start -- --update
npm run test:package
npm run env:package:stop
```

Install WordPress Plugin Check in that environment and inspect the packaged
plugin:

```bash
npm run env:package:start -- --update
npm run plugin-check:install
npm run plugin-check
npm run env:package:stop
```

## Compatibility smoke test

The default local compatibility configuration uses PHP 8.0. CI also checks the
package against WordPress 6.0 with PHP 8.0 and the latest WordPress release with
PHP 8.0 and PHP 8.4.

Build and verify the ZIP before starting this environment:

```bash
npm run build:zip
npm run verify:zip
npm run env:compat:start -- --update
npm run test:compat
npm run env:compat:stop
```

## CI and release gates

Pull requests targeting `main`, pushes to `main`, and manual CI dispatches call
`.github/workflows/verify.yml`. Superseded runs for the same pull request or
branch are cancelled automatically. The reusable workflow audits locked
dependencies, checks PHP syntax and coding standards, runs PHPUnit, validates
the Romanian translation, builds and verifies the ZIP, runs WordPress Plugin
Check, performs a clean packaged-plugin smoke test, and runs the compatibility
matrix. A separate job runs the complete Playwright admin E2E suite.

The release workflow runs the same verification before publishing artifacts.
Only tags matching `v*` can publish a GitHub release. Pushing a normal branch,
opening a pull request, or merging into `main` does not release the plugin.

Playwright is both a local and CI gate for changes that affect the WordPress
administration interface.

## Recommended pre-push sequence

```bash
npm audit --audit-level=high
composer audit --locked
npm run syntax:php
composer lint:php
msgfmt --check --verbose -o /tmp/seo-and-social-ro_RO.mo seo-and-social/languages/seo-and-social-ro_RO.po
npm run env:phpunit:start -- --update
npm run test:php
npm run env:phpunit:stop
npm run env:e2e:start -- --update
npm run e2e:fixtures
npm run test:e2e
npm run env:e2e:stop
npm run build:zip
npm run verify:zip
npm run env:package:start -- --update
npm run test:package
npm run plugin-check:install
npm run plugin-check
npm run env:package:stop
```

Before release, also complete all manual P0 and P1 scenarios, resolve or accept
every release blocker explicitly, and run the compatibility smoke test.

## Release preparation checklist

1. Create `release/<version>` from an up-to-date, clean `main` branch.
2. Align the version in the plugin header, `readme.txt`, `package.json`,
   `package-lock.json`, package/compatibility smoke checks, and translation
   metadata.
3. Add the release notes to `CHANGELOG.md` and the WordPress changelog in
   `seo-and-social/readme.txt`.
4. Confirm the manual QA report is complete and that all blocking findings are
   resolved. Link non-blocking follow-up issues without presenting them as
   release blockers.
5. Run the complete pre-push sequence and compatibility smoke test.
6. Open one release pull request targeting `main` and wait for all required CI
   jobs to pass.
7. Merge the release pull request, then create the matching `v<version>` tag on
   the resulting `main` commit.
8. Push the tag and verify that the GitHub release contains the ZIP and manifest
   produced by the successful release workflow.
