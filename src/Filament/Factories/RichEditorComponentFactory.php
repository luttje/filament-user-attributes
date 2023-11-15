<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

class RichEditorComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute, array $customizations): Column
    {
        return UserAttributeColumn::make($userAttribute['name'])
            ->label($userAttribute['label']);
    }

    public function makeField(array $userAttribute, array $customizations): Field
    {
        return RichEditor::make($userAttribute['name'])
            ->label($userAttribute['label'])
            ->maxLength(9000);
    }

    public function makeDefaultValue(array $userAttribute, array $customizations): mixed
    {
        return '';
    }

    public function makeConfigurationSchema(): array
    {
        return [
        ];
    }
}
