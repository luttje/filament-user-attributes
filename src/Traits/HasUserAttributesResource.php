<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;

trait HasUserAttributesResource
{
    /**
     * Overrides the default table function to add user attributes.
     */
    public static function table(Table $table): Table
    {
        if (!method_exists(static::class, 'resourceTable')) {
            return $table;
        }

        $columns = static::resourceTable($table)
            ->getColumns();
        $model = $table->getModel();
        $customColumns = static::getUserAttributeColumns($model);

        foreach ($customColumns as $customColumn) {
            $columns[] = $customColumn;
        }

        $table->columns($columns);

        return $table;
    }

    /**
     * Overrides the default form function to add user attributes.
     */
    public static function form(Form $form): Form
    {
        if (!method_exists(static::class, 'resourceForm')) {
            return $form;
        }

        $components = static::resourceForm($form)
            ->getComponents();
        $model = $form->getModel();
        $customFields = static::getUserAttributeFields($model);

        // TODO: Recognize there being a tab component and add the fields to the tab (if the user wants to)
        foreach ($customFields as $customField) {
            $components[] = $customField;
        }

        $form->components($components);

        return $form;
    }

    /**
     * Returns the user attribute columns.
     */
    protected static function getUserAttributeColumns(string $model): array
    {
        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The model '$model' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeColumns($model);
    }

    /**
     * Returns the user attribute fields.
     */
    protected static function getUserAttributeFields(string $model): array
    {
        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (!in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception("The model '$model' does not use the HasUserAttributesConfig trait");
        }

        return $config->getUserAttributeFields($model);
    }

    /**
     * Returns the user attribute configuration model.
     */
    protected static function getUserAttributeConfig(string $model): HasUserAttributesConfigContract
    {
        if (!in_array(HasUserAttributesContract::class, class_implements($model))) {
            throw new \Exception("The model '$model' does not implement the HasUserAttributesContract interface.");
        }

        /** @var ?HasUserAttributesContract */
        $model = $model;
        $config = $model::getUserAttributesConfig();

        if ($config === null) {
            throw new \Exception("The model '$model' did not return a configuration model from the getUserAttributesConfig function (or it was null).");
        }

        return $config;
    }
}
