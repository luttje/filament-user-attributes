<?php

namespace Luttje\FilamentUserAttributes\Filament;

use Closure;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Luttje\FilamentUserAttributes\Models\UserAttribute;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

class UserAttributeComponentFactoryRegistry
{
    protected static $factories = [];

    public static function register(string $type, string $factory): void
    {
        static::$factories[$type] = $factory;
    }

    public static function getFactory(string $type): UserAttributeComponentFactoryInterface
    {
        if (!isset(static::$factories[$type])) {
            throw new \Exception("Factory for type {$type} not registered.");
        }

        return new static::$factories[$type]();
    }

    public static function getRegisteredTypes(): array
    {
        return array_keys(static::$factories);
    }

    public static function getConfigurationSchemas(UserAttributeConfig $configModel): array
    {
        $schemas = [];

        $schemas[] = Forms\Components\Fieldset::make('common')
            ->label(ucfirst(__('filament-user-attributes::user-attributes.common')))
            ->schema(function () use ($configModel) {
                return [
                    // TODO: Make configs for these
                    Forms\Components\TextInput::make('name')
                        ->label(ucfirst(__('filament-user-attributes::attributes.name')))
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
                        // TODO:
                        // ->readOnly(function (Get $get, ?string $state) use ($configModel) {
                        //     if ($state === null) {
                        //         return false;
                        //     }

                        //     // Check if the state occurs as a name in the original configModel
                        //     $originalConfig = collect($configModel->getOriginal('config'))
                        //         ->filter(fn ($item) => $item['__is_concept'] !== true)
                        //         ->pluck('name');
                        //     return $originalConfig->contains($state);
                        // })
                        ->maxLength(255),
                    Forms\Components\Checkbox::make('required')
                        ->label(ucfirst(__('filament-user-attributes::attributes.required'))),
                    Forms\Components\TextInput::make('label')
                        ->label(ucfirst(__('filament-user-attributes::attributes.label')))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('type')
                        ->options(array_combine(static::getRegisteredTypes(), static::getRegisteredTypes()))
                        ->label(ucfirst(__('filament-user-attributes::attributes.type')))
                        ->required()
                        ->live(),
                ];
            });

        foreach (static::$factories as $type => $factoryClass) {
            /** @var UserAttributeComponentFactoryInterface */
            $factory = new $factoryClass();
            $factorySchema = $factory->makeConfigurationSchema();

            $schemas[] = Forms\Components\Fieldset::make('customizations_for_' . $type)
                ->label(ucfirst(__('filament-user-attributes::user-attributes.customizations_for', ['type' => $type])))
                ->schema($factorySchema)
                ->hidden(fn (Get $get) => $get('type') !== $type || count($factorySchema) === 0);
        }

        $schemas[] = Forms\Components\Fieldset::make('ordering')
            ->label(ucfirst(__('filament-user-attributes::user-attributes.ordering')))
            ->schema(function () use ($configModel) {
                return [
                    Forms\Components\Select::make('order_position')
                        ->options([
                            'before' => __('filament-user-attributes::attributes.order_position_before'),
                            'after' => __('filament-user-attributes::attributes.order_position_after'),
                        ])
                        ->label(ucfirst(__('filament-user-attributes::attributes.order_position')))
                        ->required(function (Get $get) {
                            $sibling = $get('order_sibling');
                            return $sibling !== null && $sibling !== '';
                        }),
                    Forms\Components\Select::make('order_sibling')
                        ->selectablePlaceholder()
                        ->live()
                        ->placeholder(ucfirst(__('filament-user-attributes::user-attributes.select_order')))
                        ->options(function () use ($configModel) {
                            $fields = $configModel->resource_type::getFieldsForOrdering();
                            $fields = array_combine(array_column($fields, 'label'), array_column($fields, 'label'));
                            return $fields;
                        })
                        ->helperText(ucfirst(__('filament-user-attributes::user-attributes.order_sibling_help')))
                        ->label(ucfirst(__('filament-user-attributes::attributes.order_sibling'))),
                ];
            });

        return $schemas;
    }
}
