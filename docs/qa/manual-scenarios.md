# Seo & Social Manual QA Scenarios

Execute these scenarios against the development environment and record results in `docs/qa/reports/test-foundation-baseline.md`.

## General rules

- Start each scenario from a known state and record any reused test data.
- Use disposable content and images where deletion is involved.
- Do not classify an observation as a defect until it reproduces at least twice.
- Never include authentication data, cookies, nonces, or private site content in evidence.

## Scenarios

### QA-001 — Clean installation and activation

Risk: the runtime package cannot load on a clean WordPress site.

1. Start the development environment.
2. Confirm Seo & Social is installed and active.
3. Open WordPress Admin and the plugin menu.
4. Check the PHP debug log after activation.

Expected: activation succeeds, the menu is visible to an Administrator, and no fatal error or plugin-generated PHP warning appears.

### QA-002 — Administrator access and available tabs

Risk: the primary user cannot manage the plugin or sees an incomplete interface.

1. Sign in as Administrator.
2. Open Seo & Social.
3. Navigate Social, SEO, LLMs.txt, and Settings.
4. Open the How to use sections.

Expected: all four tabs and management actions are available and their content loads without errors.

### QA-003 — Role and filtered-capability boundaries

Risk: unauthorized users access global settings or approved users are blocked.

1. Check the menu and direct admin URL as Editor, Author, Contributor, and Subscriber.
2. Confirm Editors can still use configured SEO/FAQ boxes on content they may edit.
3. Grant a disposable custom role through the documented role/capability filters and retest access.

Expected: only explicitly allowed users enter the global plugin page; content capabilities remain governed by WordPress.

### QA-004 — Cross-tab settings preservation

Risk: saving one settings tab erases data belonging to another tab.

1. Save identifiable valid values in Social, SEO, and LLMs.txt.
2. Save Settings with all required feature toggles and post types.
3. Reopen every tab after each save.
4. Check the public/admin REST payload.

Expected: only the active tab changes; values from all other tabs remain unchanged.

### QA-005 — Invalid JSON and controlled feedback

Risk: malformed custom schema JSON is exposed or breaks the API.

1. Enter valid custom schema JSON and save.
2. Confirm it is present in the relevant response.
3. Replace it with malformed JSON and save again.
4. Observe the notice and REST output.

Expected: a controlled notice is shown, malformed JSON is removed from public output, and the endpoint remains valid JSON.

### QA-006 — Social links dynamic rows

Risk: incomplete, deleted, or reordered rows are exposed incorrectly.

1. Add two complete extra social links.
2. Add rows missing Key, Label, and URL in turn, attempting to save each one.
3. Confirm that the browser identifies each missing required field and does not submit the form.
4. Enter a schemeless URL and a non-HTTP(S) URL; confirm submission is blocked at the URL field.
5. Add a second row using the same Key; confirm submission is blocked at the duplicate Key field.
6. Give the rows unique keys and valid absolute HTTP(S) URLs, then remove one complete row and save.
7. Reload the page and inspect the settings endpoint.

Expected: incomplete and invalid-URL rows are blocked at the relevant field; each Key is unique; complete saved rows persist in the expected order; deleted or server-rejected rows are not exposed.

### QA-007 — Schema property types and sanitization

Risk: structured schema properties change type or expose unsafe values.

1. Add text, URL, list, and JSON properties.
2. Save valid values, reload the page, and inspect the REST response types.
3. Add one row without Property key and another without Value, attempting to save each one.
4. Confirm that the browser identifies each missing required field and does not submit the form.
5. Select URL and try a schemeless value, a malformed URL, and a non-HTTP(S) scheme; confirm each is blocked or removed with clear feedback.
6. Save valid absolute HTTP and HTTPS values, including a localhost URL, and confirm they persist unchanged.
7. Add two exact duplicate rows and attempt to save; confirm submission is blocked, the second row receives focus, and its Value field explains that it must be changed or removed.
8. Add rows with the same property key but a different type or value and confirm they remain in their original order.
9. Try malformed JSON and inspect the stored/public result.

