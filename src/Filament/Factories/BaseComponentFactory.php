<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
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

        if (isset($customizations['is_limited']) && $customizations['is_limited'] === true) {
            $column->limit($customizations['limit'] ?? 50);
        }

        if (isset($customizations['wraps_text']) && $customizations['wraps_text'] === true) {
            $column->wrap();
        }

        $userAttributeName = $userAttribute['name'];

        if (isset($customizations['is_searchable']) && $customizations['is_searchable'] === true) {
            $column->searchable(query: function (Builder $query, string $search) use ($userAttributeName): Builder {
                $jsonPath = '$."' . str_replace('"', '\"', $userAttributeName) . '"';

                $query->whereHas('userAttribute', function (Builder $query) use ($jsonPath, $search) {
                    $query->where(function (Builder $subQuery) use ($jsonPath, $search) {
                        $subQuery->whereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(`values`, ?)) LIKE ?",
                            [$jsonPath, '%' . $search . '%']
                        );

                        // If search contains comma, also search with dot, because the database only stores JSON numbers with dots
                        if (strpos($search, ',') !== false) {
                            $searchWithDot = str_replace(',', '.', $search);
                            $subQuery->orWhereRaw(
                                "JSON_UNQUOTE(JSON_EXTRACT(`values`, ?)) LIKE ?",
                                [$jsonPath, '%' . $searchWithDot . '%']
                            );
                        }
                    });
                });
                return $query;
            });
        }

        if (isset($customizations['is_sortable']) && $customizations['is_sortable'] === true) {
            $column->sortable(
                query: function (Builder $query, string $direction) use ($userAttributeName): Builder {
                    $jsonPath = '$."' . str_replace('"', '\"', $userAttributeName) . '"';
                    $model = $query->getModel();
                    $modelClass = get_class($model);
                    $modelTable = $model->getTable();
                    $modelKey = $model->getKeyName();
                    $nullsFirst = $direction === 'desc';

                    return $query
                        ->select($modelTable . '.*') // Ensure we're selecting all columns from main table (or we get a `Missing required parameter` error)
                        ->leftJoin('user_attributes', function ($join) use ($modelClass, $modelTable, $modelKey) {
                            $join->on('user_attributes.model_id', '=', $modelTable . '.' . $modelKey)
                                 ->where('user_attributes.model_type', '=', $modelClass);
                        })
                        ->orderByRaw("
                            CASE
                                WHEN user_attributes.id IS NULL THEN ?
                                ELSE ?
                            END,
                            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(user_attributes.values, ?)), '') {$direction}
                        ", [
                            $nullsFirst ? 0 : 1,  // NULL records priority
                            $nullsFirst ? 1 : 0,  // Non-NULL records priority
                            $jsonPath
                        ]);
                }
            );
        }

        $column->toggleable();

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

    public function makeConfigurationSchema(): array
    {
        return [
            Checkbox::make('is_searchable')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.is_searchable')))
                ->default(true),

            Checkbox::make('is_sortable')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.is_sortable')))
                ->default(true),

            Checkbox::make('wraps_text')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.wrap_text')))
                ->default(true),

            Checkbox::make('is_limited')
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.is_limited')))
                ->default(true)
                ->live()
                ->afterStateUpdated(function (Set $set, ?bool $state) {
                    if ($state) {
                        $set('limit', 50);
                    }
                }),

            TextInput::make('limit')
                ->numeric()
                ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.limit')))
                ->step(1)
                ->required()
                ->visible(fn (Get $get) => $get('is_limited'))
                ->dehydrated(fn (Get $get) => $get('is_limited'))
                ->default(50),
        ];
    }
}
