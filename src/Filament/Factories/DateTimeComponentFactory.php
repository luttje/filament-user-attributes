<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

class DateTimeComponentFactory extends BaseComponentFactory
{
    public function makeColumn(array $userAttribute): Column
    {
        $dateFormat = match ($customizations['format'] ?? 'date') {
            'datetime' => 'd-m-Y H:i:s',
            'date' => 'd-m-Y',
            'time' => 'H:i:s',
            default => throw new \Exception('Invalid date format'),
        };

        $column = UserAttributeColumn::make($userAttribute['name'])
            ->dateTime($dateFormat);

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $customizations = $userAttribute['customizations'] ?? [];

        switch ($customizations['format'] ?? 'date') {
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

        if (!$customizations['allow_before_now']) {
            $field->minDate(now());
        }

        return $this->setUpField($field, $userAttribute);
    }

    public function makeDefaultValue(array $userAttribute): mixed
    {
        return now();
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
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.format'))),

            Checkbox::make('allow_before_now')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.allow_history')))
                ->default(false),
        ];
    }
}
