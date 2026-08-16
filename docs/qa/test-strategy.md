# Seo & Social QA Test Strategy

## Purpose

This strategy defines the first evidence-producing QA pass for Seo & Social. The plugin is a headless WordPress data provider: it stores and exposes SEO, social, schema, FAQ, OG-image, and LLMs data, but it must not render frontend SEO tags, Open Graph tags, JSON-LD, or FAQ UI.

The first milestone establishes reproducible environments, risk-based manual coverage, and an immutable baseline before expanding automated coverage.

## Quality objectives

- Protect the REST contracts consumed by headless frontends.
- Prevent unauthorized settings or metadata changes.
- Prevent private or disabled data from becoming public.
- Preserve settings when individual tabs are saved.
- Sanitize stored input and escape administrative output.
- Keep per-content overrides and global fallbacks consistent.
- Protect original Media Library files while managing generated OG images.
- Preserve the plugin's headless boundary.
- Produce reviewable evidence for defects, improvements, retests, and releases.

## Scope

### In scope

- Plugin installation, activation, access, settings, and destructive actions.
- Social, global SEO, schema, and LLMs settings.
- SEO and FAQ meta boxes on configured post types.
- Public and authenticated REST behavior.
- Custom REST namespace, route, and field names.
- Rate limiting for unauthenticated public endpoints.
- OG-image generation and cleanup behavior.
- Manual data deletion and uninstall preservation.
- Romanian admin translations where visible.
- Verification that no frontend tags or FAQ markup are rendered.

### Out of scope for this milestone

- PHPUnit integration implementation.
- Playwright implementation.
- WordPress/PHP compatibility matrix execution.
- Plugin Check and packaged-ZIP activation gates.
- Performance/load testing beyond lightweight rate-limit behavior.
- Testing a specific headless frontend implementation.

These areas are planned in later QA milestones.

## Risk model

| Risk | Impact | Likelihood | Priority | Primary coverage |
| --- | --- | --- | --- | --- |
| Disabled/private data is exposed publicly | High | Medium | P0 | QA-008, QA-014, QA-015 |
| Unauthorized user changes global settings | High | Medium | P0 | QA-002, QA-003, QA-013 |
| Saving one tab erases another tab | High | Medium | P0 | QA-004 |
| REST contract changes or custom names break consumers | High | Medium | P0 | QA-010, QA-016, QA-018 |
| Unsafe HTML or JSON reaches storage/output | High | Medium | P0 | QA-005, QA-007, QA-012 |
| Plugin renders frontend tags despite being headless | High | Low | P0 | QA-021 |
| SEO/FAQ data is saved during autosave or without capability | High | Medium | P0 | QA-013 |
| Generated OG cleanup deletes original/unrelated files | High | Low | P0 | QA-019, QA-020, QA-022 |
| Rate limiting blocks or identifies clients incorrectly | Medium | Medium | P1 | QA-017 |
| Uninstall unexpectedly destroys saved data | High | Low | P1 | QA-023 |
| Dynamic rows are lost, reordered, or exposed incomplete | Medium | Medium | P1 | QA-006, QA-007, QA-008, QA-011 |
| Translation or admin feedback is misleading | Medium | Medium | P2 | All scenarios where notices are observed |

## Test roles

- Administrator: full plugin access, settings management, maintenance, and destructive actions.
- Editor: content editing and plugin meta boxes where the post type is enabled; no global settings by default.
- Author: own-content edit boundary.
- Contributor: restricted content boundary.
- Subscriber: no plugin administration or content-edit capabilities.
- Custom role/capability: access granted only through documented filters.
- Anonymous REST client: public endpoint and rate-limit behavior.

## Environments

| Environment | Configuration | Port | Purpose |
| --- | --- | ---: | --- |
| Development | `.wp-env.json` | 8888 | Manual baseline and exploratory testing |
| PHPUnit | `.wp-env.phpunit.json` | 8892 | Future WordPress integration tests |
| E2E | `.wp-env.e2e.json` | 8893 | Future Playwright journeys |
| Packaged plugin | `.wp-env.package.json` | 8890 | Future clean ZIP activation tests |

All environments use Docker through `@wordpress/env`. Local credentials, overrides, generated reports, private keys, and disposable evidence must remain ignored by Git.

## Entry criteria

- Docker is running.
- Node.js and npm are available.
- Dependencies were installed from `package-lock.json`.
- The development WordPress environment starts successfully.
- Seo & Social is active.
- The environment contains representative posts, pages, a custom post type where needed, and suitable image attachments.
- The tester records the exact environment in the baseline report.

## Exit criteria

- Every scenario is marked Pass, Fail, Partial, or Blocked.
- Every Fail contains reproducible steps, actual result, expected result, and evidence.
- P0 failures are resolved or explicitly accepted before release work.
- No issue is created for an observation that cannot be reproduced.
- Baseline results are committed without being rewritten after later fixes.
- Retests are recorded in a separate follow-up section.

## Evidence requirements

Use the smallest evidence set that proves the result:

- REST URL and sanitized response excerpt.
- Screenshot of relevant UI state or notice.
- WordPress role and content identifier.
- Before/after option or post-meta observation when required.
- Generated filename and confirmation that the original remains intact.
- Browser console/network error only when relevant.
- Exact reproduction frequency for defects.

Do not include passwords, cookies, nonces, personal information, filesystem secrets, or unrelated site data.

## Result classification

- Pass: expected result fully observed.
- Fail: expected behavior is contradicted by a reproducible result.
- Partial: only part of the expected behavior could be verified.
- Blocked: a documented environment or dependency prevents execution.
- Observation: useful behavior or UX note that does not contradict a requirement.

Bug, improvement, expected behavior, and test-environment problems must be distinguished before opening an issue.

## Baseline preservation

The baseline report is historical evidence. After a fix, do not change an original Fail to Pass and do not delete the original observation. Add a retest row containing the issue, branch/commit, new result, date, and evidence.
