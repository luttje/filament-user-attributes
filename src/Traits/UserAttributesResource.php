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
     * Gets all field components by calling the `form` method on the instance
     */
    public static function getAllFieldComponents(): array
    {
        $form = Form::make(new FormsCapturer());
        $result = self::callInstanceOrStatic('form', $form);

        if (!$result) {
            return [];
        }

        $components = $result->getComponents();

        return FilamentUserAttributes::getAllFieldComponents($components);
    }

    /**
     * Gets all table columns by calling the `table` method on the instance
     */
    public static function getAllTableColumns(): array
    {
        $table = Table::make(new TablesCapturer());
        $result = self::callInstanceOrStatic('table', $table);

        if (!$result) {
            return [];
        }

        $columns = $result->getColumns();

        return FilamentUserAttributes::getAllTableColumns($columns);
    }

    /**
     * Gets the field components that should be used for ordering (without
     * injecting user attributes)
     */
    public static function getFieldsForOrdering(): array
    {
        self::$blockInjectUserAttributes = true;
        $fields = self::getAllFieldComponents();
        self::$blockInjectUserAttributes = false;

        return $fields;
    }

    /**
     * Gets the table columns that should be used for ordering (without
     * injecting user attributes)
     */
    public static function getColumnsForOrdering(): array
    {
        self::$blockInjectUserAttributes = true;
        $columns = self::getAllTableColumns();
        self::$blockInjectUserAttributes = false;

        return $columns;
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
