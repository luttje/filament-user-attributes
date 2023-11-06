<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeComponentFactoryInterface;

class CheckboxComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute): Field
    {
        return Checkbox::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeConfigurationSchema(): array
    {
        return [
        ];
    }
}
