<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

use Filament\Forms\Get;

class UserAttributeFieldFactoryRegistry
{
    protected static $factories = [];

    public static function register(string $type, string $factory): void
    {
        static::$factories[$type] = $factory;
    }

    public static function getFactory(string $type): UserAttributeFieldFactoryInterface
    {
        if (!isset(static::$factories[$type])) {
            throw new \Exception("Factory for type {$type} not registered.");
        }

        return new static::$factories[$type];
    }

    public static function getRegisteredTypes(): array
    {
        return array_keys(static::$factories);
    }

    public static function getConfigurationSchemas(): array
    {
        $schemas = [];

        foreach (static::$factories as $type => $factoryClass) {
            /** @var UserAttributeFieldFactoryInterface */
            $factory = new $factoryClass;
            $factorySchemas = $factory->makeConfigurationSchema();

            foreach ($factorySchemas as $schema) {
                $schema->hidden(fn (Get $get) => $get('type') !== $type);
            }

            $schemas = array_merge($schemas, $factorySchemas);
        }

        return $schemas;
    }
}
