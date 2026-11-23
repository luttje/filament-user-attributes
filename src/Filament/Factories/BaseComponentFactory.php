<?php

namespace Luttje\FilamentUserAttributes\Filament\Factories;

use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\EloquentHelper;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
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
                if (EloquentHelper::isHiddenAttribute($this->modelType, $userAttribute['inherit_attribute'])) {
                    return $default;
                }

                try {
                    $relatedField = $get($userAttribute['inherit_attribute']);
                } catch (\Error) {
                    $relatedField = null;
                }

                if ($relatedField !== null) {
                    return $relatedField;
                }

                return data_get($record ?? [], $userAttribute['inherit_attribute']);
            }

            // A related model chosen in the form (e.g. a select for 'customer_id')
            // is used first, so that changing it updates the inherited value.
            $inheritRelationInfo = EloquentHelper::getRelationInfo($this->modelType, $userAttribute['inherit_relation']);
            $relatedKey = null;

            foreach ($inheritRelationInfo?->getFormFieldNames() ?? [] as $fieldName) {
                try {
                    $relatedKey = $get($fieldName);
                } catch (\Error) {
                    $relatedKey = null;
                }

                if ($relatedKey) {
                    break;
                }
            }

            if ($relatedKey) {
                // The key comes from the client, so it's looked up with the scopes and authorization of the related resource
                $related = FilamentUserAttributes::findRecordToInheritFrom($inheritRelationInfo->relatedType, $relatedKey);
            } elseif ($record?->exists) {
                // Otherwise a saved record has the related model (even without a field in the form).
                // Filament 4 passes a new model instance as the record on create pages, hence the check if it exists.
                $related = data_get($record, $userAttribute['inherit_relation']);
            } else {
                return $default;
            }

            // Only a single related model can be inherited from (not one of many)
            if (!$related instanceof Model || EloquentHelper::isHiddenAttribute($related, $userAttribute['inherit_attribute'])) {
                return $default;
            }

            return data_get($related, $userAttribute['inherit_attribute']);
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

        // The JSON column syntax (`values->name`) lets Laravel write the right SQL for each database
        $jsonColumn = 'values->' . $userAttributeName;

        if (isset($customizations['is_searchable']) && $customizations['is_searchable'] === true) {
            $column->searchable(query: function (Builder $query, string $search) use ($jsonColumn): Builder {
                return $query->whereHas('userAttribute', function (Builder $query) use ($jsonColumn, $search) {
                    $query->where(function (Builder $subQuery) use ($jsonColumn, $search) {
                        $subQuery->where($jsonColumn, 'like', '%' . $search . '%');

                        // If search contains comma, also search with dot, because the database only stores JSON numbers with dots
                        if (str_contains($search, ',')) {
                            $subQuery->orWhere($jsonColumn, 'like', '%' . str_replace(',', '.', $search) . '%');
                        }
                    });
                });
            });
        }

        if (isset($customizations['is_sortable']) && $customizations['is_sortable'] === true) {
            $column->sortable(
                query: function (Builder $query, string $direction) use ($jsonColumn): Builder {
                    $model = $query->getModel();
                    $modelClass = $model->getMorphClass();
                    $modelTable = $model->getTable();
                    $modelKey = $model->getKeyName();
                    $value = $query->getQuery()->getGrammar()->wrap('user_attributes.' . $jsonColumn);
                    $isAscending = $direction !== 'desc';

                    return $query
                        ->select($modelTable . '.*') // Ensure we're selecting all columns from main table (or we get a `Missing required parameter` error)
                        ->leftJoin('user_attributes', function ($join) use ($modelClass, $modelTable, $modelKey) {
                            $join->on('user_attributes.model_id', '=', $modelTable . '.' . $modelKey)
                                ->where('user_attributes.model_type', '=', $modelClass);
                        })
                        // Records without a value come last when sorting ascending, and first when descending (on every database)
                        ->orderByRaw("CASE WHEN {$value} IS NULL THEN ? ELSE ? END", [
                            $isAscending ? 1 : 0,
                            $isAscending ? 0 : 1,
                        ])
                        ->orderBy('user_attributes.' . $jsonColumn, $isAscending ? 'asc' : 'desc');
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
                if (!$record?->exists) {
                    return $state;
                }

                if (!$record instanceof HasUserAttributesContract) {
                    throw new \LogicException('The model ' . get_class($record) . ' must implement ' . HasUserAttributesContract::class . ' to inherit user attribute values.');
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
