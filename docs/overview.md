# Smart 404

<!-- prettier-ignore-start -->

## What This Plugin Adds

Smart 404 renders a small accessible suggestion list after error content and hydrates static error documents through a bounded JSON endpoint.

## Why It Matters

**For developers:** The resolver ranks similar URLs first, then uses the deepest indexed ancestor and direct children.

**For teams:** The result avoids analytics, redirects, AI services, and authoring leakage.

## Screens And Workflow

The screenshot contract defines three future-required installed-App captures: an anonymous 404 at desktop and mobile widths, plus the authenticated `/admin/settings` contribution. They remain `required: false` while deferred so strict artifact and fixture-hygiene checks do not treat absent outputs as committed evidence; `promotionRequired: true` preserves the gate that authentic receipts and reviewed output files must exist before promotion. No placeholder or fixture image is Marketplace evidence; `capell.json` therefore keeps its Marketplace screenshot list empty until those captures are reviewed and promoted.

## Technical Shape

The render hook receives hydrated DTOs. The endpoint returns only `{suggestions:[{title,url}]}` and sets private cache headers. External assets use a two-second client timeout and fail closed.

## Data Model

`Smart404SuggestionData` contains a translated title and a relative public URL. Registry input is adapted from Discovery Foundation and legacy Site Discovery entries.

## Install Impact

Installation publishes settings migrations and assets, registers the AfterContent hook and three routes, and regenerates static error documents when configured.

## Common Pitfalls

Keep the missing path validated, exclude the requested URL, and preserve current origin, site, language, and indexability filters. A disabled package must return 404 from the endpoint.

## Troubleshooting

Use the health check to verify the hook and routes, then inspect the JSON endpoint with a valid absolute path. A timeout or failed response should hide the shell.

## Quick Start

1. Install the package and run its install action.
2. Enable Smart 404 in the settings surface.
3. Request a missing frontend path and verify the response status remains 404.

## Next Steps

- [Package README](../README.md)
- [Screenshot contract](screenshots.json)

<!-- prettier-ignore-end -->
