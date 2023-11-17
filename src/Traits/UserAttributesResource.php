<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;

trait UserAttributesResource
{
    /**
     * Whether to block injecting user attributes (needed to only get other
     * attributes when getting them for ordering)
     */
    private static bool $blockInjectUserAttributes = false;

    /**
     * Inserts the user attributes into the given fields schema.
     */
    public static function withUserAttributeFields(array $schema): array
    {
        if (self::$blockInjectUserAttributes) {
            return $schema;
        }

        return FilamentUserAttributes::mergeCustomFormFields($schema, self::class);
    }

    /**
     * Inserts the user attributes into the given columns schema.
     */
    public static function withUserAttributeColumns(array $columns): array
    {
        if (self::$blockInjectUserAttributes) {
            return $columns;
        }

        return FilamentUserAttributes::mergeCustomTableColumns($columns, self::class);
    }

    /**
     * Helper to call the given method on the instance if it exists, or on the
     * static class if it doesn't.
     */
    private static function callInstanceOrStatic(string $methodName, $object)
    {
        if (!method_exists(self::class, $methodName)) {
            return null;
        }

        $reflectionMethod = new \ReflectionMethod(self::class, $methodName);

        if ($reflectionMethod->isStatic()) {
            return self::$methodName($object);
        } else {
            $instance = app(self::class);
            return $instance->$methodName($object);
        }
    }

    /**
     * Calls the `form` function to get which fields exist.
     */
    public static function getFieldsForOrdering(): array
    {
        $form = Form::make(new FormsCapturer());
        self::$blockInjectUserAttributes = true;
        $result = self::callInstanceOrStatic('form', $form);
        self::$blockInjectUserAttributes = false;

        if (!$result) {
            return [];
        }

        $components = $result->getComponents();

        return FilamentUserAttributes::getAllFieldComponents($components);
    }

    /**
     * Calls the `table` function to get which columns exist.
     */
    public static function getColumnsForOrdering(): array
    {
        $table = Table::make(new TablesCapturer());
        self::$blockInjectUserAttributes = true;
        $result = self::callInstanceOrStatic('table', $table);
        self::$blockInjectUserAttributes = false;

        if (!$result) {
            return [];
        }

        $columns = $result->getColumns();

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
