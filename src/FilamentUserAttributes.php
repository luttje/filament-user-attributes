<?php

namespace Luttje\FilamentUserAttributes;

use Illuminate\Support\Facades\File;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesResourceContract;
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
        UserAttributeComponentFactoryRegistry::register('number', \Luttje\FilamentUserAttributes\Filament\Factories\NumberInputComponentFactory::class);
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
    public static function getUserAttributeColumns(string $resource): array
    {
        $config = static::getUserAttributeConfig($resource);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The resource '$resource' does not correctly use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeColumns($resource);
    }

    /**
     * Returns the user attribute fields.
     */
    public static function getUserAttributeComponents(string $resource): array
    {
        $config = static::getUserAttributeConfig($resource);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The resource '$resource' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeComponents($resource);
    }

    /**
     * Returns the user attribute configuration model.
     */
    public static function getUserAttributeConfig(string $resource): HasUserAttributesConfigContract
    {
        if (!in_array(HasUserAttributesResourceContract::class, class_implements($resource))) {
            throw new \Exception("The resource '$resource' does not implement the HasUserAttributesResourceContract interface.");
        }

        /** @var ?HasUserAttributesResourceContract */
        $resource = $resource;
        $config = $resource::getUserAttributesConfig();

        if ($config === null) {
            throw new \Exception("The resource '" . strval($resource) . "' did not return a configuration model from the getUserAttributesConfig function (or it was null).");
        }

        return $config;
    }

    /**
     * Finds all resources that have the HasUserAttributesContract interface
     */
    public static function getResourcesImplementingHasUserAttributesResourceContract()
    {
        // TODO: Make resource paths configurable in package config
        $path = app_path('Filament');
        $resources = collect(File::allFiles($path))
            ->map(function ($file) {
                $type = 'App\\Filament\\' . str_replace('/', '\\', $file->getRelativePathname());
                $type = substr($type, 0, -strlen('.php'));

                return $type;
            })
            ->filter(function ($type) {
                if (!class_exists($type)) {
                    return false;
                }

                if (!in_array(\Luttje\FilamentUserAttributes\Contracts\HasUserAttributesResourceContract::class, class_implements($type))) {
                    return false;
                }

                return true;
            })
            ->toArray();

        return $resources;
    }
}
