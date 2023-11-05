<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\File;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;

class ManageUserAttributeConfigs extends ManageRecords
{
    protected static string $resource = UserAttributeConfigResource::class;

    protected $listeners = ['managedUserAttributes' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Manage user attributes')
                ->steps(self::getSteps())
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $model = $data['model_type'];

                    // TODO: Move this to the package (duplicate code of HasUserAttributesResource)
                    if (! in_array(HasUserAttributesContract::class, class_implements($model))) {
                        throw new \Exception('The model does not implement the HasUserAttributesContract interface.');
                    }

                    $config = $model::getUserAttributesConfig();

                    if (! $config) {
                        throw new \Exception('The model does override the getUserAttributesConfig() method, or the method does not return a model.');
                    }

                    $config->userAttributesConfigs()->updateOrCreate(
                        [
                            'model_type' => $model,
                            // TODO: Use a scope for always set this:
                            'owner_type' => get_class($config),
                        ],
                        [
                            'config' => $data['user_attribute_configs'],
                        ]
                    );

                    Notification::make()
                        ->title('Created successfully')
                        ->success()
                        ->persistent()
                        ->send();

                    $this->dispatch('managedUserAttributes');
                }),
        ];
    }

    /**
     * Finds all models that have the HasUserAttributesContract interface
     */
    public static function getModelsThatImplementHasUserAttributesContract()
    {
        // TODO: Make model paths configurable in package config
        $path = app_path('Models');
        $models = collect(File::allFiles($path))
            ->map(function ($file) {
                $model = 'App\\Models\\' . str_replace('/', '\\', $file->getRelativePathname());
                $model = substr($model, 0, -strlen('.php'));

                return $model;
            })
            ->filter(function ($model) {
                if (! in_array(\Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract::class, class_implements($model))) {
                    return false;
                }

                if (empty($model::getUserAttributesConfig())) {
                    return false;
                }

                return true;
            })
            ->toArray();

        return $models;
    }

    protected static function getSteps()
    {
        $models = self::getModelsThatImplementHasUserAttributesContract();
        sort($models);

        $steps = [];

        $steps[] = Step::make('Select the model type')
            ->schema([
                Forms\Components\Select::make('model_type')
                    ->options(array_combine($models, $models))
                    ->label(ucfirst(__('validation.attributes.model_type')))
                    ->required(),
            ])
            ->afterValidation(function (Get $get, Set $set) {
                $model = $get('model_type');
                $config = $model::getUserAttributesConfig();
                $userAttributeConfigs = $config->userAttributesConfigs()->where('model_type', $model)->first();

                $set('user_attribute_configs', $userAttributeConfigs?->config->toArray() ?? []);
            });

        $steps[] = Step::make('Modify the list of attributes')
            ->schema([
                Forms\Components\Repeater::make('user_attribute_configs')
                    ->schema([
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
                    ]),
            ]);

        return $steps;
    }
}
