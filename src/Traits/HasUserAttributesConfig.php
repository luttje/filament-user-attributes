<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

trait HasUserAttributesConfig
{
    /**
     * Returns the user attributes configuration models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userAttributesConfigs()
    {
        return $this->hasMany(UserAttributeConfig::class, 'owner_id')
            ->where('owner_type', static::class);
    }

    /**
     * Returns an array of columns to be added to the table.
     *
     * Fetches the desired model configs from the current config
     * model's (self) user attributes configuration.
     *
     * @param string $model
     * @return Column[]
     */
    public function getUserAttributesColumns(string $model): array
    {
        $columns = [];

        $userAttributesConfigs = $this->userAttributesConfigs
            ->where('model_type', $model);

        foreach ($userAttributesConfigs as $userAttributesConfig) {
            $columns[] = TextColumn::make($userAttributesConfig->config['name'])
                ->label($userAttributesConfig->config['label'])
                ->sortable($userAttributesConfig->config['sortable'] ?? false);
        }

        return $columns;
    }

    /**
     * Creates a user attribute configuration for the specified model.
     * Uses the specified label, type and extra options.
     *
     * @param string $model
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     *
     * @return UserAttributeConfig
     */
    public function createUserAttributeConfig(
        string $model,
        string $name,
        string $label,
        string $type = 'text',
        array $options = []): UserAttributeConfig
    {
        $config = $this->userAttributesConfigs()
            ->where('model_type', $model)
            ->where('name', $name)
            ->first();

        if (!$config) {
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
