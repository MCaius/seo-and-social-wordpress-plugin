# Security Policy

## Supported Versions

Security updates are provided for the latest released version of Seo & Social.

| Version | Supported |
| ------- | --------- |
| Latest release | Yes |
| Older releases | No |

## Reporting a Vulnerability

If you discover a security vulnerability in Seo & Social, please do not open a public issue.

Report it privately using GitHub Security Advisories, or contact the maintainer directly.

Please include:

- A clear description of the vulnerability
- Steps to reproduce the issue
- The affected plugin version
- Any relevant screenshots, logs, or proof of concept
- Whether the issue requires administrator, editor, authenticated, or unauthenticated access

I will review valid reports as soon as possible and publish a fix before disclosing technical details publicly.

## Scope

This security policy covers the Seo & Social WordPress plugin code in this repository.

Issues in WordPress core, third-party plugins, hosting configuration, custom frontend applications, or external repositories are outside the direct scope unless they are caused by this plugin.

## Security Model

- The plugin is headless and does not render frontend SEO tags, Open Graph tags, JSON-LD, or FAQ UI.
- Public REST endpoints expose only intended public SEO, social, schema, and FAQ data.
- The global settings endpoint is public only when `Enable public REST endpoint` is enabled.
- Unauthenticated requests to public plugin endpoints have a lightweight rate limit.
- Rate limiting uses `REMOTE_ADDR` by default. Proxy IP headers are ignored unless trusted proxy headers are explicitly enabled through developer filters.
- If the public endpoint setting is disabled, the settings endpoint requires an authenticated administrator.
- Disabled LLMs.txt data does not expose draft LLMs content to public API consumers.
- Global plugin pages require Administrator access by default, and can be extended for custom roles/capabilities through trusted developer filters.
- Per-content meta boxes require the user to be able to edit the post being saved.
- Settings and meta saves use WordPress nonces.
- User input is sanitized before storage and escaped in admin output.
- Custom JSON fields are validated before being exposed through public API responses.
- Disabled modules should not expose their corresponding public REST fields.
- OG image optimization creates separate derived WebP files and does not modify original Media Library uploads.
- Optimized OG image files are generated only for administrator-selected media attachments referenced by the plugin.
- Optimized OG image files are removed when their source attachment is deleted or when all plugin data is deleted.
- Optimized OG image deletion is restricted to generated WebP files inside the WordPress uploads directory that match the plugin filename pattern.
