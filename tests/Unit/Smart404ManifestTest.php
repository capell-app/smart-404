<?php

declare(strict_types=1);

use Capell\Admin\Support\Extensions\ExtensionManagementSurfaceRegistry;
use Capell\Core\Contracts\Extensions\RegistersExtensionFrontendComponent;
use Capell\Smart404\Filament\Settings\Smart404SettingsSchema;
use Capell\Smart404\Health\Smart404HealthCheck;
use Capell\Smart404\Manifest\Smart404WidgetContribution;
use Capell\Smart404\Providers\AdminServiceProvider as Smart404AdminServiceProvider;
use Capell\Smart404\Settings\Smart404Settings;

it('registers the installed Smart 404 settings management surface', function (): void {
    $surface = collect(resolve(ExtensionManagementSurfaceRegistry::class)
        ->surfacesForPackage('capell-app/smart-404'))
        ->firstWhere('settingsGroup', Smart404Settings::group());

    expect($surface)->not->toBeNull()
        ->and($surface?->type)->toBe('settings');
});

/**
 * @return array<string, mixed>
 */
function smart404EvidenceJson(string $relativePath): array
{
    $decoded = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/' . $relativePath),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    throw_unless(is_array($decoded), RuntimeException::class, 'Expected Smart 404 evidence JSON to decode to an array.');

    $normalized = [];

    foreach ($decoded as $key => $value) {
        throw_unless(is_string($key), RuntimeException::class, 'Expected Smart 404 evidence JSON to use string keys.');

        $normalized[$key] = $value;
    }

    return $normalized;
}

it('resolves every manifest class and matches the installed settings and routes', function (): void {
    $contents = file_get_contents(__DIR__ . '/../../capell.json');
    if ($contents === false) {
        throw new RuntimeException('Unable to read Smart 404 manifest.');
    }

    $decoded = json_decode(
        $contents,
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    if (! is_array($decoded)) {
        throw new RuntimeException('Smart 404 manifest must decode to an array.');
    }

    $manifest = $decoded;
    $stringList = static function (mixed $value): array {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item)));
    };

    $providers = is_array($manifest['providers'] ?? null) ? $manifest['providers'] : [];
    $contributions = is_array($manifest['contributes'] ?? null) ? $manifest['contributes'] : [];
    $contributionClasses = [];
    foreach ($contributions as $contribution) {
        if (! is_array($contribution)) {
            continue;
        }

        foreach (['class', 'checkClass'] as $key) {
            if (is_string($contribution[$key] ?? null)) {
                $contributionClasses[] = $contribution[$key];
            }
        }
    }

    $actions = is_array($manifest['actions'] ?? null) ? $manifest['actions'] : [];
    $settingsContribution = is_array($contributions[1] ?? null) ? $contributions[1] : [];
    $settingsGroup = $settingsContribution['settingsGroup'] ?? null;
    $settingsSchema = $settingsContribution['settingsSchema'] ?? null;
    if (! is_string($settingsGroup) || ! is_string($settingsSchema)) {
        throw new RuntimeException('Smart 404 settings contribution is incomplete.');
    }

    $classes = [
        ...$stringList($providers['runtime'] ?? null),
        ...$contributionClasses,
        ...$stringList($manifest['settings'] ?? null),
        ...$stringList([$actions['install'] ?? null, $actions['resolveSuggestions'] ?? null]),
    ];

    expect(collect($classes)->every(static fn (string $class): bool => class_exists($class)))->toBeTrue()
        ->and($providers['admin'] ?? null)->toBe([Smart404AdminServiceProvider::class])
        ->and($settingsGroup)->toBe(Smart404Settings::group())
        ->and($settingsSchema)->toBe(Smart404SettingsSchema::class)
        ->and(Smart404WidgetContribution::class)->toImplement(RegistersExtensionFrontendComponent::class)
        ->and(class_exists(Smart404HealthCheck::class))->toBeTrue();
});

it('keeps the promoted installed-App evidence contract explicit and fail-closed', function (): void {
    $manifest = smart404EvidenceJson('capell.json');
    $screenshots = smart404EvidenceJson('docs/screenshots.json');
    $rawEntries = $screenshots['entries'] ?? null;

    throw_unless(is_array($rawEntries), RuntimeException::class, 'Expected Smart 404 screenshot entries to be an array.');

    $entries = [];

    foreach ($rawEntries as $entry) {
        throw_unless(is_array($entry), RuntimeException::class, 'Expected every Smart 404 screenshot entry to be an array.');

        $entries[] = $entry;
    }

    $marketplaceScreenshots = data_get($manifest, 'marketplace.screenshots');

    throw_unless(is_array($marketplaceScreenshots), RuntimeException::class, 'Expected Smart 404 Marketplace screenshots to be an array.');

    expect(collect($marketplaceScreenshots)->pluck('path')->all())->toBe([
        'docs/screenshots/smart-404-missing-page-desktop.png',
        'docs/screenshots/smart-404-missing-page-mobile.png',
        'docs/screenshots/smart-404-settings.png',
    ])
        ->and(collect($marketplaceScreenshots)->every(
            static fn (mixed $screenshot): bool => is_array($screenshot) && ($screenshot['required'] ?? false) === true,
        ))->toBeTrue()
        ->and($entries)->toHaveCount(3)
        ->and(collect($entries)->pluck('id')->all())->toBe([
            'smart-404-missing-page-desktop',
            'smart-404-missing-page-mobile',
            'smart-404-settings',
        ])
        ->and($entries[0]['expectedStatus'])->toBe(404)
        ->and($entries[1]['expectedStatus'])->toBe(404)
        ->and($entries[0]['viewport'])->toBe('desktop')
        ->and($entries[1]['viewport'])->toBe('mobile')
        ->and($entries[2]['url'])->toBe('/extensions')
        ->and($entries[2]['waitFor'])->toBe('.fi-modal-open .fi-modal-window')
        ->and($entries[2]['beforeWait'])->toBe([
            [
                'type' => 'fill',
                'selector' => 'input[wire\\:model\\.live\\.debounce\\.500ms="tableSearch"]',
                'value' => 'Smart 404',
            ],
            ['type' => 'waitForTimeout', 'timeout' => 2000],
            [
                'type' => 'click',
                'selector' => 'button[wire\\:click*="manageExtension"]:has-text("Edit")',
            ],
        ])
        ->and(collect($entries)->every(static fn (array $entry): bool => ($entry['required'] ?? false) === true
            && ($entry['promotionRequired'] ?? false) === true
            && is_string($entry['screenshotPath'] ?? null)
            && is_string($entry['darkScreenshotPath'] ?? null)
            && file_exists(dirname(__DIR__, 4) . '/' . $entry['screenshotPath'])
            && file_exists(dirname(__DIR__, 4) . '/' . $entry['darkScreenshotPath'])))->toBeTrue();
});
