<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;

trait HasUserAttributesComponent
{
    /**
     * Overrides the default table function to add user attributes.
     */
    public function table(Table $table): Table
    {
        if (!method_exists($this, 'resourceTable')) {
            return $table;
        }

        $columns = $this->resourceTable($table)
            ->getColumns();
        $model = $table->getModel();
        $customColumns = FilamentUserAttributes::getUserAttributeColumns($model);

        foreach ($customColumns as $customColumn) {
            $columns[] = $customColumn;
        }

        $table->columns($columns);

        return $table;
    }

    /**
     * Overrides the default form function to add user attributes.
     */
    public function form(Form $form): Form
    {
        if (!method_exists($this, 'resourceForm')) {
            return $form;
        }

        $components = $this->resourceForm($form)
            ->getComponents();
        $model = $form->getModel();
        $customFields = FilamentUserAttributes::getUserAttributeFields($model);

        // TODO: Recognize there being a tab component and add the fields to the tab (if the user wants to)
        foreach ($customFields as $customField) {
            $components[] = $customField;
        }

        $form->components($components);

        return $form;
    }
}
