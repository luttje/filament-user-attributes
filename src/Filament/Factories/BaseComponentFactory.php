<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\EloquentHelper;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

abstract class BaseComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function __construct(
        protected string $resource
    ) {
        //
    }

    private function makeInheritedDefault(array $userAttribute, mixed $default = null): Closure
    {
        return function (?Model $record, Get $get) use ($userAttribute, $default) {
            if ($record !== null) {
                $default = data_get($record, $userAttribute['inherit_relation'] . '.' . $userAttribute['inherit_attribute']);
            } else {
                // TODO: Should we support situations where a relation exists, but no form field is shown for it.
                // TODO: We currently only support relations that have a form field e.g: relation customer needs customer(_id) field
                $relatedField = $get($userAttribute['inherit_relation']);

                if (!$relatedField) {
                    $relatedField = $get($userAttribute['inherit_relation'] . '_id');
                }

                if ($relatedField != null) {
                    $record = $this->resource::getModel(); // TODO: Support Livewire components (which don't have the getModel method)
                    $inheritRelationInfo = EloquentHelper::getRelationInfo($record, $userAttribute['inherit_relation']);
                    $related = ($inheritRelationInfo->relatedType)::find($relatedField);
                    $default = data_get($related, $userAttribute['inherit_attribute']);
                }
            }

            return $default;
        };
    }

    protected function setUpColumn(Column $column, array $userAttribute): Column
    {
        $customizations = $userAttribute['customizations'] ?? [];
        $default = $customizations['default'] ?? false;

        if (isset($userAttribute['inherit']) && $userAttribute['inherit'] === true) {
            $default = $this->makeInheritedDefault($userAttribute, $default);
        }

        return $column
            ->label($userAttribute['label'])
            ->default($default);
    }

    public function setUpField(Field $field, array $userAttribute): Field
    {
        $default = $this->makeDefaultValue($userAttribute) ?? null;

        if (isset($userAttribute['inherit']) && $userAttribute['inherit'] === true) {
            $default = $this->makeInheritedDefault($userAttribute, $default);
        }

        $field->required($userAttribute['required'] ?? false)
            ->statePath('user_attributes.' . $userAttribute['name'])
            ->label($userAttribute['label'])
            ->default($default);

        return $field;
    }
}
