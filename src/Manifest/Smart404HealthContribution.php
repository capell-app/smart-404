<?php

declare(strict_types=1);

namespace Capell\Smart404\Manifest;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class Smart404HealthContribution implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
