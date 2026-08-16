# Seo & Social Test Foundation Baseline

This report records the first execution of the manual QA foundation. It is historical evidence: do not rewrite original results after a fix. Add retest entries in the follow-up section.

## Execution metadata

| Field | Value |
| --- | --- |
| Plugin | Seo & Social |
| Plugin version | `0.1.0` |
| Branch | `qa-test-foundation` |
| Test date | 16-08-2026 |
| Tester | MCaius|
| Environment | Local Docker via `wp-env` |
| WordPress version | 7.0.4 |
| PHP version | 8.3.33 |
| Browser and version | Chrome Version 151.0.7922.138 |
| Operating system | MacOS |
| Site language | English |

## Entry checks

- [x] Docker is running.
- [x] Dependencies were installed from `package-lock.json`.
- [x] The development environment starts on port 8888.
- [x] Seo & Social is active.
- [x] WordPress debug logging is enabled and display is disabled.
- [x] Disposable users, content, CPT, and image fixtures are available.
- [x] No production or personal site data is used.

## Results

Allowed results: `Pass`, `Fail`, `Partial`, `Blocked`, `Not run`.

| ID | Scenario | Priority | Result | Evidence | Notes / issue |
| --- | --- | --- | --- | --- | --- |
| QA-001 | Clean installation and activation | P0 | Pass | — | — |
| QA-002 | Administrator access and available tabs | P0 | Pass | — | — |
| QA-003 | Role and filtered-capability boundaries | P0 | Pass | — | — |
| QA-004 | Cross-tab settings preservation | P0 | Pass | — | — |
| QA-005 | Invalid JSON and controlled feedback | P0 | Pass| — | — |
| QA-006 | Social links dynamic rows | P1 | Pass| — | — |
| QA-007 | Schema property types and sanitization | P0 | Pass | Manual settings reload and public REST inspection | Property types and sanitization passed. Two non-blocking observations were recorded as `F-002` and `F-003`. |
| QA-008 | LLMs data enabled, disabled, and dynamic rows | P0 | Pass | — | — |
| QA-009 | Meta-box registration by post type | P1 | Pass | — | — |
| QA-010 | SEO overrides and global fallback resolution | P0 | Pass | — | — |
| QA-011 | FAQ rows, enabled state, and ordering | P1 | Pass | Manual editor reload and `faq_items` REST inspection | Complete enabled rows were preserved and returned in position order `10`, `20`, `30`; the disabled and incomplete rows were excluded from the public REST value. A separate UI defect was observed while adding dynamic rows; it is tracked in [#1](https://github.com/MCaius/seo-and-social-wordpress-plugin/issues/1) and documented as `F-001`, but does not invalidate the QA-011 acceptance criteria. |
| QA-012 | FAQ HTML policy and stored XSS boundary | P0 | Pass | Manual editor reload and `faq_items` REST inspection | With FAQ HTML disabled, submitted markup was escaped or reduced to inert text. With FAQ HTML enabled, allowed formatting such as `<p>` and `<strong>` was preserved while the `<script>` element was removed; the payload remained non-executable text in REST. |
| QA-013 | Autosave, revisions, and unauthorized metadata changes | P0 | Not run | — | — |
| QA-014 | Public settings and LLMs endpoints enabled | P0 | Not run | — | — |
| QA-015 | Private endpoints and authenticated administration | P0 | Not run | — | — |
| QA-016 | Custom REST names | P0 | Not run | — | — |
| QA-017 | Public rate limit and recovery | P1 | Not run | — | — |
| QA-018 | Content REST fields and contract shape | P0 | Not run | — | — |
| QA-019 | OG image generation | P0 | Not run | — | — |
| QA-020 | OG regeneration, deletion, and failures | P0 | Not run | — | — |
| QA-021 | Headless frontend boundary | P0 | Not run | — | — |
| QA-022 | Manual Delete all plugin data | P0 | Not run | — | — |
| QA-023 | Direct uninstall preserves data | P1 | Not run | — | — |
| QA-024 | Existing regression and localization smoke pass | P1 | Not run | — | — |

## Summary

| Result | Count |
| --- | ---: |
| Pass | 12 |
| Fail | 0 |
| Partial | 0 |
| Blocked | 0 |
| Not run | 12 |

## Findings

Add findings only after execution. Use one entry per observation.

### Finding template

- Finding ID:
- Related scenarios:
- Classification: Bug / Improvement / Expected behavior / Test-environment problem
- Priority or severity:
- Reproduction frequency:
- Environment:
- Steps:
- Expected:
- Actual:
- Evidence:
- GitHub issue:

### F-001 — Dynamic FAQ rows reuse the same editor ID

- Finding ID: `F-001`
- Related scenarios: `QA-011`, `QA-012`
- Classification: Bug
- Priority or severity: Medium
- Reproduction frequency: Always, reproduced `2/2` times after independent disposable-fixture resets
- Environment: Seo & Social `0.1.0`; WordPress `7.0.4`; PHP `8.3.33`; Chrome `151.0.7922.138`; local Docker via `wp-env`; macOS
- Steps: Open a published Page, expand Meta Boxes and FAQ, then add two or more FAQ items.
- Expected: Every FAQ answer receives a unique DOM ID and an independent TinyMCE editor.
- Actual: Every dynamic answer uses `sas_faq_answer___index__`; only the first row initializes TinyMCE and later rows remain plain textareas.
- Evidence: Two manual browser reproductions, sanitized screenshots attached to the GitHub issue, and quarantined Playwright coverage in `tests/e2e/faq-meta-box.spec.mjs`.
- GitHub issue: [#1 — Dynamic FAQ rows reuse the same editor ID and only the first row initializes TinyMCE](https://github.com/MCaius/seo-and-social-wordpress-plugin/issues/1)
- Release status: Open release blocker. The FAQ E2E group remains marked `fixme` until the issue is fixed and manually retested.

### F-002 — Schemeless schema-property URLs are normalized automatically

- Finding ID: `F-002`
- Related scenarios: `QA-007`
- Classification: Improvement
- Priority or severity: Low
- Reproduction frequency: Observed during manual execution
- Environment: Local Docker via `wp-env`; `qa-admin-e2e` branch
- Steps: Add an extra schema property with type `url`, enter a value without a URL scheme, save the settings, and inspect the saved row and public REST response.
- Expected: The interface should make it clear whether a schemeless value will be rejected or normalized.
- Actual: The value is accepted and automatically normalized by adding `http://`.
- Evidence: Manual settings reload and `headless-seo/v1/site-settings` REST inspection.
- GitHub issue: Not opened. Consider future validation feedback or a preview of the normalized value.
- Release status: Non-blocking observation.

### F-003 — Exact duplicate schema-property rows are preserved

- Finding ID: `F-003`
- Related scenarios: `QA-007`
- Classification: Expected behavior / product decision
- Priority or severity: Low
- Reproduction frequency: Observed during manual execution
- Environment: Local Docker via `wp-env`; `qa-admin-e2e` branch
- Steps: Add two extra schema-property rows with the same key, type, and value, save the settings, and inspect the public REST response.
- Expected: The duplicate policy should be explicit. Repeated keys with different values may be valid, while completely identical rows may be unnecessary.
- Actual: Both identical structured rows are saved and exposed through the public REST response.
- Evidence: Manual settings reload and `headless-seo/v1/site-settings` REST inspection.
- GitHub issue: Not opened. Consider preventing only exact `key + type + value` duplicates while continuing to allow repeated keys with different values.
- Release status: Non-blocking observation.

## Exit assessment

- [ ] All 24 scenarios have an executed result.
- [ ] Every Fail has reproducible steps and evidence.
- [ ] P0 failures are resolved or explicitly accepted.
- [ ] Observations were not presented as confirmed bugs.
- [ ] Sensitive data was removed from all evidence.

Status: Baseline execution in progress (`12/24` scenarios executed).

## Follow-up retests

Do not edit the original result table to represent a later fix. Add retests here.

| Date | Scenario / issue | Branch or commit | Previous result | Retest result | Evidence and notes |
| --- | --- | --- | --- | --- | --- |
| 16-08-2026 | `QA-011` | `qa-admin-e2e` | Not run | Pass | Manual editor reload and REST inspection confirmed persistence, enabled-state filtering, exclusion of incomplete rows, and ordering by positions `10`, `20`, `30`. The related editor UI defect is tracked separately as [#1](https://github.com/MCaius/seo-and-social-wordpress-plugin/issues/1). |
| 16-08-2026 | `QA-012` | `qa-admin-e2e` | Not run | Pass | Manual editor reload and REST inspection confirmed the plain-text and allowed-HTML policies. Allowed formatting was preserved when enabled, `<script>` was stripped, and no executable payload was exposed through `faq_items`. |
| 16-08-2026 | `QA-007` | `qa-admin-e2e` | Not run | Pass | Manual settings reload and public REST inspection confirmed property type handling and sanitization. Non-blocking URL-normalization and duplicate-row observations are documented as `F-002` and `F-003`. |
| 16-08-2026 | [#1](https://github.com/MCaius/seo-and-social-wordpress-plugin/issues/1) | `qa-admin-e2e` | Open | Fail | The dynamic FAQ editor defect remains reproducible: rows reuse the same editor ID and only the first dynamic row initializes TinyMCE. Retest after the fix is required before release. |
