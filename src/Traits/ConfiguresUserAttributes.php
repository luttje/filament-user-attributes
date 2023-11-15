<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

trait ConfiguresUserAttributes
{
    /**
     * Returns the user attributes configuration models.
     */
    public function userAttributesConfigs(): HasMany
    {
        return $this->hasMany(UserAttributeConfig::class, 'owner_id')
            ->where('owner_type', static::class);
    }

    private function getUserAttributesConfigInstance(string $resource): UserAttributeConfig
    {
        return $this->userAttributesConfigs
            ->where('resource_type', $resource)
            ->first();
    }

    /**
     * Returns an array of fields to be added to the form.
     *
     * Fetches the desired resource configs from the current config
     * model's (self) user attributes configuration.
     *
     * @return Field[]
     */
    public function getUserAttributeFields(string $resource): array
    {
        $fields = [];
        $userAttributesConfig = $this->getUserAttributesConfigInstance($resource);

        foreach ($userAttributesConfig->config as $userAttribute) {
            $type = $userAttribute['type'];
            $factory = UserAttributeComponentFactoryRegistry::getFactory($type);

            if (!isset($factory)) {
                throw new \Exception("The user attribute type '{$type}' is not yet supported.");
            }

            /** @var UserAttributeComponentFactoryInterface $factory */
            $factoryClass = $factory;
            $factory = new $factoryClass();

            $field = $factory->makeField($userAttribute);
            $field->required($userAttribute['required'] ?? false);
            $defaultValue = $factory->makeDefaultValue($userAttribute);

            $field->statePath('user_attributes.' . $userAttribute['name']);
            $field->afterStateHydrated(static function (Component $component, string | array | null $state) use ($defaultValue): void {
                $component->state(function (?Model $record) use ($component, $defaultValue) {
                    if ($record === null) {
                        return null;
                    }

                    $key = $component->getName();

                    /** @var HasUserAttributesContract */
                    $record = $record;

                    $value = $record->user_attributes->$key;

                    if ($value === null) {
                        return $defaultValue;
                    }

                    return $value;
                });
            });

            $fields[] = [
                'field' => $field,
                'ordering' => [
                    'before' => isset($userAttribute['order_position_form']) && $userAttribute['order_position_form'] === 'before',
                    'sibling' => $userAttribute['order_sibling_form'] ?? null,
                ]
            ];
        }

        return $fields;
    }

    /**
     * Returns an array of columns to be added to the table.
     *
     * Fetches the desired resource configs from the current config
     * model's (self) user attributes configuration.
     *
     * @return Column[]
     */
    public function getUserAttributeColumns(string $resource): array
    {
        $columns = [];
        $userAttributesConfig = $this->getUserAttributesConfigInstance($resource);

        foreach ($userAttributesConfig->config as $userAttribute) {
            $type = $userAttribute['type'];
            $factory = UserAttributeComponentFactoryRegistry::getFactory($type);

            if (!isset($factory)) {
                throw new \Exception("The user attribute type '{$type}' is not yet supported.");
            }

            /** @var UserAttributeComponentFactoryInterface $factory */
            $factoryClass = $factory;
            $factory = new $factoryClass();

            $column = $factory->makeColumn($userAttribute)
                ->sortable($userAttribute['sortable'] ?? false);

            $columns[] = [
                'column' => $column,
                'ordering' => [
                    'before' => isset($userAttribute['order_position_table']) && $userAttribute['order_position_table'] === 'before',
                    'sibling' => $userAttribute['order_sibling_table'] ?? null,
                ]
            ];
        }

        return $columns;
    }

    /**
     * Creates a user attribute configuration for the specified resource.
     * Uses the specified label, type and extra options.
     */
    public function createUserAttributeConfig(
        string $resource,
        string $name,
        string $label,
        string $type = 'text',
        array $options = []
    ): UserAttributeConfig {
        $config = $this->userAttributesConfigs()
            ->where('resource_type', $resource)
            ->where('name', $name)
            ->first();

        if (!$config) {
            $config = new UserAttributeConfig();
            $config->resource_type = $resource;
            $config->name = $name;
        }

        $config->label = $label;
        $config->type = $type;
        $config->options = $options;

        $this->userAttributesConfigs()->save($config);

        return $config;
    }
}
