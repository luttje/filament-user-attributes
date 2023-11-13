<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

class TagsInputComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute): Field
    {
        return TagsInput::make($userAttribute['name'])
            ->splitKeys(['Tab', ' '])
            ->label($userAttribute['label'])
            ->suggestions($userAttribute['suggestions'] ?? []);
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
                ->label(ucfirst(__('filament-user-attributes::attributes.suggestions')))
                ->helperText(__('filament-user-attributes::user-attributes.suggestions_help')),
        ];
    }
}
