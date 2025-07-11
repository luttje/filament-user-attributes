<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

class SelectComponentFactory extends BaseComponentFactory
{
    public function makeColumn(array $userAttribute): Column
    {
        $column = UserAttributeColumn::make($userAttribute['name']);

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $customizations = $userAttribute['customizations'] ?? [];

        $options = collect($customizations['options'] ?? [])
            ->mapWithKeys(function ($option) {
                return [$option['id'] => $option['label']];
            });

        $field = Select::make($userAttribute['name'])
            ->options($options);

        return $this->setUpField($field, $userAttribute);
    }

    public function makeDefaultValue(array $userAttribute): mixed
    {
        return null;
    }

    public function makeConfigurationSchema(): array
    {
        return [
            Repeater::make('options')
                ->schema([
                    TextInput::make('id')->required(),
                    TextInput::make('label'),
                ]),

            ...parent::makeConfigurationSchema(),
        ];
    }
}
