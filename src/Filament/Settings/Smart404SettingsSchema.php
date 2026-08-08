<?php

declare(strict_types=1);

namespace Capell\Smart404\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

final class Smart404SettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')
                        ->label(__('capell-smart-404::settings.enabled'))
                        ->helperText(__('capell-smart-404::settings.enabled_helper'))
                        ->default(true),
                    TextInput::make('max_suggestions')
                        ->label(__('capell-smart-404::settings.max_suggestions'))
                        ->helperText(__('capell-smart-404::settings.max_suggestions_helper'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(5),
                ]),
        ];
    }
}
