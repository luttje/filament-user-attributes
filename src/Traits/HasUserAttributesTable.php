<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig;

trait HasUserAttributesTable
{
    /**
     * Overrides the default table function to add user attributes.
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        $table = $table->columns(static::getUserAttributeColumns());
        return static::resourceTable($table);
    }

    /**
     * Returns the user attribute columns.
     *
     * @return array
     */
    protected static function getUserAttributeColumns(): array
    {
        $model = static::getModel();

        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception('The model does not use the HasUserAttributesConfig trait.');
        }

        return $config->getUserAttributesColumns($model);
    }

    /**
     * Returns the user attribute configuration model.
     *
     * @param HasUserAttributesContract $model
     * @return Model
     */
    protected static function getUserAttributeConfig(string $model): Model
    {
        if (!in_array(HasUserAttributesContract::class, class_implements($model))) {
            throw new \Exception('The model does not implement the HasUserAttributesContract interface.');
        }

        return $model::getUserAttributesConfig();
    }
}
