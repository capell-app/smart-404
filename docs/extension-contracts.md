# Extension and action examples

<!-- Maintained by scripts/generate-package-readmes.php -->

Use the action functions with records and Data objects supplied by your application.
They pass each argument to the package operation and return its result.

These adapters show container registration. Use the owning package registry when
a contract requires contributor discovery.

Contract adapters wrap an existing implementation. Call their registration function
from your service provider with that implementation; tagged contracts keep their declared tag.
Resolve the backend by its concrete class before registration so the replacement contract
does not resolve itself. Static contract metadata uses one backend class per adapter.

## Action `install`

<!-- example: action install -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\Smart404;

function runInstall(\Capell\Core\Data\PackageData $package, array $arguments = [], ?\Capell\Core\Contracts\ProgressReporter $reporter = null): void
{
    \Capell\Smart404\Actions\InstallSmart404PackageAction::run($package, $arguments, $reporter);
}
```

## Action `resolveSuggestions`

<!-- example: action resolveSuggestions -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\Smart404;

function runResolveSuggestions(string $path, ?\Capell\Core\Models\Site $site = null, ?\Capell\Core\Models\Language $language = null, ?\Illuminate\Support\Collection $entries = null): \Illuminate\Support\Collection
{
    return \Capell\Smart404\Actions\ResolveSmart404SuggestionsAction::run($path, $site, $language, $entries);
}
```
