<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources;

use Closure;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\EditUserAttributeConfig;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;
use UnitEnum;

class UserAttributeConfigResource extends Resource
{
    protected static ?string $model = UserAttributeConfig::class;

    protected static ?string $tenantOwnershipRelationshipName = 'owner';

    /**
     * Redeclared so that toggling it only affects this resource (and resources
     * extending it), instead of every Filament resource.
     *
     * Set this to true in your own resource to let every panel user manage
     * user attribute configurations without a policy.
     */
    protected static bool $shouldSkipAuthorization = false;

    /**
     * Denies everything unless a policy is registered for the config model.
     *
     * Filament allows all actions when a model has no policy. Since this
     * resource lets users change the forms and tables of other resources,
     * access must be granted explicitly: either through a policy, or by
     * setting `$shouldSkipAuthorization` to true.
     */
    public static function getAuthorizationResponse(string | UnitEnum $action, ?Model $record = null): Response
    {
        if (!static::shouldSkipAuthorization() && blank(Gate::getPolicyFor(static::getModel()))) {
            return Response::deny('No policy is registered for ' . static::getModel() . '. Register a policy or set $shouldSkipAuthorization to true on your resource.');
        }

        return parent::getAuthorizationResponse($action, $record);
    }

    /**
     * Limit the query to only the user attributes for the correct config (like a user or tenant).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $owners = [];

        // Besides the known resources, also consider resources that already
        // have a configuration. Those may not be discoverable (e.g. Livewire
        // components), but their configs must still be listed and editable.
        $resources = array_unique([
            ...array_keys(FilamentUserAttributes::getConfigurableResources()),
            ...UserAttributeConfig::query()->distinct()->pluck('resource_type')->all(),
        ]);

        foreach ($resources as $resource) {
            if (!is_string($resource) || !static::isConfigurableResource($resource)) {
                continue;
            }

            $config = $resource::getUserAttributesConfig();

            if (!$config) {
                continue;
            }

            $owners[get_class($config) . '|' . $config->getKey()] = $config;
        }

        // Fail closed: without a known owner nothing may be shown, otherwise
        // the configs of every owner (e.g. all tenants) would be exposed.
        if (empty($owners)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($owners) {
            foreach ($owners as $owner) {
                $query->orWhere(function (Builder $query) use ($owner) {
                    $query->where('owner_type', get_class($owner))
                        ->where('owner_id', $owner->getKey());
                });
            }
        });
    }

    /**
     * Whether the given class may be configured through this resource.
     *
     * The class name comes from the URL, so it must never be trusted: only
     * classes that opted in by implementing the UserAttributesConfigContract
     * are accepted. When resources are registered manually (discovery is
     * disabled) that list is authoritative.
     */
    protected static function isConfigurableResource(string $class): bool
    {
        if (!class_exists($class) || !is_subclass_of($class, UserAttributesConfigContract::class)) {
            return false;
        }

        if (FilamentUserAttributes::getResourceDiscoverPaths() === false) {
            return array_key_exists($class, FilamentUserAttributes::getConfigurableResources());
        }

        return true;
    }

    public static function resolveRecordRouteBinding(int | string $key, ?Closure $modifyQuery = null): ?Model
    {
        if (is_string($key) && static::isConfigurableResource($key)) {
            $resource = $key;
            $config = $resource::getUserAttributesConfig();

            if (!$config) {
                throw new \Exception("No valid user attributes config found for resource {$resource}. Did you forget to implement the `UserAttributesConfigContract` method `getUserAttributesConfig()`?");
            }

            $userAttributeConfigs = $config
                ->userAttributesConfigs()
                ->where('resource_type', $resource)
                ->first();

            if (!$userAttributeConfigs) {
                $modelType = FilamentUserAttributes::getModelFromResource($resource);

                $userAttributeConfigs = UserAttributeConfig::create([
                    'resource_type' => $resource,
                    'model_type' => $modelType,
                    'owner_type' => get_class($config),
                    'owner_id' => $config->id,
                    'config' => [],
                ]);
            }

            return $userAttributeConfigs;
        }

        return parent::resolveRecordRouteBinding($key, $modifyQuery);
    }

    public static function form(Schema $form): Schema
    {
        /** @var UserAttributeConfig|null */
        $model = $form->getRecord();

        if (! $model instanceof UserAttributeConfig) {
            return $form->components([])->columns(1);
        }

        return $form
            ->components([
                Forms\Components\Repeater::make('config')
                    ->addActionLabel(__('filament-user-attributes::user-attributes.add_attribute'))
                    ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.config')))
                    ->reorderable(false)
                    ->schema([
                        ...UserAttributeComponentFactoryRegistry::getConfigurationSchemas($model),
                    ])
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource_type')
                    ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.resource_type')))
                    ->formatStateUsing(function (string $state) {
                        $nameTransformer = config('filament-user-attributes.discovery_resource_name_transformer');
                        return $nameTransformer($state);
                    }),
                Tables\Columns\TextColumn::make('config')
                    ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.config')))
                    ->formatStateUsing(function (ArrayObject $state) {
                        $count = count($state);
                        return trans_choice('filament-user-attributes::user-attributes.amount', $count, ['amount' => $count]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        $callerClass = get_called_class();
        ManageUserAttributeConfigs::$injectedResource = $callerClass;
        EditUserAttributeConfig::$injectedResource = $callerClass;

        return [
            'index' => ManageUserAttributeConfigs::route('/'),
            'edit' => EditUserAttributeConfig::route('/{record}/edit'),
        ];
    }
}
