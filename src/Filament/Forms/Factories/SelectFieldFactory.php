<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeFieldFactoryInterface;

class SelectFieldFactory implements UserAttributeFieldFactoryInterface
{
    public function makeField(array $userAttribute): Field
    {
        return Select::make($userAttribute['name'])
            ->options($userAttribute['options'] ?? [])
            ->label($userAttribute['label']);
    }

    public function makeConfigurationSchema(): array
    {
        return [
            TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255),
        ];
    }
}
