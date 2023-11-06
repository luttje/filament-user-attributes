<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeFieldFactoryInterface;

class RadioFieldFactory implements UserAttributeFieldFactoryInterface
{
    public function makeField(array $userAttribute): Field
    {
        return Radio::make($userAttribute['name'])
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
