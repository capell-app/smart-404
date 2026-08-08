<?php

declare(strict_types=1);

namespace Capell\Smart404\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class Smart404HealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    /** @return Collection<int, DoctorCheckResultData> */
    public static function runDiagnostics(): Collection
    {
        $diagnostics = app()->bound(RenderHookRegistry::class)
            ? resolve(RenderHookRegistry::class)->diagnostics()[RenderHookLocation::AfterContent->value] ?? []
            : [];
        $registered = collect($diagnostics)->contains(static fn (array $entry): bool => $entry['owner'] === 'capell-app/smart-404'
            && $entry['key'] === 'smart-404-widget'
            && $entry['target'] === 'capell::layout.main');
        $routesRegistered = collect([
            'capell-smart-404.suggestions',
            'capell-smart-404.script',
            'capell-smart-404.styles',
        ])->every(static fn (string $route): bool => Route::has($route));
        $passed = $registered && $routesRegistered;

        return collect([new DoctorCheckResultData(
            label: (string) __('capell-smart-404::package.health.hook.label'),
            passed: $passed,
            message: $passed
                ? (string) __('capell-smart-404::package.health.hook.passed')
                : (string) __('capell-smart-404::package.health.hook.failed'),
            remediation: $passed ? null : (string) __('capell-smart-404::package.health.hook.remediation'),
        )]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }
}
