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
        $table = $table->columns(static::getUserAttributeColumns());

        return static::resourceTable($table);
    }

    /**
     * Overrides the default form function to add user attributes.
     */
    public static function form(Form $form): Form
    {
        $components = static::resourceForm($form)
            ->getComponents();
        $customFields = static::getUserAttributeFields();

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
    protected static function getUserAttributeColumns(): array
    {
        $model = static::getModel();

        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (! in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception('The model does not use the HasUserAttributesConfig trait.');
        }

        return $config->getUserAttributeColumns($model);
    }

    /**
     * Returns the user attribute fields.
     */
    protected static function getUserAttributeFields(): array
    {
        $model = static::getModel();

        /** @var HasUserAttributesConfig */
        $config = static::getUserAttributeConfig($model);

        if (! in_array(HasUserAttributesConfig::class, class_uses_recursive($config))) {
            throw new \Exception('The model does not use the HasUserAttributesConfig trait.');
        }

        return $config->getUserAttributeFields($model);
    }

    /**
     * Returns the user attribute configuration model.
     */
    protected static function getUserAttributeConfig(string $model): HasUserAttributesConfigContract
    {
        if (! in_array(HasUserAttributesContract::class, class_implements($model))) {
            throw new \Exception('The model does not implement the HasUserAttributesContract interface.');
        }

        /** @var HasUserAttributesContract */
        $model = $model;

        return $model::getUserAttributesConfig();
    }
}
