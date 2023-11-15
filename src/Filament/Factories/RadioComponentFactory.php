<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

class RadioComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute, array $customizations): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute, array $customizations): Field
    {
        $options = collect($customizations['options'] ?? [])
            ->mapWithKeys(function ($option) {
                return [$option['id'] => $option['label']];
            });

        return Radio::make($userAttribute['name'])
            ->options($options)
            ->label($userAttribute['label']);
    }

    public function makeDefaultValue(array $userAttribute, array $customizations): mixed
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
                ])
        ];
    }
}
