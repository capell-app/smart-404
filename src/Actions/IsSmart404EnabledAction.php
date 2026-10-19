<?php

declare(strict_types=1);

namespace Capell\Smart404\Actions;

use Capell\Smart404\Settings\Smart404Settings;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static bool run()
 */
final class IsSmart404EnabledAction
{
    use AsFake;
    use AsObject;

    public function handle(): bool
    {
        try {
            if (app()->bound(Smart404Settings::class)) {
                return resolve(Smart404Settings::class)->enabled === true;
            }
        } catch (Throwable) {
            // Fall back to config when settings have not been installed yet.
        }

        return config('capell-smart-404.enabled', true) === true;
    }
}
