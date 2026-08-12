# Smart 404 screenshot evidence

This package has three deferred captures in [`screenshots.json`](screenshots.json):

- an anonymous missing page at desktop width;
- the same missing page at mobile width; and
- the authenticated Smart 404 settings contribution.

## Capture checklist

Run the shared screenshot runner against a clean, installed Capell App with
Smart 404 enabled and seeded indexed pages. Use the exact App/Core/Packages
source identities for the release candidate. Do not capture the package
workbench or copy an image from another package.

Before promotion, validate both the output paths and the receipt metadata:

```bash
npm run screenshots:capture:check
npm run screenshots:check
```

Review each receipt and image for the route, viewport, source identity, HTTP
404 preservation, absence of authoring controls, and absence of fixture-state
text. Only then should the outputs be committed, the entries changed to
`required: true`, and the Marketplace screenshot list populated in the same
change.

If the host application, runner, or seeded data is unavailable, keep the
entries deferred and record the blocker. A manifest check without authentic
outputs is not screenshot evidence.
