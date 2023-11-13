<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

class NumberInputComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->numeric()
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute): Field
    {
        return TextInput::make($userAttribute['name'])
            ->numeric()
            ->label($userAttribute['label'])
            ->step(10 ** ($userAttribute['decimal_places'] ?? 0))
            ->minValue($userAttribute['minimum'] ?? 0)
            ->maxValue($userAttribute['maximum'] ?? PHP_INT_MAX);
    }

    public function makeDefaultValue(array $userAttribute): mixed
    {
        return 0;
    }

    public function makeConfigurationSchema(): array
    {
        return [
            TextInput::make('decimal_places')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::attributes.decimal_places')))
                ->step(1)
                ->required()
                ->default(0),
            TextInput::make('minimum')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::attributes.minimum')))
                ->step(fn (Get $get) => $get('decimal_places') * 0.1)
                ->minValue(PHP_INT_MIN)
                ->default(PHP_INT_MIN),
            TextInput::make('maximum')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::attributes.maximum')))
                ->step(fn (Get $get) => $get('decimal_places') * 0.1)
                ->maxValue(PHP_INT_MAX)
                ->default(PHP_INT_MAX),
        ];
    }
}
