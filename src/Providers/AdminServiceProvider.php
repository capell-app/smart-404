<?php

declare(strict_types=1);

namespace Capell\Smart404\Providers;

use Capell\Admin\Data\Extensions\ExtensionManagementSurfaceData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Support\Packages\RegistersInstalledRuntime;
use Capell\Smart404\Settings\Smart404Settings;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\ServiceProvider;
use Override;

final class AdminServiceProvider extends ServiceProvider
{
    use RegistersInstalledRuntime;

    #[Override]
    public function register(): void
    {
        $this->registerInstalledRuntime(Smart404ServiceProvider::$packageName, 'admin');
    }

    protected function bootInstalledRuntime(): void
    {
        CapellAdmin::registerExtensionManagementSurface(ExtensionManagementSurfaceData::settings(
            packageName: Smart404ServiceProvider::$packageName,
            label: 'capell-smart-404::settings.title',
            settingsGroup: Smart404Settings::group(),
            icon: Heroicon::OutlinedExclamationTriangle,
        ));
    }
}
