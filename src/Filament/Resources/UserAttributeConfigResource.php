<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

class UserAttributeConfigResource extends Resource
{
    protected static ?string $model = UserAttributeConfig::class;

    // TODO: Move into package
    private static function getModelsThatImplementHasUserAttributesContract()
    {
        // Finds all models that have the HasUserAttributesContract interface
        // TODO: Make model paths configurable
        $path = app_path('Models');
        $models = collect(File::allFiles($path))
            ->map(function ($file) {
                $model = 'App\\Models\\' . str_replace('/', '\\', $file->getRelativePathname());
                $model = substr($model, 0, -strlen('.php'));

                return $model;
            })
            ->filter(function ($className) {
                return in_array(\Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract::class, class_implements($className));
            })
            ->toArray();

        return $models;
    }

    public static function form(Form $form): Form
    {
        $modelOptions = self::getModelsThatImplementHasUserAttributesContract();

        return $form
            ->schema([
                Select::make('model_type')
                    ->options(array_combine($modelOptions, $modelOptions))
                    ->label(ucfirst(__('validation.attributes.model')))
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(ucfirst(__('validation.attributes.name')))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->label(ucfirst(__('validation.attributes.label')))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Text',
                        // 'textarea' => 'Textarea',
                        // 'select' => 'Select',
                        // 'checkbox' => 'Checkbox',
                        // 'radio' => 'Radio',
                        // 'date' => 'Date',
                        // 'datetime' => 'Datetime',
                        // 'time' => 'Time',
                        // 'file' => 'File',
                        // 'image' => 'Image',
                        // 'password' => 'Password',
                        // 'email' => 'Email',
                        // 'number' => 'Number',
                        // 'tel' => 'Tel',
                        // 'url' => 'Url',
                        // 'color' => 'Color',
                        // 'range' => 'Range',
                        // 'search' => 'Search',
                        // 'hidden' => 'Hidden',
                    ])
                    ->label(ucfirst(__('validation.attributes.type')))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')
                    ->label(ucfirst(__('validation.attributes.model_type'))),
                Tables\Columns\TextColumn::make('config.name')
                    ->label(ucfirst(__('validation.attributes.name'))),
                Tables\Columns\TextColumn::make('config.label')
                    ->label(ucfirst(__('validation.attributes.label'))),
                Tables\Columns\TextColumn::make('config.type')
                    ->label(ucfirst(__('validation.attributes.type'))),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
