<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Luttje\FilamentUserAttributes\Models\UserAttribute;

/**
 * @see Model
 * @property object $user_attributes
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
     * Stores an instance of the anonymous class that is created when the user_attributes
     */
    private $__userAttributesInstance;

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
     * Additionally removes user_attributes so it doesn't get saved to
     * the database.
     *
     * @return void
     */
    protected static function bootHasUserAttributes()
    {
        static::saving(function ($model) {
            if (!isset($model->attributes['user_attributes'])) {
                return;
            }

            $userAttributes = $model->attributes['user_attributes'];

            // In some cases (like when Filament is saving a model), the user_attributes
            // may not have been added to dirtyUserAttributes yet. In this case, we
            // need to add them now.
            foreach ($userAttributes as $key => $value) {
                if (!array_key_exists($key, $model->dirtyUserAttributes)) {
                    $model->dirtyUserAttributes[$key] = $value;
                }
            }

            unset($model->attributes['user_attributes']);
        });

        static::saved(function ($model) {
            if ($model->shouldDestroyUserAttributes) {
                $model->userAttribute()->delete();
                $model->shouldDestroyUserAttributes = false;

                return;
            }

            if (!empty($model->dirtyUserAttributes)) {
                // If the model already has user attributes, merge them, otherwise create a new record
                if ($model->userAttribute()->exists()) {
                    $newValues = array_merge($model->userAttribute->values->toArray(), $model->dirtyUserAttributes);
                    $model->userAttribute->values = $newValues;
                    $model->userAttribute->save();
                } else {
                    $model->userAttribute()->create(['values' => $model->dirtyUserAttributes]);

                    // Ensure that the user attributes are dirty for the next time the model is used.
                    //$model->unsetRelation('userAttribute'); ?/ This was here because I accidentally fetched the relationship, just before creating it. Causing it to be set as null (and then not updated after save)
                }

                // Clear the delayed attributes as they are now saved
                $model->dirtyUserAttributes = [];
            }
        });
    }

    /**
     * Accessor for serializing the model including the user attributes.
     */
    public function getUserAttributesAttribute()
    {
        return $this->getUserAttributeValues()->toArray();
    }

    /**
     * Relationship to the user attributes resource.
     */
    public function userAttribute(): MorphOne
    {
        return $this->morphOne(UserAttribute::class, 'resource');
    }

    public function hasUserAttribute(string $key): bool
    {
        return $this->userAttribute()->where('values->' . $key, '!=', null)->exists();
    }

    public function setUserAttributeValue(string $key, $value)
    {
        if ($this->shouldDestroyUserAttributes) {
            throw new \Exception('Cannot set user attribute on a model that has been marked for deletion.');
        }

        $this->dirtyUserAttributes[$key] = $value;
    }

    public function setUserAttributeValues(object $values)
    {
        if ($this->shouldDestroyUserAttributes) {
            throw new \Exception('Cannot set user attributes on a model that has been marked for deletion.');
        }

        $this->dirtyUserAttributes = array_merge($this->dirtyUserAttributes, (array) $values);
    }

    public function destroyUserAttributes()
    {
        $this->shouldDestroyUserAttributes = true;
    }

    public function getUserAttributeValue(string $keyOrPath)
    {
        $userAttribute = $this->userAttribute;
        $array = $userAttribute?->values;

        if (!$array) {
            return null;
        }

        return Arr::get($array, $keyOrPath);
    }

    public function getUserAttributeValues(): ArrayObject
    {
        $userAttribute = $this->userAttribute;
        return $userAttribute?->values ?? new ArrayObject([]);
    }

    /**
     * Static Getters
     */
    public static function getUserAttributeQuery(): Builder
    {
        return UserAttribute::query()
            ->where('resource_type', static::class);
    }

    public static function allUserAttributes(string $key)
    {
        return static::getUserAttributeQuery()
            ->get()
            ->pluck('values.' . $key);
    }

    /**
     * Aggregates
     */
    public static function userAttributeSum(string $path): int
    {
        return UserAttribute::query()
            ->where('resource_type', static::class)
            ->sum('values->' . $path);
    }

    /**
     * Where filters
     */
    public static function whereUserAttribute(string $key, $value)
    {
        return static::whereHas('userAttribute', function ($query) use ($key, $value) {
            $query->where('values->' . $key, $value);
        });
    }

    public static function whereUserAttributeContains(string $key, $value)
    {
        return static::whereHas('userAttribute', function ($query) use ($key, $value) {
            $query->whereJsonContains('values->' . $key, $value);
        });
    }

    public static function whereUserAttributeLength(string $key, $operator, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return static::whereHas('userAttribute', function ($query) use ($key, $operator, $value) {
            $query->whereJsonLength('values->' . $key, $operator, $value);
        });
    }

    /**
     * Magic Methods
     */

    /**
     * We want users of this trait to be able to access the user_attributes property as if it
     * were a real property on the model. This makes it easy to get and set user attributes.
     *
     * Creates an anonymous class when user_attributes is called for the first time and stores it
     * in the $__userAttributesInstance property. The anonymous class stores a reference back to
     * the owner object and uses the __get and __set magic methods to intercept property accesses.
     *
     * @return object
     */
    public function __get($key)
    {
        if ($key === 'user_attributes') {
            if (!$this->__userAttributesInstance) {
                $this->__userAttributesInstance = new class ($this) {
                    private $owner;

                    public function __construct($owner)
                    {
                        $this->owner = $owner;
                    }

                    public function __get($key)
                    {
                        return $this->owner->getUserAttributeValue($key);
                    }

                    public function __set($key, $value)
                    {
                        $this->owner->setUserAttributeValue($key, $value);
                    }

                    public function __unset($key)
                    {
                        $this->owner->setUserAttributeValue($key, null);
                    }

                    public function __isset($key)
                    {
                        return $this->owner->hasUserAttribute($key);
                    }
                };
            }

            return $this->__userAttributesInstance;
        }

        return parent::__get($key);
    }

    /**
     * When the user attempts to directly set the user_attributes property, we intercept it
     * and call setUserAttributeValues instead.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     */
    public function __set($key, $value)
    {
        if ($key === 'user_attributes') {
            if (!is_object($value)) {
                // We throw, because if a developer would set `$model->user_attributes = ['key' => 'value']`, it would
                // mess with the IDE's ability to recognize `user_attributes` as an object (thanks to the PHPdoc and __get)
                throw new \Exception('The user_attributes property must be an object. Be sure to wrap arrays with UserAttribute::make($yourArray) or cast them to an object.');
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
