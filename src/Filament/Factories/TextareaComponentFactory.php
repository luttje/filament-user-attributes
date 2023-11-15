<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

class TextareaComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute, array $customizations): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute, array $customizations): Field
    {
        return Textarea::make($userAttribute['name'])
            ->label($userAttribute['label'])
            ->placeholder($customizations['placeholder'] ?? null)
            ->maxLength(9000);
    }

    public function makeDefaultValue(array $userAttribute, array $customizations): mixed
    {
        return '';
    }

    public function makeConfigurationSchema(): array
    {
        return [
            TextInput::make('placeholder')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.placeholder')))
                ->maxLength(255),
        ];
    }
}
