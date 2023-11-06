<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeFieldFactoryInterface;

class TextareaFieldFactory implements UserAttributeFieldFactoryInterface
{
    public function makeField(array $userAttribute): Field
    {
        return Textarea::make($userAttribute['name'])
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
