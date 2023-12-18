<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Tables\Columns\Column;
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
        // TODO: SHould we support situations where a relation exists, but no form field is shown for it.
        // TODO: We currently only support relations that have a form field e.g: relation customer needs customer_id field
        return function (Get $get) use ($userAttribute, $default) {
            $relatedField = $get($userAttribute['inherit_relation']);
            $related = null;

            if (!$relatedField) {
                $relatedField = $get($userAttribute['inherit_relation'] . '_id');
            }

            if ($relatedField != null) {
                $model = $this->resource::getModel(); // TODO: Support Livewire components (which don't have the getModel method)
                $inheritRelationInfo = EloquentHelper::getRelationInfo($model, $userAttribute['inherit_relation']);
                $related = ($inheritRelationInfo->relatedType)::find($relatedField);
            }

            if ($related) {
                // Get user attributes differently
                if (str_starts_with($userAttribute['inherit_attribute'], 'user_attributes.')) {
                    $attribute = str_replace('user_attributes.', '', $userAttribute['inherit_attribute']);
                    $default = $related->user_attributes->{$attribute};
                } else {
                    $default = data_get($related, $userAttribute['inherit_attribute']);
                }
                // TODO: How do we handle relations (e.g: where statePath is customer.address.street)?
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
        $customizations = $userAttribute['customizations'] ?? [];
        $default = $customizations['default'] ?? false;

        if (isset($userAttribute['inherit']) && $userAttribute['inherit'] === true) {
            $default = $this->makeInheritedDefault($userAttribute, $default);
        }

        return $field
            ->label($userAttribute['label'])
            ->default($default);
    }
}
