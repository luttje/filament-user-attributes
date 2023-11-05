<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

class UserAttributeConfigResource extends Resource
{
    protected static ?string $model = UserAttributeConfig::class;

    public static function getEloquentQuery(): Builder
    {
        $models = ManageUserAttributeConfigs::getModelsThatImplementHasUserAttributesContract();
        $query = parent::getEloquentQuery();

        foreach ($models as $model) {
            $config = $model::getUserAttributesConfig();

            if (! $config) {
                continue;
            }

            // TODO: Use a scope for always set this
            $query->orWhere(function ($query) use ($model, $config) {
                $query->where('model_type', $model)
                    ->where('owner_type', get_class($config))
                    ->where('owner_id', $config->id);
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')
                    ->label(ucfirst(__('validation.attributes.model_type'))),
                Tables\Columns\TextColumn::make('config')
                    ->formatStateUsing(function (ArrayObject $state) {
                        return __(':count custom attributes', ['count' => count($state)]);
                    }),
            ])
            ->actions([
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
        ];
    }
}