Expected: incomplete rows are blocked with the missing required field identified; URL values require explicit absolute HTTP(S) syntax without silently adding a scheme; complete rows persist after reload; exact duplicates are blocked inline while server-side sanitization remains a fallback; meaningful same-key rows remain ordered; valid types remain structured; invalid values are controlled; and the response remains deterministic.

### QA-008 — LLMs data enabled, disabled, and dynamic rows

Risk: disabled or draft LLMs content is exposed anonymously.

1. Add site summary, recommended pages, ignored sections, and custom content.
2. Save, reload the page, and confirm that complete recommended-page and ignored-section rows persist.
3. Add a recommended page with URL and note but no page title, then attempt to save.
4. Enter a schemeless or non-HTTP(S) recommended-page URL and confirm submission is blocked at that URL.
5. Add another recommended page with the same URL and confirm submission is blocked at the duplicate URL.
6. Add an ignored section with a description but no section name, then attempt to save.
7. Save with LLMs disabled and request `/llms` anonymously.
8. Inspect the same data as Administrator.
9. Enable LLMs and request the endpoint again.

Expected: complete rows persist after reload; incomplete rows, invalid recommended-page URLs, and duplicate recommended-page URLs are blocked at the relevant field; anonymous disabled output contains only the disabled state; administrators can review stored drafts; enabled public output is complete and correctly rendered.

### QA-009 — Meta-box registration by post type

Risk: SEO/FAQ boxes appear on disabled post types or disappear from enabled ones.

1. Enable SEO and FAQ for Post and Page only.
2. Open a Post, Page, attachment, and available custom post type.
3. Change the enabled post types and repeat.

Expected: each meta box appears only on configured post types; Media remains absent unless intentionally selected.

### QA-010 — SEO overrides and global fallback resolution

Risk: a headless frontend receives the wrong final SEO data.

1. Save identifiable global SEO defaults.
2. Create content with all local SEO fields empty.
3. Inspect `seo_overrides` and `seo_resolved`.
4. Add local title, description, robots, schema type, canonical URL, and OG image.
5. Inspect the response again.

Expected: overrides contain only local data; resolved data uses local values first and global defaults otherwise; `source` identifies the origin correctly.

### QA-011 — FAQ rows, enabled state, and ordering

Risk: incomplete or disabled FAQ items appear publicly or sorting is unstable.

1. Add complete enabled FAQ rows with positions 30, 10, and 20.
2. Add a disabled complete row and an incomplete row.
3. Open the Code tab for an existing row and a newly added row; confirm the formatting buttons remain compact and readable instead of stretching across the row.
4. Save and reload the editor.
5. Inspect `faq_items` in REST.

Expected: stored rows remain editable; Code toolbar controls use the normal compact WordPress layout in existing and dynamic rows; public output contains only complete enabled rows sorted 10, 20, 30.

### QA-012 — FAQ HTML policy and stored XSS boundary

Risk: untrusted FAQ content becomes executable HTML.

1. Disable FAQ HTML, save formatted HTML and a harmless script-like payload, then inspect stored/public output.
2. Enable FAQ HTML and repeat with allowed formatting and disallowed markup.
3. Reopen the editor and inspect REST output.

Expected: plain mode removes markup; HTML mode preserves only WordPress-allowed markup; no script executes in admin or consumers.

### QA-013 — Autosave, revisions, and unauthorized metadata changes

Risk: background saves or insufficient capabilities overwrite SEO/FAQ data.

1. Save known SEO and FAQ values normally.
2. Trigger autosave and create a revision without intentionally changing plugin fields.
3. Attempt edits as roles with different `edit_post` rights.
4. Exercise Quick Edit/Bulk Edit if available for the post type.

Expected: existing metadata is not corrupted; unauthorized users cannot change it; ordinary WordPress editing flows remain stable.

### QA-014 — Public settings and LLMs endpoints enabled

Risk: public endpoints fail or omit required contracts.

1. Enable the public endpoint.
2. Request the configured settings and `/llms` routes while logged out.
3. Check status, content type, top-level keys, and absence of admin-only data.

Expected: both routes return controlled JSON according to feature state and expose no private administration data.

### QA-015 — Private endpoints and authenticated administration

