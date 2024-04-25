<?php

namespace Luttje\FilamentUserAttributes\Filament;

use Closure;
use Filament\Forms;
use Filament\Forms\Get;
use Luttje\FilamentUserAttributes\EloquentHelper;
use Luttje\FilamentUserAttributes\EloquentHelperRelationshipInfo;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

class UserAttributeComponentFactoryRegistry
{
    protected static $factories = [];

    protected static $relatedAmountMap = [
        '__self' => 1,
        'belongsTo' => 1,
        'belongsToMany' => 99,
        'hasMany' => 99,
        'hasManyThrough' => 99,
        'hasOne' => 1,
        'hasOneThrough' => 1,
        'morphMany' => 99,
        'morphOne' => 1,
        'morphTo' => 1,
        'morphToMany' => 99,
        'morphedByMany' => 99,
    ];

    public static function register(string $type, string $factory): void
    {
        static::$factories[$type] = $factory;
    }

    public static function getFactory(string $type, string $modelType): UserAttributeComponentFactoryInterface
    {
        if (!isset(static::$factories[$type])) {
            throw new \Exception("Factory for type {$type} not registered.");
        }

        return new static::$factories[$type]($modelType);
    }

    public static function getRegisteredTypes(): array
    {
        return array_keys(static::$factories);
    }

    /**
     * Looks at the config resource and gets the model relations that can be inherited from.
     * It can only inherit from related models whose resource is also configurable.
     */
    public static function getInheritRelationOptions(UserAttributeConfig $configModel): array
    {
        // TODO: Clean this up, possibly moving it somewhere else
        $resources = FilamentUserAttributes::getConfigurableResources();
        $modelsMappedToResources = FilamentUserAttributes::getResourcesByModel();

        $model = $configModel->model_type;
        $relations = [];

        $self = new EloquentHelperRelationshipInfo();
        $self->name = '__self';
        $self->relationTypeShort = '__self';
        $self->relatedType = $model;
        $relations[] = $self;

        $relations = [...$relations, ...EloquentHelper::discoverRelations($model)];

        $options = [];
        $nameTransformer = config('filament-user-attributes.discovery_model_name_transformer');

        foreach ($relations as $relation) {
            $relatedModel = $relation->relatedType;
            $relatedResource = $modelsMappedToResources[$relatedModel] ?? null;

            if (!$relatedResource) {
                continue;
            }

            if (!isset(static::$relatedAmountMap[$relation->relationTypeShort])) {
                trigger_error("Relation type {$relation->relationTypeShort} not found in relatedAmountMap", E_USER_WARNING);

                continue;
            }

            $relationAmount = static::$relatedAmountMap[$relation->relationTypeShort];
            $languageKey = 'filament-user-attributes::user-attributes.inherit_relation_option_label';

            if ($relation->name === '__self') {
                $languageKey = 'filament-user-attributes::user-attributes.inherit_relation_option_label_self';
            }

            $options[$relation->name] = __($languageKey, [
                'related_name' => $resources[$relatedResource],
                'resource' => $nameTransformer($model),
                'relationship' => __('filament-user-attributes::user-attributes.relationships.' . $relation->relationTypeShort),
                'related_resource' => $nameTransformer($relatedModel, $relationAmount),
            ]);
        }

        return $options;
    }

