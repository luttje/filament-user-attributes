<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
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

    private function getUserAttributesConfigInstance(string $resource): ?UserAttributeConfig
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

        if (!$userAttributesConfig) {
            return $fields;
        }

        foreach ($userAttributesConfig->config as $userAttribute) {
            if (isset($userAttribute['order_position_form'])
                && $userAttribute['order_position_form'] === 'hidden') {
                continue;
            }

            $type = $userAttribute['type'];
            $model = FilamentUserAttributes::getModelFromResource($resource);
            $factory = UserAttributeComponentFactoryRegistry::getFactory($type, $model);

            if (!isset($factory)) {
                throw new \Exception("The user attribute type '{$type}' is not yet supported.");
            }

            $field = $factory->makeField($userAttribute);

            $fields[] = [
                'field' => $field,
                'ordering' => [
                    'position' => $userAttribute['order_position_form'] ?? null,
                    'sibling' => $userAttribute['order_sibling_form'] ?? null,
                ],
                'inheritance' => [
                    'enabled' => $userAttribute['inherit'] ?? false,
                    'relation' => $userAttribute['inherit_relation'] ?? null,
                    'attribute' => $userAttribute['inherit_attribute'] ?? null,
                ],
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

        if (!$userAttributesConfig) {
            return $columns;
        }

        foreach ($userAttributesConfig->config as $userAttribute) {
            if (isset($userAttribute['order_position_table'])
                && $userAttribute['order_position_table'] === 'hidden') {
                continue;
            }

            $type = $userAttribute['type'];
            $model = FilamentUserAttributes::getModelFromResource($resource);
            $factory = UserAttributeComponentFactoryRegistry::getFactory($type, $model);

            if (!isset($factory)) {
                throw new \Exception("The user attribute type '{$type}' is not yet supported.");
            }

            $columns[] = [
                'column' => $factory->makeColumn($userAttribute),
                'ordering' => [
                    'position' => $userAttribute['order_position_table'] ?? null,
                    'sibling' => $userAttribute['order_sibling_table'] ?? null,
                ],
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
        array $customizations = []
    ): UserAttributeConfig {
        $config = $this->userAttributesConfigs()
            ->where('resource_type', $resource)
            ->first();

        if (!$config) {
            $config = new UserAttributeConfig();
            $config->owner_type = get_class($this);
            $config->resource_type = $resource;
            $config->model_type = FilamentUserAttributes::getModelFromResource($resource);
        }

        $config->config = array_merge($config->config ?? [], [
            [
                'name' => $name,
                'type' => $type,
                'label' => $label,
                'customizations' => $customizations,
            ]
        ]);

        $this->userAttributesConfigs()->save($config);

        return $config;
    }
}
