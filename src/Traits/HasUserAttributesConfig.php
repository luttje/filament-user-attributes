<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeInput;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

trait HasUserAttributesConfig
{
    /**
     * Returns the user attributes configuration models.
     */
    public function userAttributesConfigs(): HasMany
    {
        return $this->hasMany(UserAttributeConfig::class, 'owner_id')
            ->where('owner_type', static::class);
    }

    private function getUserAttributesConfig(string $model): UserAttributeConfig
    {
        return $this->userAttributesConfigs
            ->where('model_type', $model)
            ->first();
    }

    /**
     * Returns an array of columns to be added to the table.
     *
     * Fetches the desired model configs from the current config
     * model's (self) user attributes configuration.
     *
     * @return Column[]
     */
    public function getUserAttributeColumns(string $model): array
    {
        $columns = [];
        $userAttributesConfig = $this->getUserAttributesConfig($model);

        foreach ($userAttributesConfig->config as $userAttribute) {
            if($userAttribute['type'] === 'text') {
                $column = UserAttributeColumn::make($userAttribute['name']);
            } else {
                throw new \Exception("The user attribute type '{$userAttribute['type']}' is not yet supported.");
            }

            $columns[] = $column
                ->label($userAttribute['label'])
                ->sortable($userAttribute['sortable'] ?? false);
        }

        return $columns;
    }

    /**
     * Returns an array of fields to be added to the form.
     *
     * Fetches the desired model configs from the current config
     * model's (self) user attributes configuration.
     *
     * @return Field[]
     */
    public function getUserAttributeFields(string $model): array
    {
        $fields = [];
        $userAttributesConfig = $this->getUserAttributesConfig($model);

        foreach ($userAttributesConfig->config as $userAttribute) {
            if($userAttribute['type'] === 'text') {
                $field = UserAttributeInput::make($userAttribute['name']);
            } else {
                throw new \Exception("The user attribute type '{$userAttribute['type']}' is not yet supported.");
            }

            $fields[] = $field
                ->label($userAttribute['label']);
        }

        return $fields;
    }

    /**
     * Creates a user attribute configuration for the specified model.
     * Uses the specified label, type and extra options.
     */
    public function createUserAttributeConfig(
        string $model,
        string $name,
        string $label,
        string $type = 'text',
        array $options = []
    ): UserAttributeConfig {
        $config = $this->userAttributesConfigs()
            ->where('model_type', $model)
            ->where('name', $name)
            ->first();

        if (! $config) {
            $config = new UserAttributeConfig();
            $config->model_type = $model;
            $config->name = $name;
        }

        $config->label = $label;
        $config->type = $type;
        $config->options = $options;

        $this->userAttributesConfigs()->save($config);

        return $config;
    }
}
