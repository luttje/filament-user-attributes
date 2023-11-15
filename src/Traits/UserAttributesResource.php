<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;

trait UserAttributesResource
{
    /**
     * Overrides the default form function to add user attributes.
     */
    public static function form(Form $form): Form
    {
        if (!method_exists(self::class, 'resourceForm')) {
            return $form;
        }

        $components = self::resourceForm($form)
            ->getComponents();

        FilamentUserAttributes::mergeCustomFormFields($form, $components, self::class);

        return $form;
    }

    /**
     * Overrides the default table function to add user attributes.
     */
    public static function table(Table $table): Table
    {
        if (!method_exists(self::class, 'resourceTable')) {
            return $table;
        }

        $columns = self::resourceTable($table)
            ->getColumns();

        FilamentUserAttributes::mergeCustomTableColumns($table, $columns, self::class);

        return $table;
    }

    /**
     * Calls the resourceForm function to get which fields exist.
     */
    public static function getFieldsForOrdering(): array
    {
        if (!method_exists(self::class, 'resourceForm')) {
            return [];
        }

        $form = Form::make(new FormsCapturer());
        $components = self::resourceForm($form)
            ->getComponents();

        return FilamentUserAttributes::getAllFieldComponents($components);
    }

    /**
     * Calls the resourceTable function to get which columns exist.
     */
    public static function getColumnsForOrdering(): array
    {
        if (!method_exists(self::class, 'resourceTable')) {
            return [];
        }

        $table = Table::make(new TablesCapturer());
        $columns = self::resourceTable($table)
            ->getColumns();

        return FilamentUserAttributes::getAllTableColumns($columns);
    }
}

/**
 * Shim class to capture the form components that are being configured.
 */
class FormsCapturer extends Component implements HasForms
{
    use InteractsWithForms;
}

/**
 * Shim class to capture the table columns that are being configured.
 */
class TablesCapturer extends Component implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
}

