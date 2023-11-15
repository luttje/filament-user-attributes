<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

class CheckboxComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute, array $customizations): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute, array $customizations): Field
    {
        return Checkbox::make($userAttribute['name'])
            ->label($userAttribute['label'])
            ->default($customizations['default'] ?? false);
    }

    public function makeDefaultValue(array $userAttribute, array $customizations): mixed
    {
        return $customizations['default'] ?? false;
    }

    public function makeConfigurationSchema(): array
    {
        return [
            Checkbox::make('default')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.default'))),
        ];
    }
}
