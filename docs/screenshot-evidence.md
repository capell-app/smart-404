# Smart 404 screenshot evidence

This package has three required route-backed captures in [`screenshots.json`](screenshots.json), each in light and dark modes:

- an anonymous missing page at desktop width;
- the same missing page at mobile width; and
- the authenticated Smart 404 settings contribution.

## Accepted evidence

The shared screenshot runner captured the six outputs from an isolated installed
App with Smart 404 enabled and scoped public URLs seeded. The retained acceptance
report [`docs/screenshot-receipts/cap0128/smart-404.json`](../../../docs/screenshot-receipts/cap0128/smart-404.json) has SHA-256
`54ca6000b7cc5dbe73717477ea920877c4a52e27bf7dc9262a3b59c7b3441f1c`.

Receipt provenance records:

- runner `9c093afa50dedd7ff5cfaa21956a41e0901ab297` (clean);
- Core `af67510bcab13e1f15b62300e1f04a985dccce88` (clean);
- Packages `b11a2339580487440fc4ab861fd3f0eac5ab1b89` (clean);
- four anonymous public captures with expected HTTP 404;
- two authenticated-admin settings captures with HTTP 200; and
- browser and backend diagnostics available with zero console, page, request,
  HTTP, and backend errors for every capture.

The six output hashes are:

| Output                                    | SHA-256                                                            |
| ----------------------------------------- | ------------------------------------------------------------------ |
| `smart-404-missing-page-desktop.png`      | `fb4982afac8575c5978be5025c2f09a7c327f8e0e3fe0584fdd6337102cc22cc` |
| `smart-404-missing-page-desktop-dark.png` | `45e4f048a1dd94e734b295fb29c5ae95df1af93180dd36e7acfd6e174dcc7fb8` |
| `smart-404-missing-page-mobile.png`       | `6b52c325bfd588c16b211ba8c7cec2eaa5282c603e17934d9e06c48fc4401159` |
| `smart-404-missing-page-mobile-dark.png`  | `01b9977d2556b854008fa86ef92c63d02690f045681c3dd234ce031bc95c02be` |
| `smart-404-settings.png`                  | `d1df1dcd522d6a009b68da37b52c86f05be44e248ae4f1d14f88dae88208bc18` |
| `smart-404-settings-dark.png`             | `499cd85d9f4ebe4b9ad96122676f0aa710f1ece455b6bef201682828445fbe54` |

All six outputs were visually inspected. Desktop and mobile missing-page copy,
actions, illustration, and footer are complete and legible; the mobile layouts
have no clipping or overlap. Both authenticated settings captures show the
Smart 404 modal with enablement and maximum-suggestions controls unobstructed.
The native-pixel diagnostic crop used to settle the scaled dark-preview question
is retained outside the repository and is not a promoted screenshot.

Validate both the output paths and manifest synchronization with:

```bash
npm run screenshots:capture:check
npm run screenshots:check
```

If these outputs are replaced, the replacement must repeat the same shared-runner
receipt and human-review gate. A manifest check without authentic outputs remains
insufficient evidence.
