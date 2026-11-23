<?php

namespace Luttje\FilamentUserAttributes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void resolveOwnerUsing(?\Closure $resolver)
 * @method static \Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract|null resolveOwner(string $resource)
 * @method static \Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract getResourceOwner(string $resource)
 * @method static void registerResources(array|\Closure $resources)
 * @method static void registerUserAttributeConfigComponent(\Filament\Schemas\Components\Component|\Closure $component)
 * @method static void registerDefaultUserAttributeComponentFactories()
 * @method static array getUserAttributeConfigComponents(\Luttje\FilamentUserAttributes\Models\UserAttributeConfig $configModel)
 * @method static array getUserAttributeColumns(string $resource)
 * @method static array getUserAttributeFields(string $resource)
 * @method static array mergeCustomFormFields(array $fields, string $resource)
 * @method static array mergeCustomTableColumns(array $columns, string $resource)
 * @method static array addColumnBesidesColumn(array $columns, string $siblingColumnName, string $position, \Filament\Tables\Columns\Column $columnToAdd)
 * @method static array addFieldBesidesField(array $components, string $siblingComponentName, string $position, \Filament\Schemas\Components\Component $componentToAdd, ?string $parentLabel = null)
 * @method static array getAllFieldComponents(array $components, ?string $parentLabel = null)
 * @method static array getAllTableColumns(array $columns)
 * @method static array|false getResourceDiscoverPaths()
 * @method static array getConfigurableResources(bool $configuredOnly = true)
 * @method static array getConfigurableModels(bool $configuredOnly = true)
 * @method static array discoverConfigurableResources(array $paths, bool $configuredOnly)
 * @method static array discoverConfigurableModels(array $paths, bool $configuredOnly)
 * @method static \Illuminate\Support\Collection getResourcesByModel()
 * @method static \Illuminate\Database\Eloquent\Model|null findRecordToInheritFrom(string $relatedType, mixed $key)
 * @method static string getModelFromResource(string $resource)
 * @method static string findModelFilePath(string $model)
 * @method static string findResourceFilePath(string $resource)
 * @method static string classNameToLabel(string $className)
 * @method static string classNameToModelLabel(string $className, int $amount = 1)
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
