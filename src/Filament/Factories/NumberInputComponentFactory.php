<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\Column;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

class NumberInputComponentFactory extends BaseComponentFactory
{
    public const DEFAULT_MINIMUM = -999999;

    public const DEFAULT_MAXIMUM = 999999;

    public function makeColumn(array $userAttribute): Column
    {
        $customizations = $userAttribute['customizations'] ?? [];

        $column = UserAttributeColumn::make($userAttribute['name']);

        if (isset($customizations['is_currency'])) {
            $column->money(
                currency: $customizations['currency_format'] ?? 'EUR',
            );
        } else {
            $column->numeric(
                decimalPlaces: isset($customizations['decimal_places']) ? $customizations['decimal_places'] : 0
            );
        }

        return $this->setUpColumn($column, $userAttribute);
    }

    public function makeField(array $userAttribute): Field
    {
        $customizations = $userAttribute['customizations'] ?? [];

        $field = TextInput::make($userAttribute['name'])
            ->numeric()
            ->step(
                isset($customizations['decimal_places'])
                    ? (1 / (10 ** $customizations['decimal_places']))
                    : 1
            )
            ->minValue($customizations['minimum'] ?? static::DEFAULT_MINIMUM)
            ->maxValue($customizations['maximum'] ?? static::DEFAULT_MAXIMUM);

        return $this->setUpField($field, $userAttribute);
    }

    public function makeDefaultValue(array $userAttribute): mixed
    {
        return 0;
    }

    public function makeConfigurationSchema(): array
    {
        $allCurrencyData = require __DIR__ . '/../../Data/ISO4217.php';

        $popularCurrencyCodes = ['USD', 'EUR', 'GBP', 'JPY', 'CAD'];

        $popularCurrencies = [];
        $remainingCurrencies = [];

        foreach ($allCurrencyData as $code => $currency) {
            $label = $currency['name'] . ' (' . $currency['symbol'] . ')';

            if (in_array($code, $popularCurrencyCodes, true)) {
                $popularCurrencies[$code] = $label;
            } else {
                $remainingCurrencies[$code] = $label;
            }
        }

        asort($popularCurrencies);
        asort($remainingCurrencies);

        return [
            Checkbox::make('is_currency')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.is_currency')))
                ->default(false)
                ->live()
                ->afterStateUpdated(function (Set $set, ?bool $state) {
                    if ($state) {
                        $set('currency_format', 'EUR');
                    }
                }),

            Select::make('currency_format')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.currency_format')))
                ->options([
                    __('filament-user-attributes::user-attributes.attributes.currency_format_common') => $popularCurrencies,
                    __('filament-user-attributes::user-attributes.attributes.currency_format_other') => $remainingCurrencies
                ])
                ->default('EUR')
                ->visible(fn (Get $get) => $get('is_currency'))
                ->dehydrated(fn (Get $get) => $get('is_currency')),

            TextInput::make('decimal_places')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.decimal_places')))
                ->step(1)
                ->required()
                ->default(0),

            TextInput::make('minimum')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.minimum')))
                ->step(fn (Get $get) => $get('decimal_places') * 0.1)
                ->minValue(static::DEFAULT_MINIMUM)
                ->default(static::DEFAULT_MINIMUM),

            TextInput::make('maximum')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.maximum')))
                ->step(fn (Get $get) => $get('decimal_places') * 0.1)
                ->maxValue(static::DEFAULT_MAXIMUM)
                ->default(static::DEFAULT_MAXIMUM),

            ...parent::makeConfigurationSchema(),
        ];
    }
}
