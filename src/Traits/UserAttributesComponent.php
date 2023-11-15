<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;

trait UserAttributesComponent
{
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
     * Overrides the default table function to add user attributes.
     */
    public function table(Table $table): Table
    {
        if (!method_exists($this, 'resourceTable')) {
            return $table;
        }

        $columns = $this->resourceTable($table)
            ->getColumns();

        FilamentUserAttributes::mergeCustomTableColumns($table, $columns, self::class);

        return $table;
    }

    /**
     * Calls the resourceForm function to get which fields exist.
     */
    public static function getFieldsForOrdering(): array
    {
        $shim = app(self::class);
        if (!method_exists($shim, 'resourceForm')) {
            return [];
        }

        $form = Form::make(new FormsCapturer());
        $components = $shim->resourceForm($form)
            ->getComponents();

        return FilamentUserAttributes::getAllFieldComponents($components);
    }

    /**
     * Calls the resourceTable function to get which columns exist.
     */
    public static function getColumnsForOrdering(): array
    {
        $shim = app(self::class);
        if (!method_exists($shim, 'resourceTable')) {
            return [];
        }

        $table = Table::make(new TablesCapturer());
        $columns = $shim->resourceTable($table)
            ->getColumns();

        return FilamentUserAttributes::getAllTableColumns($columns);
    }
}
