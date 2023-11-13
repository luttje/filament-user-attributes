<?php

namespace Luttje\FilamentUserAttributes\Filament;

use Filament\Forms;
use Filament\Forms\Get;

class UserAttributeComponentFactoryRegistry
{
    protected static $factories = [];

    public static function register(string $type, string $factory): void
    {
        static::$factories[$type] = $factory;
    }

    public static function getFactory(string $type): UserAttributeComponentFactoryInterface
    {
        if (!isset(static::$factories[$type])) {
            throw new \Exception("Factory for type {$type} not registered.");
        }

        return new static::$factories[$type]();
    }

    public static function getRegisteredTypes(): array
    {
        return array_keys(static::$factories);
    }

    public static function getConfigurationSchemas(string $resource): array
    {
        $schemas = [];

        // TODO: Make configs for these
        $schemas[] = Forms\Components\TextInput::make('name')
            ->label(ucfirst(__('filament-user-attributes::attributes.name')))
            ->required()
            ->maxLength(255);
        $schemas[] = Forms\Components\Checkbox::make('required')
            ->label('Required');
        $schemas[] = Forms\Components\TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255);
        $schemas[] = Forms\Components\Select::make('type')
            ->options(array_combine(static::getRegisteredTypes(), static::getRegisteredTypes()))
            ->label(ucfirst(__('filament-user-attributes::attributes.type')))
            ->required()
            ->live();

        // Will be filled by the previous wizard step, with the resource this is for.
        $schemas[] = Forms\Components\Select::make('order_sibling')
            ->selectablePlaceholder()
            ->live()
            ->placeholder(ucfirst(__('filament-user-attributes::user-attributes.select_order')))
            ->options(function () use ($resource) {
                $fields = $resource::getFieldsForOrdering();
                $fields = array_combine(array_column($fields, 'label'), array_column($fields, 'label'));
                return $fields;
            })
            ->helperText(ucfirst(__('filament-user-attributes::user-attributes.order_sibling_help')))
            ->label(ucfirst(__('filament-user-attributes::attributes.order_sibling')));

        // Before or after the order sibling field
        $schemas[] = Forms\Components\Select::make('order_position')
            ->options([
                'before' => __('filament-user-attributes::attributes.order_position_before'),
                'after' => __('filament-user-attributes::attributes.order_position_after'),
            ])
            ->label(ucfirst(__('filament-user-attributes::attributes.order_position')))
            ->required(function (Get $get) {
                $sibling = $get('order_sibling');
                return $sibling !== null && $sibling !== '';
            });

        foreach (static::$factories as $type => $factoryClass) {
            /** @var UserAttributeComponentFactoryInterface */
            $factory = new $factoryClass();
            $factorySchema = $factory->makeConfigurationSchema();

            foreach ($factorySchema as $field) {
                $field->hidden(fn (Get $get) => $get('type') !== $type);
            }

            $schemas = array_merge($schemas, $factorySchema);
        }

        return $schemas;
    }
}
