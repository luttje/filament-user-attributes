<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\EditUserAttributeConfig;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

class UserAttributeConfigResource extends Resource
{
    protected static ?string $model = UserAttributeConfig::class;

    protected static ?string $tenantOwnershipRelationshipName = 'owner';

    public static function getEloquentQuery(): Builder
    {
        $resources = FilamentUserAttributes::getConfigurableResources();
        $query = parent::getEloquentQuery();

        /** @var \Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract $resource */
        foreach ($resources as $resource => $_) {
            $config = $resource::getUserAttributesConfig();

            if (!$config) {
                continue;
            }

            // TODO: Use a scope for always set this
            $query->orWhere(function ($query) use ($resource, $config) {
                $query->where('resource_type', $resource)
                    ->where('owner_type', get_class($config))
                    ->where('owner_id', $config->getKey());
            });
        }

        return $query;
    }

    public static function resolveRecordRouteBinding(int | string $key): ?Model
    {
        if(class_exists($key)) {
            $resource = $key;
            $config = $resource::getUserAttributesConfig();
            $userAttributeConfigs = $config
                ->userAttributesConfigs()
                ->where('resource_type', $resource)
                ->first();

            if (!$userAttributeConfigs) {
                $userAttributeConfigs = UserAttributeConfig::create([
                    'resource_type' => $resource,
                    'owner_type' => get_class($config),
                    'owner_id' => $config->id,
                    'config' => [],
                ]);
            }

            return $userAttributeConfigs;
        }

        return parent::resolveRecordRouteBinding($key);
    }

    public static function form(Form $form): Form
    {
        /** @var UserAttributeConfig */
        $model = $form->model;
        return $form
            ->schema([
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
