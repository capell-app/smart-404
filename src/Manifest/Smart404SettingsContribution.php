<?php

declare(strict_types=1);

namespace Capell\Smart404\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionSetting;

final class Smart404SettingsContribution implements ExtensionContribution, RegistersExtensionSetting
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
