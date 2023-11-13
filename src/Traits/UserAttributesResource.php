<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;

trait UserAttributesResource
{
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
        return [];
    }
}

/**
 * Shim class to capture the form components that are being configured.
 */
class FormsCapturer extends Component implements HasForms
{
    use InteractsWithForms;
}
