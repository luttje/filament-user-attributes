<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeFieldFactoryInterface;

class TagsInputFieldFactory implements UserAttributeFieldFactoryInterface
{
    public function makeField(array $userAttribute): Field
    {
        return TagsInput::make($userAttribute['name'])
            ->label($userAttribute['label'])
            ->suggestions($userAttribute['suggestions'] ?? []);
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
