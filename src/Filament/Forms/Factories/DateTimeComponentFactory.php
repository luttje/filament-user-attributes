<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms\Factories;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeComponentFactoryInterface;

class DateTimeComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute): Column
    {
        $dateFormat = match ($userAttribute['format'] ?? 'date') {
            'datetime' => 'd-m-Y H:i:s',
            'date' => 'd-m-Y',
            'time' => 'H:i:s',
            default => throw new \Exception('Invalid date format'),
        };
        return UserAttributeColumn::make($userAttribute['name'])
            ->dateTime($dateFormat)
            ->label($userAttribute['label']);
    }

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

        if (!$userAttribute['allow_before_now']) {
            $field->minDate(now());
        }

        return $field->label($userAttribute['label']);
    }

    public function makeConfigurationSchema(): array
    {
        return [
            Select::make('format')
                ->options([
                    'datetime' => 'Date & Time',
                    'date' => 'Date',
                    'time' => 'Time',
                ])
                ->label(ucfirst(__('validation.attributes.format'))),

            Checkbox::make('allow_before_now')
                ->label(ucfirst(__('validation.attributes.allow_history')))
                ->default(false),
        ];
    }
}
