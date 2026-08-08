<?php

declare(strict_types=1);

namespace Capell\Smart404\Actions;

use Capell\Core\Actions\Install\PublishPackageMigrationsAction;
use Capell\Core\Actions\Install\RunMigrationsAction;
use Capell\Core\Contracts\PackageLifecycleAction;
use Capell\Core\Contracts\ProgressReporter;
use Capell\Core\Data\PackageData;
use Capell\Core\Models\Site;
use Capell\Core\Support\Install\NullProgressReporter;
use Capell\Frontend\Actions\RegenerateSiteErrorPagesAction;
use Capell\Smart404\Support\Assets\PublishSmart404AssetsAction;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class InstallSmart404PackageAction implements PackageLifecycleAction
{
    use AsFake;
    use AsObject;

    public function handle(PackageData $package, array $arguments = [], ?ProgressReporter $reporter = null): void
    {
        $reporter ??= new NullProgressReporter;
        PublishPackageMigrationsAction::run(new Collection([$package->name => $package]), $reporter);
        RunMigrationsAction::run($reporter);
        PublishSmart404AssetsAction::run();

        if (config('capell-frontend.static_errors.enabled', false) === true && class_exists('Capell\\Frontend\\Actions\\RegenerateSiteErrorPagesAction')) {
            Site::query()->where('status', true)->pluck('id')->each(static function (mixed $siteId): void {
                if (is_numeric($siteId)) {
                    RegenerateSiteErrorPagesAction::run((int) $siteId);
                }
            });
        }

        $reporter->report((string) __('capell-smart-404::package.installed'));
    }
}
