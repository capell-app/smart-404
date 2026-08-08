<?php

declare(strict_types=1);

namespace Capell\Smart404\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\DiscoveryFoundation\Providers\DiscoveryFoundationServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\SiteDiscovery\Providers\SiteDiscoveryServiceProvider;
use Capell\Smart404\Providers\Smart404ServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class Smart404TestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-smart-404';
    }

    /** @return class-string[] */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            DiscoveryFoundationServiceProvider::class,
            SiteDiscoveryServiceProvider::class,
            Smart404ServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(DiscoveryFoundationServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SiteDiscoveryServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(Smart404ServiceProvider::$packageName);
    }
}
