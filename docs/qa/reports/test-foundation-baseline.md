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
| QA-007 | Schema property types and sanitization | P0 | Not run | — | — |
| QA-008 | LLMs data enabled, disabled, and dynamic rows | P0 | Not run | — | — |
| QA-009 | Meta-box registration by post type | P1 | Not run | — | — |
| QA-010 | SEO overrides and global fallback resolution | P0 | Not run | — | — |
| QA-011 | FAQ rows, enabled state, and ordering | P1 | Not run | — | — |
| QA-012 | FAQ HTML policy and stored XSS boundary | P0 | Not run | — | — |
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
| Pass | 0 |
| Fail | 0 |
| Partial | 0 |
| Blocked | 0 |
| Not run | 24 |

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

## Exit assessment

- [ ] All 24 scenarios have an executed result.
- [ ] Every Fail has reproducible steps and evidence.
- [ ] P0 failures are resolved or explicitly accepted.
- [ ] Observations were not presented as confirmed bugs.
- [ ] Sensitive data was removed from all evidence.

Status: Baseline not executed.

## Follow-up retests

Do not edit the original result table to represent a later fix. Add retests here.

| Date | Scenario / issue | Branch or commit | Previous result | Retest result | Evidence and notes |
| --- | --- | --- | --- | --- | --- |
| — | — | — | — | — | — |
