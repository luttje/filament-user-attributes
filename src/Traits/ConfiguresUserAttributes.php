<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

trait ConfiguresUserAttributes
{
    /**
     * Returns the user attributes configuration models.
     */
    public function userAttributesConfigs(): MorphMany
    {
        return $this->morphMany(UserAttributeConfig::class, 'owner');
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
     * Adds a user attribute to the configuration of the specified resource.
     *
     * The customizations depend on the type (e.g. `placeholder` for text).
     * The options configure the attribute itself, e.g:
     *
     *     [
     *         'required' => true,
     *         'order_position_form' => 'after', // 'before', 'after' or 'hidden'
     *         'order_sibling_form' => 'Name',   // Label of the field to order next to
     *         'order_position_table' => 'hidden',
     *         'inherit' => true,
     *         'inherit_relation' => 'customer', // Or '__self'
     *         'inherit_attribute' => 'name',
     *     ]
     */
    public function createUserAttributeConfig(
        string $resource,
        string $name,
        string $label,
        string $type = 'text',
        array $customizations = [],
        array $options = []
    ): UserAttributeConfig {
        $config = $this->userAttributesConfigs()
            ->where('resource_type', $resource)
            ->first();

        if (!$config) {
            $config = new UserAttributeConfig();
            $config->resource_type = $resource;
            $config->model_type = FilamentUserAttributes::getModelFromResource($resource);
        }

        $config->config = [
            ...($config->config?->getArrayCopy() ?? []),
            [
                ...$options,
                'name' => $name,
                'type' => $type,
                'label' => $label,
                'customizations' => $customizations,
            ],
        ];

        $this->userAttributesConfigs()->save($config);

        // So the new attribute shows up when getting the fields and columns
        $this->unsetRelation('userAttributesConfigs');

        return $config;
    }
}
