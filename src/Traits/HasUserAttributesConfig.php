<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\Filament\Forms\UserAttributeComponentFactoryRegistry;
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

    private function getUserAttributesConfigInstance(string $model): UserAttributeConfig
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
        $userAttributesConfig = $this->getUserAttributesConfigInstance($model);

        foreach ($userAttributesConfig->config as $userAttribute) {
            $type = $userAttribute['type'];
            $factory = UserAttributeComponentFactoryRegistry::getFactory($type);

            if (!isset($factory)) {
                throw new \Exception("The user attribute type '{$type}' is not yet supported.");
            }

            /** @var UserAttributeComponentFactoryInterface $factory */
            $factoryClass = $factory;
            $factory = new $factoryClass();

            $column = $factory->makeColumn($userAttribute);

            $columns[] = $column
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
    public function getUserAttributeComponents(string $model): array
    {
        $fields = [];
        $userAttributesConfig = $this->getUserAttributesConfigInstance($model);

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

            $field->statePath('user_attributes.' . $userAttribute['name']);
            $field->afterStateHydrated(static function (Component $component, string | array | null $state): void {
                $component->state(function (?Model $record) use ($component) {
                    if ($record === null) {
                        return null;
                    }

                    $key = $component->getName();

                    /** @var HasUserAttributesContract */
                    $record = $record;
                    return $record->user_attributes->$key;
                });
            });

            $fields[] = $field;
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
