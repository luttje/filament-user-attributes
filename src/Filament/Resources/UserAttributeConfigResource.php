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
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\EditUserAttributeConfig;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

class UserAttributeConfigResource extends Resource
{
    protected static ?string $model = UserAttributeConfig::class;

    public static function getEloquentQuery(): Builder
    {
        $resources = FilamentUserAttributes::getResourcesImplementingHasUserAttributesResourceContract();
        $query = parent::getEloquentQuery();

        foreach ($resources as $resource) {
            $config = $resource::getUserAttributesConfig();

            if (!$config) {
                continue;
            }

            // TODO: Use a scope for always set this
            $query->orWhere(function ($query) use ($resource, $config) {
                $query->where('resource_type', $resource)
                    ->where('owner_type', get_class($config))
                    ->where('owner_id', $config->id);
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

            return $userAttributeConfigs;
        }

        return parent::resolveRecordRouteBinding($key);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('config')
                    ->reorderable(false)
                    ->schema([
                        ...UserAttributeComponentFactoryRegistry::getConfigurationSchemas($form->model->resource_type),
                    ]),
                ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource_type')
                    ->label(ucfirst(__('filament-user-attributes::attributes.resource_type'))),
                Tables\Columns\TextColumn::make('config')
                    ->formatStateUsing(function (ArrayObject $state) {
                        return __(':count custom attributes', ['count' => count($state)]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUserAttributeConfigs::route('/'),
            'edit' => EditUserAttributeConfig::route('/{record}/edit'),
        ];
    }
}
