<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeComponentFactoryInterface;

class TextareaComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute): Field
    {
        return Textarea::make($userAttribute['name'])
            ->label($userAttribute['label'])
            ->placeholder($userAttribute['placeholder'] ?? null);
    }

    public function makeConfigurationSchema(): array
    {
        return [
            TextInput::make('placeholder')
                ->label(ucfirst(__('validation.attributes.placeholder')))
                ->maxLength(255),
        ];
    }
}
