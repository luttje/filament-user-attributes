<?php

namespace Luttje\FilamentUserAttributes;

use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig;

class FilamentUserAttributes
{
    /**
     * Registers all types of user attribute field factories.
     */
    public static function registerUserAttributeComponentFactories(): void
    {
        UserAttributeComponentFactoryRegistry::register('text', \Luttje\FilamentUserAttributes\Filament\Factories\TextComponentFactory::class);
        UserAttributeComponentFactoryRegistry::register('textarea', \Luttje\FilamentUserAttributes\Filament\Factories\TextareaComponentFactory::class);
        UserAttributeComponentFactoryRegistry::register('richeditor', \Luttje\FilamentUserAttributes\Filament\Factories\RichEditorComponentFactory::class);
        UserAttributeComponentFactoryRegistry::register('tags', \Luttje\FilamentUserAttributes\Filament\Factories\TagsInputComponentFactory::class);

        UserAttributeComponentFactoryRegistry::register('select', \Luttje\FilamentUserAttributes\Filament\Factories\SelectComponentFactory::class);
        UserAttributeComponentFactoryRegistry::register('checkbox', \Luttje\FilamentUserAttributes\Filament\Factories\CheckboxComponentFactory::class);
        UserAttributeComponentFactoryRegistry::register('radio', \Luttje\FilamentUserAttributes\Filament\Factories\RadioComponentFactory::class);

        UserAttributeComponentFactoryRegistry::register('datetime', \Luttje\FilamentUserAttributes\Filament\Factories\DateTimeComponentFactory::class);
    }

    /**
     * Returns the user attribute columns.
     */
    public static function getUserAttributeColumns(string $model): array
    {
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The model '$model' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeColumns($model);
    }

    /**
     * Returns the user attribute fields.
     */
    public static function getUserAttributeComponents(string $model): array
    {
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The model '$model' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeComponents($model);
    }

    /**
     * Returns the user attribute configuration model.
     */
    public static function getUserAttributeConfig(string $model): HasUserAttributesConfigContract
    {
        if (!in_array(HasUserAttributesContract::class, class_implements($model))) {
            throw new \Exception("The model '$model' does not implement the HasUserAttributesContract interface.");
        }

        /** @var ?HasUserAttributesContract */
        $model = $model;
        $config = $model::getUserAttributesConfig();

        if ($config === null) {
            throw new \Exception("The model '" . strval($model) . "' did not return a configuration model from the getUserAttributesConfig function (or it was null).");
        }

        return $config;
    }
}
