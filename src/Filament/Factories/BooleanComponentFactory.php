<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

abstract class BooleanComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute, array $customizations): Column
    {
        if (!config('filament-user-attributes.use_icons_for_boolean_components', false)) {
            return UserAttributeColumn::make($userAttribute['name'])
                ->label($userAttribute['label'])
                ->default($customizations['default'] ?? false)
                ->formatStateUsing(function ($state) {
                    return $state ? __('filament-user-attributes::user-attributes.boolean_component_display_yes') : __('filament-user-attributes::user-attributes.boolean_component_display_no');
                });
        }

        return UserAttributeColumn::setUpColumn(
            \Filament\Tables\Columns\IconColumn::make($userAttribute['name'])
                ->label($userAttribute['label'])
                ->default($customizations['default'] ?? false)
                ->boolean()
        );
    }

    public function makeField(array $userAttribute, array $customizations): Field
    {
        return Toggle::make($userAttribute['name'])
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
            Toggle::make('default')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.default'))),
        ];
    }
}