Risk: disabling public access does not protect data or also blocks administrators.

1. Disable the public endpoint.
2. Request both plugin routes anonymously.
3. Repeat as Subscriber and Administrator.

Expected: anonymous/unauthorized requests receive the correct authorization error; Administrator access succeeds.

### QA-016 — Custom REST names

Risk: configuration changes break registration or leave old routes/fields active.

1. Change the REST namespace, settings path, SEO override field, and FAQ field.
2. Save and request the new routes/fields.
3. Request the old routes/fields.
4. Restore defaults.

Expected: new names work consistently, reserved `seo_resolved` remains unchanged, and obsolete names are no longer exposed by the plugin.

### QA-017 — Public rate limit and recovery

Risk: anonymous clients are not limited or remain blocked incorrectly.

1. Use a controlled low limit/window through a disposable test configuration or filter.
2. Send requests up to the limit from one client address.
3. Send one additional request.
4. Wait for the window to expire and retry.
5. Repeat from a distinct trusted client address only if proxy headers are explicitly configured.

Expected: allowed requests succeed, the excess request returns 429, and access recovers after the window; untrusted forwarded headers do not bypass the limit.

### QA-018 — Content REST fields and contract shape

Risk: registered fields differ across content types or return malformed structures.

1. Enable content REST fields for Post, Page, and a supported custom post type.
2. Request representative items with and without overrides/FAQ.
3. Validate field names, null/empty behavior, arrays, booleans, integers, and resolved source metadata.

Expected: every configured REST-enabled post type exposes a stable, correctly typed contract.

### QA-019 — OG image generation

Risk: optimized images have the wrong dimensions/format or replace originals.

1. Enable OG optimization.
2. Select a disposable JPEG/PNG as global and local OG image.
3. Save and trigger generation.
4. Inspect REST metadata and files.

Expected: a separate 1200x630 WebP is generated, REST prefers it, metadata is correct, and the original image remains unchanged.

### QA-020 — OG regeneration, deletion, and failures

Risk: maintenance actions delete unrelated files or fail silently.

1. Regenerate used OG images twice.
2. Delete generated WebP images using the plugin action.
3. Confirm original media remains.
4. Test a missing source file or unsupported/invalid attachment in disposable data.

Expected: repeated generation is controlled, only plugin-owned generated files are deleted, failures produce useful notices, and unrelated/original files remain.

### QA-021 — Headless frontend boundary

Risk: the plugin unexpectedly renders frontend SEO or FAQ markup.

1. Configure global and per-content SEO, schema, and FAQ data.
2. View the frontend HTML source for representative content.
3. Search for plugin-generated title/meta, Open Graph, JSON-LD, and FAQ markup.
4. Confirm data remains available through REST.

Expected: no plugin-generated frontend tags or FAQ UI appear; structured data is available only through the documented REST contracts.

### QA-022 — Manual Delete all plugin data

Risk: the danger-zone action misses plugin data or deletes unrelated content/original media.

1. Use a disposable environment with settings, SEO/FAQ post meta, and generated OG files.
2. Record unrelated posts, users, terms, and original media.
3. Run Delete all plugin data and confirm the warning.
4. Recheck the database-visible behavior, generated files, and unrelated content.

Expected: plugin settings/meta/generated files are removed; posts, users, terms, and original media remain.

### QA-023 — Direct uninstall preserves data

Risk: uninstall contradicts the documented preservation policy.

1. Use a disposable installation with representative saved plugin data.
2. Deactivate and uninstall the plugin without first using Delete all plugin data.
3. Reinstall and reactivate the plugin.

Expected: saved settings and post metadata remain available because `uninstall.php` intentionally performs no cleanup.

### QA-024 — Existing regression and localization smoke pass

Risk: the new QA environment differs from current CI assumptions or Romanian UI is unusable.

1. Run the existing four regression scripts in the development checkout.
2. Switch WordPress to Romanian and navigate/save representative plugin screens.
3. Confirm notices, labels, and dynamic-row controls remain understandable.

Expected: current regression scripts pass when executed later under the verification instructions; the translated admin workflow has no missing critical strings or broken controls.
