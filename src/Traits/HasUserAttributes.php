<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Luttje\FilamentUserAttributes\Models\UserAttribute;
use Luttje\FilamentUserAttributes\UserAttributeValues;

/**
 * @see Model
 * @property UserAttributeValues $user_attributes
 */
trait HasUserAttributes
{
    /**
     * All user attributes that have been set on the model but not yet saved.
     */
    protected $dirtyUserAttributes = [];

    /**
     * Whether the user attributes should be destroyed when the model is saved.
     */
    protected $shouldDestroyUserAttributes = false;

    /**
     * Gives property and array access to the user attributes.
     */
    private ?UserAttributeValues $userAttributeValuesInstance = null;

    /**
     * Setup the model to make user_attributes fillable (so they reach the 'saving' hook).
     *
     * Optionally eager load the userAttributes relationship.
     */
    protected function initializeHasUserAttributes()
    {
        if (config('filament-user-attributes.eager_load_user_attributes', false)) {
            $this->with[] = 'userAttribute';
        }

        // In case fillable is used, we need to ensure that the user_attributes
        // are included in the fillable attributes of the model or they won't
        // be saved when the model is saved.
        if (!empty($this->fillable)) {
            $this->mergeFillable(['user_attributes']);
        }

        // Ensure that the user attributes are appended to the model when it is serialized.
        $this->append('user_attributes');
    }

    /**
     * Boots the trait and adds a saved hook to save the user attributes
     * when the model is saved.
     *
     * @return void
     */
    protected static function bootHasUserAttributes()
    {
        static::saved(function ($model) {
            if ($model->shouldDestroyUserAttributes) {
                $model->userAttribute()->delete();
                $model->setRelation('userAttribute', null);
                $model->shouldDestroyUserAttributes = false;

                return;
            }

            if (!empty($model->dirtyUserAttributes)) {
                $model->saveAllUserAttributes($model->dirtyUserAttributes);

                // Clear the delayed attributes as they are now saved
                $model->dirtyUserAttributes = [];
            }
        });
    }

    /**
     * Overrides the default isGuardableColumn method to ensure that
     * user_attributes is considered a guardable column, ensuring it
     * is passed along by Model::fill (which Filament uses to save).
     */
    protected function isGuardableColumn($key)
    {
        if ($key === 'user_attributes') {
            return true;
        }

        return parent::isGuardableColumn($key);
    }

    /**
     * Intercepts mass assignment (e.g. `Model::create(['user_attributes' => [...]])`
     * or Filament saving a form) so user attributes never reach the model's own table.
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'user_attributes') {
            if ($value !== null) {
                $this->setUserAttributeValues($value);
            }

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Saves all user attributes, merging them with those already stored.
     */
    public function saveAllUserAttributes(array $attributes)
    {
        $userAttribute = $this->userAttribute;

        if ($userAttribute) {
            $userAttribute->values = array_merge($userAttribute->values?->toArray() ?? [], $attributes);
            $userAttribute->save();

            return;
        }

        $this->setRelation('userAttribute', $this->userAttribute()->create(['values' => $attributes]));
    }

    /**
     * Accessor for serializing the model including the user attributes.
     */
    public function getUserAttributesAttribute()
    {
        return $this->getUserAttributeValues()->toArray();
    }

    /**
     * Relationship to the user attributes model.
     */
    public function userAttribute(): MorphOne
    {
        return $this->morphOne(UserAttribute::class, 'model');
    }

    /**
     * Whether the user attribute has a (non-null) value, including values
     * that have been set but not yet saved.
     */
    public function hasUserAttribute(string $key): bool
    {
        return $this->getUserAttributeValue($key) !== null;
    }

    public function setUserAttributeValue(string $key, $value)
    {
        if ($this->shouldDestroyUserAttributes) {
            throw new \LogicException('Cannot set user attribute on a model that has been marked for deletion.');
        }

        $this->dirtyUserAttributes[$key] = $value;
    }

    /**
     * Sets multiple user attributes at once. They are merged with the
     * existing user attributes when the model is saved.
     */
    public function setUserAttributeValues(array|object $values)
    {
        if ($this->shouldDestroyUserAttributes) {
            throw new \LogicException('Cannot set user attributes on a model that has been marked for deletion.');
        }

        $values = $values instanceof Arrayable ? $values->toArray() : (array) $values;

        $this->dirtyUserAttributes = array_merge($this->dirtyUserAttributes, $values);
    }

    /**
     * Removes all user attributes when the model is saved.
     */
    public function destroyUserAttributes()
    {
        $this->dirtyUserAttributes = [];
        $this->shouldDestroyUserAttributes = true;
    }

