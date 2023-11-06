<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeFieldFactoryInterface;

class DateTimeFieldFactory implements UserAttributeFieldFactoryInterface
{
    public function makeField(array $userAttribute): Field
    {
        switch ($userAttribute['format'] ?? 'date') {
            case 'datetime':
                $field = DateTimePicker::make($userAttribute['name']);
                break;
            case 'time':
                $field = TimePicker::make($userAttribute['name']);
                break;
            case 'date':
            default:
                $field = DatePicker::make($userAttribute['name']);
        }

        return $field->label($userAttribute['label']);
    }

    public function makeConfigurationSchema(): array
    {
        return [
            TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255),
        ];
    }
}
