<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

class NumberInputComponentFactory extends BaseComponentFactory
{
    public const DEFAULT_MINIMUM = -999999;

    public const DEFAULT_MAXIMUM = 999999;

    public function makeColumn(array $userAttribute): Column
    {
        $column = UserAttributeColumn::make($userAttribute['name'])
            ->numeric();

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $customizations = $userAttribute['customizations'] ?? [];

        $field = TextInput::make($userAttribute['name'])
            ->numeric()
            ->step(10 ** ($customizations['decimal_places'] ?? 0))
            ->minValue($customizations['minimum'] ?? static::DEFAULT_MINIMUM)
            ->maxValue($customizations['maximum'] ?? static::DEFAULT_MAXIMUM);

        return $this->setUpField($field, $userAttribute);
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
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.decimal_places')))
                ->step(1)
                ->required()
                ->default(0),
            TextInput::make('minimum')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.minimum')))
                ->step(fn (Get $get) => $get('decimal_places') * 0.1)
                ->minValue(static::DEFAULT_MINIMUM)
                ->default(static::DEFAULT_MINIMUM),
            TextInput::make('maximum')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.maximum')))
                ->step(fn (Get $get) => $get('decimal_places') * 0.1)
                ->maxValue(static::DEFAULT_MAXIMUM)
                ->default(static::DEFAULT_MAXIMUM),
        ];
    }
}
