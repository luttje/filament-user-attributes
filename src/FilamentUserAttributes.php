<?php

namespace Luttje\FilamentUserAttributes;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Tables\Columns\Column;
use Illuminate\Support\Facades\File;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

class FilamentUserAttributes
{
    protected static array | Closure $registeredResources = [];

    protected static ?array $cachedDiscoveredResources = null;

    /**
     * Register resources that can be configured with user attributes.
     * You can provide an associative array of resources, where the key
     * is the resource class and the value is the resource label to show
     * to users.
     *
     * You can also provide a closure that returns an array of resources
     * in the same format.
     *
     * Call this in your AppServiceProvider's boot function.
     */
    public static function registerResources(array | Closure $resources): void
    {
        if (is_array($resources)) {
            self::$registeredResources = array_merge(self::$registeredResources, $resources);
        } elseif ($resources instanceof Closure) {
            self::$registeredResources = $resources;
        }
    }

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

        if (!in_array(ConfiguresUserAttributes::class, class_uses_recursive($config))) {
            throw new \Exception("The resource '$resource' does not correctly use the ConfiguresUserAttributes trait");
        }

        return $config->getUserAttributeColumns($resource);
    }

    /**
     * Returns the user attribute fields.
     */
    public static function getUserAttributeFields(string $resource): array
    {
        $config = static::getUserAttributeConfig($resource);

        if (!in_array(ConfiguresUserAttributes::class, class_uses_recursive($config))) {
            throw new \Exception("The resource '$resource' does not use the ConfiguresUserAttributes trait");
        }

        return $config->getUserAttributeFields($resource);
    }

    /**
     * Returns the user attribute configuration model.
     */
    public static function getUserAttributeConfig(string $resource): ConfiguresUserAttributesContract
    {
        if (!in_array(UserAttributesConfigContract::class, class_implements($resource))) {
            throw new \Exception("The resource '$resource' does not implement the UserAttributesConfigContract interface.");
        }

        /** @var ?UserAttributesConfigContract */
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
    public static function getConfigurableResources()
    {
        $discoverPaths = config('filament-user-attributes.discover_resources');

        if ($discoverPaths === false) {
            $resources = self::$registeredResources;

            if ($resources instanceof Closure) {
                return $resources();
            }

            return $resources;
        }

        if (self::$cachedDiscoveredResources === null) {
            self::$cachedDiscoveredResources = self::discoverConfigurableResources($discoverPaths);
        }

        return self::$cachedDiscoveredResources;
    }

    /**
     * Discovers all resources that have the HasUserAttributesContract interface
     */
    public static function discoverConfigurableResources(array $paths): array
    {
        $resources = [];

        foreach ($paths as $targetPath) {
            $path = app_path($targetPath);

            if (!File::exists($path)) {
                continue;
            }

            $nameTransformer = config('filament-user-attributes.discovery_resource_name_transformer');

            $resourcesForPath = collect(File::allFiles($path))
                ->map(function ($file) use ($targetPath) {
                    $type = app()->getNamespace() . $targetPath . '\\' . $file->getRelativePathName();
                    $type = substr($type, 0, -strlen('.php'));

                    return $type;
                })
                ->filter(function ($type) {
                    if (!class_exists($type)) {
                        return false;
                    }

                    if (!in_array(\Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract::class, class_implements($type))) {
                        return false;
                    }

                    return true;
                })
                ->mapWithKeys(function ($type) use ($nameTransformer) {
                    return [$type => $nameTransformer($type)];
                })
                ->toArray();

            $resources = array_merge($resources, $resourcesForPath);
        }

        return $resources;
    }

    /**
     * Helper function to get label for a component.
     */
    private static function getComponentLabel($component, ?string $parentLabel = null): string
    {
        $label = $component->getLabel();

        if (!empty($label)) {
            return $parentLabel ? ($parentLabel . ' > ' . $label) : $label;
        }

        return $parentLabel ?? '';
    }

    /**
     * Gets all components and child components as a flat array of names with labels
     */
    public static function getAllFieldComponents(array $components, ?string $parentLabel = null): array
    {
        $namesWithLabels = [];

        foreach ($components as $component) {
            $label = self::getComponentLabel($component, $parentLabel);

            if ($component instanceof \Filament\Forms\Components\Field) {
                $namesWithLabels[] = [
                    'name' => $component->getName(),
                    'label' => $label,
                ];
            }

            if ($component instanceof Component) {
                $namesWithLabels = array_merge(
                    $namesWithLabels,
                    static::getAllFieldComponents(
                        $component->getChildComponents(),
                        $label
                    )
                );
            }
        }

        return $namesWithLabels;
    }

    /**
     * Gets all columns as a flat array of names with labels
     */
    public static function getAllTableColumns(array $columns): array
    {
        $namesWithLabels = [];

        foreach ($columns as $column) {
            $label = self::getComponentLabel($column);
            $namesWithLabels[] = [
                'name' => $column->getName(),
                'label' => $label,
            ];
        }

        return $namesWithLabels;
    }

    /**
     * Search the components and child components until the component with the given name is found,
     * then add the given component after it.
     */
    public static function addFieldBesidesField(array $components, string $siblingComponentName, string $position, Component $componentToAdd, ?string $parentLabel = null): array
    {
        $newComponents = [];

        foreach ($components as $component) {
            $label = self::getComponentLabel($component, $parentLabel);

            $newComponents[] = $component;

            if ($component instanceof \Filament\Forms\Components\Field
            && $label === $siblingComponentName) {
                if ($position === 'before') {
                    array_splice($newComponents, count($newComponents) - 1, 0, [$componentToAdd]);
                } elseif($position === 'after') {
                    $newComponents[] = $componentToAdd;
                } else {
                    throw new \Exception("Invalid position '$position' given.");
                }
            }

            if ($component instanceof Component) {
                $childComponents = static::addFieldBesidesField(
                    $component->getChildComponents(),
                    $siblingComponentName,
                    $position,
                    $componentToAdd,
                    $label
                );

                $component->childComponents($childComponents);
            }
        }

        return $newComponents;
    }

    /**
     * Search the columns and child columns until the column with the given name is found,
     * unlike with forms, tables simply have columns in a flat array next to each other.
     */
    public static function addColumnBesidesColumn(array $columns, string $siblingColumnName, string $position, Column $columnToAdd): array
    {
        $newColumns = [];

        foreach ($columns as $column) {
            $label = self::getComponentLabel($column);
            $newColumns[] = $column;

            if ($label === $siblingColumnName) {
                if ($position === 'before') {
                    array_splice($newColumns, count($newColumns) - 1, 0, [$columnToAdd]);
                } elseif ($position === 'after') {
                    $newColumns[] = $columnToAdd;
                } else {
                    throw new \Exception("Invalid position '$position' given.");
                }
            }
        }

        return $newColumns;
    }

    /**
     * Merges the custom fields into the given form schema.
     */
    public static function mergeCustomFormFields(array $fields, string $resource): array
    {
        $customFields = collect(FilamentUserAttributes::getUserAttributeFields($resource));

        for ($i = 0; $i < $customFields->count(); $i++) {
            $customField = $customFields->pop();

            if (!isset($customField['ordering'])
                || $customField['ordering']['sibling'] === null) {
                $customFields->prepend($customField);
                continue;
            }

            $fields = self::addFieldBesidesField(
                $fields,
                $customField['ordering']['sibling'],
                $customField['ordering']['position'],
                $customField['field']
            );
        }

        return array_merge($fields, $customFields->pluck('field')->toArray());
    }

    /**
     * Merges the custom columns into the given table schema.
     */
    public static function mergeCustomTableColumns(array $columns, $resource): array
    {
        $customColumns = collect(FilamentUserAttributes::getUserAttributeColumns($resource));

        for ($i = 0; $i < $customColumns->count(); $i++) {
            $customColumn = $customColumns->pop();

            if (!isset($customColumn['ordering'])
                || $customColumn['ordering']['sibling'] === null) {
                $customColumns->prepend($customColumn);
                continue;
            }

            $columns = self::addColumnBesidesColumn(
                $columns,
                $customColumn['ordering']['sibling'],
                $customColumn['ordering']['position'],
                $customColumn['column']
            );
        }

        return array_merge($columns, $customColumns->pluck('column')->toArray());
    }

    /**
     * Converts a class name to a human readable label by getting
     * the last part of the name and adding spaces between words.
     */
    public static function classNameToLabel(string $className): string
    {
        $className = basename($className);
        $className = preg_replace('/(?<!^)[A-Z]/', ' $0', $className);
        $className = preg_replace('/Resource$/', 'Page', $className);

        return $className;
    }
}
