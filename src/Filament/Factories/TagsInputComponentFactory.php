<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

class TagsInputComponentFactory extends BaseComponentFactory
{
    public function makeColumn(array $userAttribute): Column
    {
        $column = UserAttributeColumn::make($userAttribute['name']);

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $customizations = $userAttribute['customizations'] ?? [];

        $field = TagsInput::make($userAttribute['name'])
            ->splitKeys(['Tab', ' '])
            ->suggestions($customizations['suggestions'] ?? []);

        return $this->setUpField($field, $userAttribute);
    }

    public function makeDefaultValue(array $userAttribute): mixed
    {
        return [];
    }

    public function makeConfigurationSchema(): array
    {
        return [
            TagsInput::make('suggestions')
                ->splitKeys(['Tab', ' '])
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.suggestions')))
                ->helperText(__('filament-user-attributes::user-attributes.suggestions_help')),

            ...parent::makeConfigurationSchema(),
        ];
    }
}
