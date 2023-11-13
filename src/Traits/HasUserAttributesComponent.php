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
        $customColumns = FilamentUserAttributes::getUserAttributeColumns(self::class);

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

        FilamentUserAttributes::mergeCustomFormFields($form, $components, self::class);

        return $form;
    }

    /**
     * Calls the resourceForm function to get which fields exist.
     */
    public static function getFieldsForOrdering(): array
    {
        throw new \Exception('Not implemented');
        return [];
    }

    /**
     * Calls the resourceTable function to get which columns exist.
     */
    public static function getColumnsForOrdering(): array
    {
        throw new \Exception('Not implemented');
        return [];
    }
}
