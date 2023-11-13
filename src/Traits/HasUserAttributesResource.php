<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Actions\Contracts\HasTable;
use Filament\Tables\Table;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;

trait HasUserAttributesResource
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
        $customFields = FilamentUserAttributes::getUserAttributeComponents(self::class);

        $appendComponents = [];

        foreach ($customFields as $customField) {
            if ($customField['ordering']['sibling'] === null) {
                $appendComponents[] = $customField['component'];
                continue;
            }
            $components = self::addComponentAfterComponent(
                $components,
                $customField['ordering']['sibling'],
                $customField['ordering']['before'],
                $customField['component']
            );
        }

        $components = array_merge($components, $appendComponents);

        $form->components($components);

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

        return static::getAllFieldComponents($components);
    }

    /**
     * Calls the resourceTable function to get which columns exist.
     */
    public static function getColumnsForOrdering(): array
    {
        return [];
    }

    // /**
    //  * Gets all components and child components as a flat array of names with labels
    //  */
    // public static function getAllFieldComponents(array $components, ?string $parentLabel = null): array
    // {
    //     $namesWithLabels = [];

    //     foreach ($components as $component) {
    //         $label = $component->getLabel();

    //         if (!empty($label)) {
    //             $label = $parentLabel ? ($parentLabel . ' > ' . $component->getLabel()) : $component->getLabel();
    //         } else {
    //             $label = $parentLabel;
    //         }

    //         if ($component instanceof \Filament\Forms\Components\Field) {
    //             $namesWithLabels[] = [
    //                 'name' => $component->getName(),
    //                 'label' => $label,
    //             ];
    //         }

    //         if ($component instanceof Component) {
    //             $namesWithLabels = array_merge(
    //                 $namesWithLabels,
    //                 static::getAllFieldComponents(
    //                     $component->getChildComponents(),
    //                     $label
    //                 )
    //             );
    //         }
    //     }

    //     return $namesWithLabels;
    // }

    /**
     * Helper function to get label for a component.
     */
    private static function getComponentLabel($component, ?string $parentLabel = null): string
    {
        $label = $component->getLabel();

        if (!empty($label)) {
            return $parentLabel ? ($parentLabel . ' > ' . $label) : $label;
        }

        return $parentLabel ?? '';
    }

    /**
     * Gets all components and child components as a flat array of names with labels
     */
    public static function getAllFieldComponents(array $components, ?string $parentLabel = null): array
    {
        $namesWithLabels = [];

        foreach ($components as $component) {
            $label = static::getComponentLabel($component, $parentLabel);

            if ($component instanceof \Filament\Forms\Components\Field) {
                $namesWithLabels[] = [
                    'name' => $component->getName(),
                    'label' => $label,
                ];
            }

            if ($component instanceof Component) {
                $namesWithLabels = array_merge(
                    $namesWithLabels,
                    static::getAllFieldComponents(
                        $component->getChildComponents(),
                        $label
                    )
                );
            }
        }

        return $namesWithLabels;
    }

    /**
     * Search the components and child components until the component with the given name is found,
     * then add the given component after it.
     */
    public static function addComponentAfterComponent(array $components, string $siblingComponentName, bool $before, Component $componentToAdd, ?string $parentLabel = null): array
    {
        $newComponents = [];

        foreach ($components as $component) {
            $label = static::getComponentLabel($component, $parentLabel);

            $newComponents[] = $component;

            if ($component instanceof \Filament\Forms\Components\Field
            && $label === $siblingComponentName) {
                if (!$before) {
                    $newComponents[] = $componentToAdd;
                } else {
                    array_splice($newComponents, count($newComponents) - 1, 0, [$componentToAdd]);
                }
            }

            if ($component instanceof Component) {
                $childComponents = static::addComponentAfterComponent(
                    $component->getChildComponents(),
                    $siblingComponentName,
                    $before,
                    $componentToAdd,
                    $label
                );

                $component->childComponents($childComponents);
            }
        }

        return $newComponents;
    }
}

/**
 * Shim class to capture the form components that are being configured.
 */
class FormsCapturer extends Component implements HasForms
{
    use InteractsWithForms;
}