    public static function getConfigurationSchemas(UserAttributeConfig $configModel): array
    {
        $schemas = [];

        $schemas[] = Forms\Components\Fieldset::make('common')
            ->label(ucfirst(__('filament-user-attributes::user-attributes.common')))
            ->schema(function () {
                return [
                    // TODO: Make configs for these, so developers can tweak which default fields are shown
                    Forms\Components\TextInput::make('name')
                        ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.name')))
                        ->required()
                        ->rules([
                            function (Get $get) {
                                $otherNames = $get('../../config.*.name');

                                return function (string $attribute, $value, Closure $fail) use ($otherNames) {
                                    $userAttributeConfigs = collect($otherNames)->filter(function ($item) use ($value) {
                                        return $item === $value;
                                    });

                                    if ($userAttributeConfigs->count() > 1) {
                                        $fail(__('filament-user-attributes::user-attributes.name_already_exists'));
                                    }
                                };
                            },
                        ])
                        ->helperText(ucfirst(__('filament-user-attributes::user-attributes.name_help')))
                        ->maxLength(255),
                    Forms\Components\Select::make('type')
                        ->options(array_combine(static::getRegisteredTypes(), static::getRegisteredTypes()))
                        ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.type')))
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('label')
                        ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.label')))
                        ->required()
                        ->maxLength(255),
                ];
            });

        $inheritRelationOptions = static::getInheritRelationOptions($configModel);

        $schemas[] = Forms\Components\Fieldset::make(__('filament-user-attributes::user-attributes.default_value_config'))
            ->schema([
                Forms\Components\Checkbox::make('required')
                    ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.required'))),

                Forms\Components\Grid::make()
                    ->columns([
                        'lg' => 3
                    ])
                    ->schema([
                        Forms\Components\Checkbox::make('inherit')
                            ->label(ucfirst(__('filament-user-attributes::user-attributes.inherit')))
                            ->helperText(ucfirst(__('filament-user-attributes::user-attributes.inherit_help')))
                            ->live(),
                        Forms\Components\Select::make('inherit_relation')
                            ->options($inheritRelationOptions)
                            ->label(ucfirst(__('filament-user-attributes::user-attributes.inherit_relation')))
                            ->required(fn (Get $get) => $get('inherit'))
                            ->disabled(fn (Get $get) => !$get('inherit'))
                            ->live(),
                        Forms\Components\Select::make('inherit_attribute')
                            ->options(function (Get $get) use ($configModel) {
                                $inheritRelationName = $get('inherit_relation');

                                if (!$inheritRelationName) {
                                    return [];
                                }

                                if ($inheritRelationName === '__self') {
                                    $inheritRelatedModelType = $configModel->model_type;
                                } else {
                                    $model = $configModel->model_type;
                                    $inheritRelationInfo = EloquentHelper::getRelationInfo($model, $inheritRelationName);

                                    if (!$inheritRelationInfo) {
                                        return [];
                                    }

                                    $inheritRelatedModelType = $inheritRelationInfo->relatedType;
                                }

                                $resource = FilamentUserAttributes::getResourcesByModel()
                                    ->filter(function ($class, $model) use ($inheritRelatedModelType) {
                                        return $model === $inheritRelatedModelType;
                                    })
                                    ->first();

                                if (!$resource) {
                                    return [];
                                }

                                $attributes = $resource::getAllFieldComponents();
                                $attributes = array_combine(array_column($attributes, 'statePath'), array_column($attributes, 'label'));
                                return $attributes;
                            })
                            ->label(ucfirst(__('filament-user-attributes::user-attributes.inherit_attribute')))
                            ->required(fn (Get $get) => $get('inherit'))
                            ->disabled(fn (Get $get) => !$get('inherit')),
                    ])
            ]);

        $customConfigFields = FilamentUserAttributes::getUserAttributeConfigComponents($configModel);

        foreach ($customConfigFields as $customConfigField) {
            $schemas[] = $customConfigField;
        }

        foreach (static::$factories as $type => $factoryClass) {
            /** @var UserAttributeComponentFactoryInterface */
            $factory = new $factoryClass($configModel->model_type);
            $factorySchema = $factory->makeConfigurationSchema();

            $schemas[] = Forms\Components\Fieldset::make('customizations_for_' . $type)
                ->label(ucfirst(__('filament-user-attributes::user-attributes.customizations_for', ['type' => $type])))
                ->statePath('customizations')
                ->schema($factorySchema)
                ->mutateDehydratedStateUsing(function (Get $get, $state) use ($type, $factorySchema) {
                    if ($get('type') !== $type) {
                        return null;
                    }

                    // Unset all names that are not in the schema
                    // TODO: Why doesn't filament just ignore things that are hidden or disabled?
                    $names = collect($factorySchema)->map(function ($item) {
                        return $item->getName();
                    });
                    $state = collect($state)
                        ->filter(function ($value, $name) use ($names) {
                            return $names->contains($name);
                        })
                        ->toArray();

                    return $state;
                })
                ->hidden(fn (Get $get) => $get('type') !== $type || count($factorySchema) === 0)
                ->disabled(fn (Get $get) => $get('type') !== $type || count($factorySchema) === 0);
        }

        $schemas[] = Forms\Components\Fieldset::make('ordering')
            ->label(ucfirst(__('filament-user-attributes::user-attributes.ordering')))
            ->schema([
                Forms\Components\Fieldset::make('ordering_form')
                    ->label(ucfirst(__('filament-user-attributes::user-attributes.ordering_form')))
                    ->schema(function () use ($configModel) {
                        return [
                            Forms\Components\Select::make('order_position_form')
                                ->options([
                                    'before' => __('filament-user-attributes::user-attributes.attributes.order_position_before'),
                                    'after' => __('filament-user-attributes::user-attributes.attributes.order_position_after'),
                                    'hidden' => __('filament-user-attributes::user-attributes.attributes.order_position_hidden'),
                                ])
                                ->placeholder(ucfirst(__('filament-user-attributes::user-attributes.attributes.order_sibling_at_end')))
                                ->selectablePlaceholder()
                                ->live()
                                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.order_position')))
                                ->required(function (Get $get) {
                                    $sibling = $get('order_sibling_form');
                                    return $sibling !== null && $sibling !== '';
                                }),
                            Forms\Components\Select::make('order_sibling_form')
                                ->selectablePlaceholder()
                                ->live()
                                ->disabled(function (Get $get) {
                                    return $get('order_position_form') === 'hidden'
                                        || $get('order_position_form') == null;
                                })
                                ->placeholder(ucfirst(__('filament-user-attributes::user-attributes.select_sibling')))
                                ->options(function () use ($configModel) {
                                    $fields = $configModel->resource_type::getFieldsForOrdering();
                                    $fields = array_combine(array_column($fields, 'label'), array_column($fields, 'label'));
                                    return $fields;
                                })
                                ->helperText(ucfirst(__('filament-user-attributes::user-attributes.order_sibling_help')))
                                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.order_sibling'))),
                        ];
                    }),
                Forms\Components\Fieldset::make('ordering_table')
                    ->label(ucfirst(__('filament-user-attributes::user-attributes.ordering_table')))
                    ->schema(function () use ($configModel) {
                        return [
                            Forms\Components\Select::make('order_position_table')
                                ->options([
                                    'before' => __('filament-user-attributes::user-attributes.attributes.order_position_before'),
                                    'after' => __('filament-user-attributes::user-attributes.attributes.order_position_after'),
                                    'hidden' => __('filament-user-attributes::user-attributes.attributes.order_position_hidden'),
                                ])
                                ->placeholder(ucfirst(__('filament-user-attributes::user-attributes.attributes.order_sibling_at_end')))
                                ->selectablePlaceholder()
                                ->live()
                                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.order_position')))
                                ->required(function (Get $get) {
                                    $sibling = $get('order_sibling_table');
                                    return $sibling !== null && $sibling !== '';
                                }),
                            Forms\Components\Select::make('order_sibling_table')
                                ->selectablePlaceholder()
                                ->live()
                                ->disabled(function (Get $get) {
                                    return $get('order_position_table') === 'hidden'
                                        || $get('order_position_table') == null;
                                })
                                ->placeholder(ucfirst(__('filament-user-attributes::user-attributes.select_sibling')))
                                ->options(function () use ($configModel) {
                                    $columns = $configModel->resource_type::getColumnsForOrdering();
                                    $columns = array_combine(array_column($columns, 'label'), array_column($columns, 'label'));
                                    return $columns;
                                })
                                ->helperText(ucfirst(__('filament-user-attributes::user-attributes.order_sibling_help')))
                                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.order_sibling'))),
                        ];
                    }),
            ]);

        return $schemas;
    }
}
