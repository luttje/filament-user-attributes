<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\EloquentHelper;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryInterface;

abstract class BaseComponentFactory implements UserAttributeComponentFactoryInterface
{
    public function __construct(
        protected string $modelType
    ) {
        //
    }

    private function makeInheritedDefault(array $userAttribute, mixed $default = null): Closure
    {
        return function (?Model $record, Get $get) use ($userAttribute, $default) {
            if ($userAttribute['inherit_relation'] === '__self') {
                $relatedField = $get($userAttribute['inherit_attribute']);

                if ($relatedField !== null) {
                    return $relatedField;
                }

                return data_get($record ?? [], $userAttribute['inherit_attribute']);
            }

            if ($record !== null) {
                $default = data_get($record, $userAttribute['inherit_relation'] . '.' . $userAttribute['inherit_attribute']);
            } else {
                // TODO: Should we support situations where a relation exists, but no form field is shown for it.
                // TODO: We currently only support relations that have a form field e.g: relation customer needs customer(_id) field
                $relatedField = $get($userAttribute['inherit_relation']);

                if (!$relatedField) {
                    $relatedField = $get($userAttribute['inherit_relation'] . '_id');
                }

                if ($relatedField !== null) {
                    $record = $this->modelType;
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

            // Default won't work when user_attributes does not yet contain the value, therefor we also set it here
            $field->formatStateUsing(function (Component $component, ?Model $record, $state) use ($userAttribute, $default) {
                if ($record === null) {
                    return $state;
                }

                if (!in_array(HasUserAttributesContract::class, class_implements($record))) {
                    throw new \Exception('Record must implement HasUserAttributesContract');
                }

                /** @var HasUserAttributesContract $record */
                if (!$record->hasUserAttribute($userAttribute['name'])) {
                    // Force the default value
                    return $component->evaluate($default);
                }

                return $state;
            });
        }

        $field->required($userAttribute['required'] ?? false)
            ->statePath('user_attributes.' . $userAttribute['name'])
            ->label($userAttribute['label'])
            ->default($default);

        return $field;
    }
}
