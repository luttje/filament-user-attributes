<?php

namespace Luttje\FilamentUserAttributes;

use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeFieldFactoryRegistry;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig;

class FilamentUserAttributes
{
    /**
     * Registers all types of user attribute field factories.
     */
    public static function registerUserAttributeFieldFactories(): void
    {
        UserAttributeFieldFactoryRegistry::register('text', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\TextFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('textarea', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\TextareaFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('richeditor', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\RichEditorFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('tags', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\TagsInputFieldFactory::class);

        UserAttributeFieldFactoryRegistry::register('select', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\SelectFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('checkbox', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\CheckboxFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('radio', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\RadioFieldFactory::class);

        UserAttributeFieldFactoryRegistry::register('date', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\DateTimeFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('datetime', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\DateTimeFieldFactory::class);
        UserAttributeFieldFactoryRegistry::register('time', \Luttje\FilamentUserAttributes\Filament\Forms\Factories\DateTimeFieldFactory::class);
    }

    /**
     * Returns the user attribute columns.
     */
    public static function getUserAttributeColumns(string $model): array
    {
        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The model '$model' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeColumns($model);
    }

    /**
     * Returns the user attribute fields.
     */
    public static function getUserAttributeFields(string $model): array
    {
        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The model '$model' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeFields($model);
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
            throw new \Exception("The model '$model' did not return a configuration model from the getUserAttributesConfig function (or it was null).");
        }

        return $config;
    }
}
