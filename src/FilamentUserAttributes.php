<?php

namespace Luttje\FilamentUserAttributes;

use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

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

                if (!in_array(\Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract::class, class_implements($type))) {
                    return false;
                }

                return true;
            })
            ->toArray();

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
    public static function addFieldBesidesField(array $components, string $siblingComponentName, bool $before, Component $componentToAdd, ?string $parentLabel = null): array
    {
        $newComponents = [];

        foreach ($components as $component) {
            $label = self::getComponentLabel($component, $parentLabel);

            $newComponents[] = $component;

            if ($component instanceof \Filament\Forms\Components\Field
            && $label === $siblingComponentName) {
                if (!$before) {
                    $newComponents[] = $componentToAdd;
                } else {
                    array_splice($newComponents, count($newComponents) - 1, 0, [$componentToAdd]);
                }
            }

            if ($component instanceof Component) {
                $childComponents = static::addFieldBesidesField(
                    $component->getChildComponents(),
                    $siblingComponentName,
                    $before,
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
    public static function addColumnBesidesColumn(array $columns, string $siblingColumnName, bool $before, Column $columnToAdd): array
    {
        $newColumns = [];

        foreach ($columns as $column) {
            $label = self::getComponentLabel($column);
            $newColumns[] = $column;

            if ($label === $siblingColumnName) {
                if (!$before) {
                    $newColumns[] = $columnToAdd;
                } else {
                    array_splice($newColumns, count($newColumns) - 1, 0, [$columnToAdd]);
                }
            }
        }

        return $newColumns;
    }

    /**
     * Merges the custom fields into the form.
     */
    public static function mergeCustomFormFields(array $components, string $resource): array
    {
        $customFields = FilamentUserAttributes::getUserAttributeFields($resource);

        $appendFields = [];

        foreach ($customFields as $customField) {
            if ($customField['ordering']['sibling'] === null) {
                $appendFields[] = $customField['field'];
                continue;
            }
            $components = self::addFieldBesidesField(
                $components,
                $customField['ordering']['sibling'],
                $customField['ordering']['before'],
                $customField['field']
            );
        }

        return array_merge($components, $appendFields);
    }

    /**
     * Merges the custom columns into the table.
     * Note that Filament has already set the columns (unlike with forms where they get reset).
     * Because of this we should not re-insert the existing columns, but instead append the new ones and then
     * re-order them within the column layout.
     */
    public static function mergeCustomTableColumns(array $columns, $resource): array
    {
        $customColumns = FilamentUserAttributes::getUserAttributeColumns($resource);
        $appendColumns = [];

        foreach ($customColumns as $customColumn) {
            if ($customColumn['ordering']['sibling'] === null) {
                $appendColumns[] = $customColumn['column'];
                continue;
            }
            $columns = self::addColumnBesidesColumn(
                $columns,
                $customColumn['ordering']['sibling'],
                $customColumn['ordering']['before'],
                $customColumn['column']
            );
        }

        return array_merge($columns, $appendColumns);
    }
}
