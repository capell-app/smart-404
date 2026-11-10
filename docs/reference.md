# Smart 404 reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/smart-404`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Smart 404 is an **Available**, **No schema impact** Capell package in the **Capell Search & SEO** product group. It ships as `capell-app/smart-404` and extends these surfaces: admin, frontend, console.

- Status: Available
- Tier: premium
- Bundle: search-seo
- Composer package: `capell-app/smart-404`
- Namespace: `Capell\Smart404`
- Theme key: not applicable

## Technical Shape

### Service providers

- `Capell\Smart404\Providers\Smart404ServiceProvider`
- `Capell\Smart404\Providers\AdminServiceProvider`

### Config files

- `packages/smart-404/config/capell-smart-404.php`

### Settings migrations

- `packages/smart-404/database/settings/2026_08_08_000001_create_smart_404_settings.php`

### Settings classes

- `Smart404Settings`

### Filament classes

- `Smart404SettingsSchema`

### Route files

- `packages/smart-404/routes/web.php`

### Actions

- `InstallSmart404PackageAction`
- `IsSmart404EnabledAction`
- `ResolveSmart404SuggestionsAction`

### Data objects

- `Smart404PublicUrlEntryData`
- `Smart404SuggestionData`

### Manifest action API

- `install: Capell\Smart404\Actions\InstallSmart404PackageAction`
- `resolveSuggestions: Capell\Smart404\Actions\ResolveSmart404SuggestionsAction`

### Manifest contributions

- `frontend-component: Capell\Smart404\Manifest\Smart404WidgetContribution`
- `health-check: Capell\Smart404\Manifest\Smart404HealthContribution`
- `route: Capell\Smart404\Manifest\Smart404FrontendRoutesContribution`
- `setting: Capell\Smart404\Manifest\Smart404SettingsContribution`

### Health checks

- `Capell\Smart404\Health\Smart404HealthCheck`

### Blade views

- `packages/smart-404/resources/views/widget.blade.php`

### Cache tags

- `smart-404`


## Data Model

- Required tables: `settings`, `pages`, `sites`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: Docs gap: migrations and manifest contributions do not prove a cascade, pruning command, or timed retention policy.

<!-- prettier-ignore-end -->
