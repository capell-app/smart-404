# Smart 404

<!-- prettier-ignore-start -->

## What This Plugin Adds

Smart 404 is a premium Search and SEO extension that adds deterministic suggestions to frontend and static 404 documents while keeping the original HTTP 404 response.

- Status: Beta

Evidence: [`capell.json`](capell.json), [`src/Actions/ResolveSmart404SuggestionsAction.php`](src/Actions/ResolveSmart404SuggestionsAction.php), [`resources/views/widget.blade.php`](resources/views/widget.blade.php).

## Why It Matters

**For developers:** The resolver ranks similar public URLs, then the deepest indexed ancestor and its direct children.

**For teams:** The feature does not redirect, track visitors, call an AI service, or expose authoring data.

Evidence: [`src/Support/RenderHooks/RegisterSmart404Hook.php`](src/Support/RenderHooks/RegisterSmart404Hook.php), [`routes/web.php`](routes/web.php).

## Screens And Workflow

Authentic desktop, mobile, and settings captures are deferred until a clean host application is available. See [`docs/screenshots.json`](docs/screenshots.json).

## Technical Shape

- Provider: `Capell\Smart404\Providers\Smart404ServiceProvider`.
- Settings: enabled and maximum suggestions, with config defaults for threshold, endpoint, timeout, and rate limit.
- Public route: `GET /smart-404/suggestions?path=...` with current-origin, site, language, and indexability filtering.
- External CSS and JavaScript assets use DOM creation and `textContent`; timeout or malformed JSON hides the shell.

## Data Model

The resolver returns immutable `Smart404SuggestionData` objects with translated titles and relative URLs. It consumes the shared Foundation registry and creates no Smart 404 content tables.

## Install Impact

- Required packages: Admin, Core, Discovery Foundation, and Frontend.
- Settings migration: `database/settings/2026_08_08_000001_create_smart_404_settings.php`.
- Frontend impact: an AfterContent hook on error responses only.
- Static errors: installation regenerates enabled site error documents when a static error store is configured.

## Common Pitfalls

- The endpoint returns 404 when disabled, 422 for malformed paths, 429 when throttled, and 200 with an empty array when no suggestions qualify.
- Candidate URLs must be relative to the current origin and must not be noindex or from another site or language.
- Never change the 404 response to a redirect or query the database from the public Blade view.

## Troubleshooting

Check the Smart 404 health result for the AfterContent hook and the three public routes. If static hydration is empty, inspect the endpoint response and browser timeout rather than adding inline scripts.

## Quick Start

1. Require and install `capell-app/smart-404`.
2. Enable Smart 404 in the settings surface and choose a maximum of one to ten suggestions.
3. Request a missing path and verify the response remains 404 while the suggestion list is present.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Screenshot contract](docs/screenshots.json)

<!-- prettier-ignore-end -->
