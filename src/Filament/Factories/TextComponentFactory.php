<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

class TextComponentFactory extends BaseComponentFactory
{
    public function makeColumn(array $userAttribute): Column
    {
        $column = UserAttributeColumn::make($userAttribute['name']);

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $customizations = $userAttribute['customizations'] ?? [];

        $field = TextInput::make($userAttribute['name'])
            ->placeholder($customizations['placeholder'] ?? null);

        return $this->setUpField($field, $userAttribute);
    }

    public function makeDefaultValue(array $userAttribute): mixed
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
