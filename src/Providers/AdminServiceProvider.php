<?php

declare(strict_types=1);

namespace Capell\Smart404\Providers;

use Capell\Admin\Data\Extensions\ExtensionManagementSurfaceData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Smart404\Settings\Smart404Settings;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! CapellCore::isPackageInstalled(Smart404ServiceProvider::$packageName)) {
            return;
        }

        CapellAdmin::registerExtensionManagementSurface(ExtensionManagementSurfaceData::settings(
            packageName: Smart404ServiceProvider::$packageName,
            label: 'capell-smart-404::settings.title',
            settingsGroup: Smart404Settings::group(),
            icon: Heroicon::OutlinedExclamationTriangle,
        ));
    }
}
