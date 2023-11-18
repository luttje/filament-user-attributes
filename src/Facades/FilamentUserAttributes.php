<?php

namespace Luttje\FilamentUserAttributes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerResources(array|Closure $resources)
 * @method static void registerDefaultUserAttributeComponentFactories()
 * @method static array getUserAttributeColumns(string $resource)
 * @method static array getUserAttributeFields(string $resource)
 * @method static ConfiguresUserAttributesContract getUserAttributeConfig(string $resource)
 * @method static array getConfigurableResources(?bool $configuredOnly = true)
 * @method static array getConfigurableModels(?bool $configuredOnly = true)
 * @method static array discoverConfigurableResources(array $paths, bool $configuredOnly)
 * @method static array discoverConfigurableModels(array $paths, bool $configuredOnly)
 * @method static string findModelFilePath(string $model)
 * @method static string findResourceFilePath(string $resource)
 * @method static array getAllFieldComponents(array $components, ?string $parentLabel = null)
 * @method static array getAllTableColumns(array $columns)
 * @method static array addFieldBesidesField(array $components, string $siblingComponentName, string $position, Component $componentToAdd, ?string $parentLabel = null)
 * @method static array addColumnBesidesColumn(array $columns, string $siblingColumnName, string $position, Column $columnToAdd)
 * @method static array mergeCustomFormFields(array $fields, string $resource)
 * @method static array mergeCustomTableColumns(array $columns, $resource)
 * @method static string classNameToLabel(string $className)
 *
 * @see \Luttje\FilamentUserAttributes\FilamentUserAttributes
 */
class FilamentUserAttributes extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'filamentUserAttributes';
    }
}
