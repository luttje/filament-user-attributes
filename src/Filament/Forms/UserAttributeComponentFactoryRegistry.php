<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

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

    public static function getConfigurationSchemas(): array
    {
        $schemas = [];

        // TODO: Make configs for these
        $schemas[] = Forms\Components\TextInput::make('name')
            ->label(ucfirst(__('validation.attributes.name')))
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
            ->label(ucfirst(__('validation.attributes.type')))
            ->required()
            ->live();

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
