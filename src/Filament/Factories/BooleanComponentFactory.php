<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

abstract class BooleanComponentFactory extends BaseComponentFactory
{
    public function makeColumn(array $userAttribute): Column
    {
        if (config('filament-user-attributes.use_icons_for_boolean_components', false)) {
            $column = UserAttributeColumn::setUpColumnState(
                \Filament\Tables\Columns\IconColumn::make($userAttribute['name'])
                        ->boolean()
            );
        } else {
            $column = UserAttributeColumn::make($userAttribute['name'])
                ->formatStateUsing(function ($state) {
                    return $state ? __('filament-user-attributes::user-attributes.boolean_component_display_yes') : __('filament-user-attributes::user-attributes.boolean_component_display_no');
                });
        }

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $field = Toggle::make($userAttribute['name']);

        return $this->setUpField($field, $userAttribute);
    }

    public function makeDefaultValue(array $userAttribute): mixed
    {
        $customizations = $userAttribute['customizations'] ?? [];
        return $customizations['default'] ?? false;
    }

    public function makeConfigurationSchema(): array
    {
        return [
            Toggle::make('default')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.default'))),

            ...parent::makeConfigurationSchema(),
        ];
    }
}