    /**
     * Gets a user attribute by its key, or a nested value using dot notation.
     * Values that have been set but not yet saved are included.
     */
    public function getUserAttributeValue(string $keyOrPath)
    {
        return Arr::get($this->getUserAttributeValues()->getArrayCopy(), $keyOrPath);
    }

    /**
     * Gets all user attributes, including those set but not yet saved.
     */
    public function getUserAttributeValues(): ArrayObject
    {
        if ($this->shouldDestroyUserAttributes) {
            return new ArrayObject([]);
        }

        $stored = $this->userAttribute?->values?->getArrayCopy() ?? [];

        return new ArrayObject(array_merge($stored, $this->dirtyUserAttributes));
    }

    /**
     * Static Getters
     */
    public static function getUserAttributeQuery(): Builder
    {
        return UserAttribute::query()
            ->where('model_type', (new static())->getMorphClass());
    }

    /**
     * Gets the value of the given user attribute for all models of this type.
     */
    public static function allUserAttributes(string $key): Collection
    {
        return static::getUserAttributeQuery()
            ->get()
            ->pluck('values.' . $key);
    }

    /**
     * Aggregates
     */
    public static function userAttributeSum(string $path): int|float
    {
        $sum = (float) static::getUserAttributeQuery()
            ->sum(static::userAttributeJsonPath($path));

        return floor($sum) === $sum ? (int) $sum : $sum;
    }

    /**
     * Converts a dot notation path (as used by getUserAttributeValue) to a JSON
     * column path. Paths already using `->` are supported as well.
     */
    protected static function userAttributeJsonPath(string $path): string
    {
        return 'values->' . str_replace('.', '->', $path);
    }

    /**
     * Query scopes. These can be called statically (`Product::whereUserAttribute(...)`)
     * or chained onto an existing query (`Product::where(...)->whereUserAttribute(...)`).
     */
    public function scopeWhereUserAttribute(Builder $query, string $key, mixed $operator = null, mixed $value = null): void
    {
        if (func_num_args() === 3) {
            [$operator, $value] = ['=', $operator];
        }

        $query->whereHas('userAttribute', function (Builder $query) use ($key, $operator, $value) {
            $query->where(static::userAttributeJsonPath($key), $operator, $value);
        });
    }

    public function scopeWhereUserAttributeContains(Builder $query, string $key, mixed $value): void
    {
        $query->whereHas('userAttribute', function (Builder $query) use ($key, $value) {
            $query->whereJsonContains(static::userAttributeJsonPath($key), $value);
        });
    }

    public function scopeWhereUserAttributeLength(Builder $query, string $key, mixed $operator, mixed $value = null): void
    {
        if (func_num_args() === 3) {
            [$operator, $value] = ['=', $operator];
        }

        $query->whereHas('userAttribute', function (Builder $query) use ($key, $operator, $value) {
            $query->whereJsonLength(static::userAttributeJsonPath($key), $operator, $value);
        });
    }

    /**
     * Magic Methods
     */

    /**
     * Lets `$model->user_attributes` be used to get and set user attributes,
     * as properties or as array keys:
     *
     *     $model->user_attributes->color = 'red';
     *     $model->user_attributes['color']; // 'red'
     */
    public function __get($key)
    {
        if ($key === 'user_attributes') {
            return $this->userAttributeValuesInstance ??= new UserAttributeValues($this);
        }

        return parent::__get($key);
    }

    /**
     * Setting `$model->user_attributes = [...]` merges the given values into
     * the user attributes.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     */
    public function __set($key, $value)
    {
        if ($key === 'user_attributes') {
            if (!is_array($value) && !is_object($value)) {
                throw new \InvalidArgumentException('The user_attributes property can only be set to an array or object. To remove all user attributes, use unset($model->user_attributes).');
            }

            $this->setUserAttributeValues($value);

            return;
        }

        parent::__set($key, $value);
    }

    /**
     * When the user attempts to unset the user_attributes property, we intercept it
     * and call destroyUserAttributes instead.
     *
     * @param  mixed  $key
     */
    public function __unset($key)
    {
        if ($key === 'user_attributes') {
            $this->destroyUserAttributes();

            return;
        }

        parent::__unset($key);
    }

    /**
     * When the user attempts to check if the user_attributes property is set, we intercept it
     * for user_attributes.
     *
     * @param  mixed  $key
     */
    public function __isset($key)
    {
        if ($key === 'user_attributes') {
            return true;
        }

        return parent::__isset($key);
    }
}
