<?php

declare(strict_types=1);

namespace Capell\Smart404\Providers;

use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Events\FrontendRenderPreparing;
use Capell\Frontend\Support\Render\FrontendHookRegistrar;
use Capell\Smart404\Actions\ResolveSmart404SuggestionsAction;
use Capell\Smart404\Filament\Settings\Smart404SettingsSchema;
use Capell\Smart404\Settings\Smart404Settings;
use Capell\Smart404\Support\RenderHooks\RegisterSmart404Hook;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\LaravelPackageTools\Package;

final class Smart404ServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-smart-404';

    public static string $packageName = 'capell-app/smart-404';

    public static PackageTypeEnum $type = PackageTypeEnum::Plugin;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-smart-404')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasAssets()
            ->hasRoute('web')
            ->hasMigrations(['2026_08_08_000001_create_smart_404_settings']);
    }

    public function packageBooted(): void
    {
        $this->registerRateLimiter();

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerSettings();
        $this->registerFrontendDataListener();
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views');

        if ($this->app->bound(FrontendHookRegistrar::class)) {
            resolve(FrontendHookRegistrar::class)->contribute(
                location: RenderHookLocation::AfterContent,
                extension: new RegisterSmart404Hook,
                owner: self::$packageName,
                key: 'smart-404-widget',
                scenario: 'frontend-main-layout',
                target: 'capell::layout.main',
                cacheSafe: false,
            );
        }

    }

    protected function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerFrontendDataListener(): void
    {
        $this->app->make(Dispatcher::class)->listen(
            FrontendRenderPreparing::class,
            static function (FrontendRenderPreparing $event): void {
                if (! $event->renderContext->isError
                    || ! $event->renderContext->site instanceof Site
                    || ! $event->renderContext->language instanceof Language) {
                    return;
                }

                $event->context->setFrontendData(
                    'smart-404.suggestions',
                    ResolveSmart404SuggestionsAction::run(
                        request()->getPathInfo(),
                        $event->renderContext->site,
                        $event->renderContext->language,
                    ),
                );
            },
        );
    }

    private function registerSettings(): void
    {
        $this->surface()->settingsClass(Smart404Settings::group(), Smart404Settings::class);
        $this->surface()->settingsSchema(Smart404Settings::group(), Smart404SettingsSchema::class);
        $this->surface()->settingsMetadata(new SettingsGroupMetadata(
            group: Smart404Settings::group(),
            label: 'capell-smart-404::settings.title',
            icon: Heroicon::OutlinedExclamationTriangle,
            navigationGroup: 'capell-admin::navigation.group_system',
            navigationSort: 96,
            packageName: self::$packageName,
        ));
    }

    private function registerRateLimiter(): void
    {
        RateLimiter::for(
            'capell-smart-404-suggestions',
            static fn (Request $request): Limit => Limit::perMinute(
                max(1, is_numeric($configured = config('capell-smart-404.rate_limit.per_minute', 60)) ? (int) $configured : 60),
            )->by($request->ip()),
        );
    }
}
