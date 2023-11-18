<?php

namespace Luttje\FilamentUserAttributes;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\Column;
use Illuminate\Support\Facades\File;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

/**
 * Class FilamentUserAttributes
 *
 * This class provides functionality for managing user attributes in Filament admin
 * panels. It handles the registration and discovery of resources, user attribute
 * components, and facilitates the integration of custom fields and columns in
 * Filament resources.
 */
class FilamentUserAttributes
{
    /**
     * @var array|Closure List of registered resources or a closure that returns resources.
     */
    protected array | Closure $registeredResources = [];

    /**
     * @var array|null Cached list of discovered resources.
     */
    protected ?array $cachedDiscoveredResources = null;

    /**
     * @var string Path to the application directory.
     */
    protected string $appPath;

    /**
     * @var string Namespace of the application.
     */
    protected string $appNamespace;

    /**
     * Constructor for FilamentUserAttributes.
     *
     * @param string|null $appPath       Optional path to the application directory.
     * @param string|null $appNamespace  Optional namespace of the application.
     */
    public function __construct(string $appPath = null, string $appNamespace = null)
    {
        $this->appNamespace = $appNamespace ?? app()->getNamespace();
        $this->appPath = $appPath ?? app_path();

        $this->appNamespace = rtrim($this->appNamespace, '\\') . '\\';
        $this->appPath = rtrim($this->appPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

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
    public function registerResources(array | Closure $resources): void
    {
        if (config('filament-user-attributes.discover_resources') !== false) {
            throw new \Exception("You cannot register resources when the 'filament-user-attributes.discover_resources' config option is enabled. Set it to false.");
        }

        if (is_array($resources)) {
            $this->registeredResources = array_merge($this->registeredResources, $resources);
        } elseif ($resources instanceof Closure) {
            $this->registeredResources = $resources;
        }
    }

    /**
     * Registers all types of user attribute field factories.
     */
    public function registerDefaultUserAttributeComponentFactories(): void
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
    public function getUserAttributeColumns(string $resource): array
    {
        $config = $this->getUserAttributeConfig($resource);

        if (!in_array(ConfiguresUserAttributes::class, class_uses_recursive($config))) {
            throw new \Exception("The resource '$resource' does not correctly use the ConfiguresUserAttributes trait");
        }

        return $config->getUserAttributeColumns($resource);
    }

    /**
     * Returns the user attribute fields.
     */
    public function getUserAttributeFields(string $resource): array
    {
        $config = $this->getUserAttributeConfig($resource);

        if (!in_array(ConfiguresUserAttributes::class, class_uses_recursive($config))) {
            throw new \Exception("The resource '$resource' does not use the ConfiguresUserAttributes trait");
        }

        return $config->getUserAttributeFields($resource);
    }

    /**
     * Returns the user attribute configuration model.
     */
    public function getUserAttributeConfig(string $resource): ConfiguresUserAttributesContract
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
    public function getConfigurableResources($configured = true)
    {
        $discoverPaths = config('filament-user-attributes.discover_resources');

        if ($discoverPaths === false) {
            $resources = $this->registeredResources;

            if ($resources instanceof Closure) {
                return $resources();
            }

            return $resources;
        }

        if ($this->cachedDiscoveredResources === null) {
            $this->cachedDiscoveredResources = $this->discoverConfigurableResources($discoverPaths, $configured);
        }

        return $this->cachedDiscoveredResources;
    }

    /**
     * Discovers all resources that have the HasUserAttributesContract interface
     */
    public function discoverConfigurableResources(array $paths, bool $configured): array
    {
        $resources = [];

        foreach ($paths as $targetPath) {
            $path = $this->appPath . $targetPath;

            if (!File::exists($path)) {
                continue;
            }

            $nameTransformer = config('filament-user-attributes.discovery_resource_name_transformer');

            $resourcesForPath = collect(File::allFiles($path))
                ->map(function ($file) use ($targetPath) {
                    $type = $this->appNamespace . $targetPath . '\\' . $file->getRelativePathName();
                    $type = substr($type, 0, -strlen('.php'));

                    return $type;
                });

            // Note: this will autoload the models if $configured = true
            if ($configured) {
                $resourcesForPath = $resourcesForPath->filter(function ($type) {
                    if (!class_exists($type)) {
                        return false;
                    }

                    if (!in_array(\Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract::class, class_implements($type))) {
                        return false;
                    }

                    return true;
                });
            }

            $resourcesForPath = $resourcesForPath->mapWithKeys(function ($type) use ($nameTransformer) {
                    return [$type => $nameTransformer($type)];
                })
                ->toArray();

            $resources = array_merge($resources, $resourcesForPath);
        }

        return $resources;
    }

    /**
     * Discovers all models that could possibly be configured with user attributes.
     */
    public function getConfigurableModels($configured = true)
    {
        $discoverPaths = config('filament-user-attributes.discover_models');

        if ($discoverPaths === false) {
            return [];
        }

        return $this->discoverConfigurableModels($discoverPaths, $configured);
    }

    /**
     * Discovers all models that could possibly be configured with user attributes.
     */
    public function discoverConfigurableModels(array $paths, bool $configured): array
    {
        $models = [];

        foreach ($paths as $targetPath) {
            $path = $this->appPath . $targetPath;

            if (!File::exists($path)) {
                continue;
            }

            $modelsForPath = collect(File::allFiles($path))
                ->map(function ($file) use ($targetPath) {
                    $type = $this->appNamespace . $targetPath . '\\' . $file->getRelativePathName();
                    $type = substr($type, 0, -strlen('.php'));

                    return $type;
                });

            // Note: this will autoload the models if $configured = true
            if ($configured) {
                $modelsForPath = $modelsForPath->filter(function ($type) {
                    if (!class_exists($type)) {
                        return false;
                    }

                    if (!in_array(\Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract::class, class_implements($type))) {
                        return false;
                    }

                    return true;
                });
            }

            $models = array_merge($models, $modelsForPath->toArray());
        }

        return $models;
    }

    /**
     * Uses configured path discovery information to find the path for the given
     * model class
     */
    public function findModelFilePath(string $model): string
    {
        $discoverPaths = config('filament-user-attributes.discover_models');

        foreach ($discoverPaths as $targetPath) {
            $path = $this->appPath . $targetPath;

            if (!File::exists($path)) {
                continue;
            }

            $file = $path . DIRECTORY_SEPARATOR . class_basename($model) . '.php';

            if (File::exists($file)) {
                return $file;
            }
        }

        throw new \Exception("Could not find the file for model '$model'.");
    }

    /**
     * Helper function to get label for a component.
     */
    private function getComponentLabel($component, ?string $parentLabel = null): string
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
    public function getAllFieldComponents(array $components, ?string $parentLabel = null): array
    {
        $namesWithLabels = [];

        foreach ($components as $component) {
            $label = $this->getComponentLabel($component, $parentLabel);

            if ($component instanceof \Filament\Forms\Components\Field) {
                $namesWithLabels[] = [
                    'name' => $component->getName(),
                    'label' => $label,
                ];
            }

            if ($component instanceof Tabs
                || $component instanceof Tab
                || $component instanceof Section
            ) {
                $namesWithLabels = array_merge(
                    $namesWithLabels,
                    $this->getAllFieldComponents(
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
    public function getAllTableColumns(array $columns): array
    {
        $namesWithLabels = [];

        foreach ($columns as $column) {
            $label = $this->getComponentLabel($column);
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
    public function addFieldBesidesField(
        array $components,
        string $siblingComponentName,
        string $position,
        Component $componentToAdd,
        ?string $parentLabel = null,
        &$siblingFound = false
    ): array {
        $newComponents = [];

        foreach ($components as $component) {
            $label = $this->getComponentLabel($component, $parentLabel);

            $newComponents[] = $component;

            if ($component instanceof \Filament\Forms\Components\Field
            && $label === $siblingComponentName) {
                $siblingFound = true;
                if ($position === 'before') {
                    array_splice($newComponents, count($newComponents) - 1, 0, [$componentToAdd]);
                } elseif($position === 'after') {
                    $newComponents[] = $componentToAdd;
                }
            }

            if ($component instanceof Tabs
                || $component instanceof Tab
                || $component instanceof Section
            ) {
                $containerChildComponents = $component->getChildComponents();
                $childComponents = $this->addFieldBesidesField(
                    $containerChildComponents,
                    $siblingComponentName,
                    $position,
                    $componentToAdd,
                    $label,
                    $siblingFound
                );

                $component->childComponents($childComponents);
            }
        }

        if (!$siblingFound) {
            $newComponents[] = $componentToAdd;
        }

        return $newComponents;
    }

    /**
     * Search the columns and child columns until the column with the given name is found,
     * unlike with forms, tables simply have columns in a flat array next to each other.
     */
    public function addColumnBesidesColumn(array $columns, string $siblingColumnName, string $position, Column $columnToAdd): array
    {
        $newColumns = [];

        foreach ($columns as $column) {
            $label = $this->getComponentLabel($column);
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
    public function mergeCustomFormFields(array $fields, string $resource): array
    {
        $customFields = collect(FilamentUserAttributes::getUserAttributeFields($resource));

        for ($i = 0; $i < $customFields->count(); $i++) {
            $customField = $customFields->pop();

            if (!isset($customField['ordering'])
                || $customField['ordering']['sibling'] === null) {
                $customFields->prepend($customField);
                continue;
            }

            $fields = $this->addFieldBesidesField(
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
    public function mergeCustomTableColumns(array $columns, $resource): array
    {
        $customColumns = collect(FilamentUserAttributes::getUserAttributeColumns($resource));

        for ($i = 0; $i < $customColumns->count(); $i++) {
            $customColumn = $customColumns->pop();

            if (!isset($customColumn['ordering'])
                || $customColumn['ordering']['sibling'] === null) {
                $customColumns->prepend($customColumn);
                continue;
            }

            $columns = $this->addColumnBesidesColumn(
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
    public function classNameToLabel(string $className): string
    {
        if (method_exists($className, 'getModelLabel')) {
            $label = $className::getModelLabel();

            if (!empty($label)) {
                return $label . ucfirst(__('filament-user-attributes::user-attributes.suffix_page'));
            }
        }

        $className = class_basename($className);
        $className = preg_replace('/(?<!^)[A-Z]/', ' $0', $className);
        $className = preg_replace('/Resource$/', ucfirst(__('filament-user-attributes::user-attributes.suffix_page')), $className);

        return $className;
    }
}
