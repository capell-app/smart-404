<?php

declare(strict_types=1);

namespace Capell\Smart404\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\Core\Contracts\SettingsSchemaContract;
use Capell\Smart404\Filament\Settings\Smart404SettingsSchema;
use Spatie\LaravelSettings\Settings;

final class Smart404Settings extends Settings implements SettingsContract, SettingsSchemaContract
{
    public bool $enabled = true;

    public int $max_suggestions = 5;

    public static function group(): string
    {
        return 'smart_404';
    }

    public static function schema(): string
    {
        return Smart404SettingsSchema::class;
    }
}
