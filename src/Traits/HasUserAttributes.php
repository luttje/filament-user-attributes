<?php

namespace Luttje\FilamentUserAttributes\Traits;

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

        // In case fillable is used, we need to ensure that the user_attributes
        // are included in the fillable attributes of the model or they won't
        // be saved when the model is saved.
        if (!empty($this->fillable)) {
            $this->mergeFillable(['user_attributes']);
        } else {
            $version = app()->version();
            $major = (int) explode('.', $version)[0];
            $isHighEnoughLaravel = $major >= 11;

            // Laravel 11 and higher consider user_attributes a guardable column
            // because we implemented the `setUserAttributesAttribute` mutator.
            // So we only need to perform the below hack if the Laravel version is
            // lower than 11.
            if (!$isHighEnoughLaravel) {
                // Otherwise guarded is used, in that case we need to ensure that all
                // attributes not in guarded are fillable, including user_attributes.
                $columns = $this->getConnection()
                            ->getSchemaBuilder()
                            ->getColumnListing($this->getTable());

                $fillable = array_diff($columns, $this->guarded);
                $fillable[] = 'user_attributes';

                // We have to fiddle with the fillable array here, because if any
                // attributes are in $guarded, Laravel will consider any attributes
                // that don't exist in the database as guarded, unless we explicitly
                // add them to the fillable array.
                // NOTE: This is quite a hack, so we mention this in the README.md!
                $this->mergeFillable($fillable);
            }
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
                $model->saveAllUserAttributes($model->dirtyUserAttributes);

                // Clear the delayed attributes as they are now saved
                $model->dirtyUserAttributes = [];
            }
        });
    }

    /**
     * Saves all user attributes.
     *
     * @param  array  $attributes
     */
    public function saveAllUserAttributes(array $attributes)
    {
        // If the model already has user attributes, merge them, otherwise create a new record
        if ($this->userAttribute()->exists()) {
            $newValues = array_merge($this->userAttribute->values->toArray(), $attributes);
            $this->userAttribute->values = $newValues;
            $this->userAttribute->save();
        } else {
            $this->userAttribute()->create(['values' => $attributes]);

            // Ensure that the user attributes are dirty for the next time the model is used.
            //$this->unsetRelation('userAttribute'); ?/ This was here because I accidentally fetched the relationship, just before creating it. Causing it to be set as null (and then not updated after save)
        }
    }

    /**
     * Accessor for serializing the model including the user attributes.
     */
    public function getUserAttributesAttribute()
    {
        return $this->getUserAttributeValues()->toArray();
    }

    /**
     * Mutator for setting the user attributes.
     *
     * This is only here so that in Laravel 11 and higher, the user_attributes
     * attribute is considered a 'guardable' column. This causes guarded models
     * to still save user_attributes, even if that is not a real database column.
     */
    public function setUserAttributesAttribute($value)
    {
        $this->attributes['user_attributes'] = $value;
    }

    /**
     * Relationship to the user attributes model.
     */
    public function userAttribute(): MorphOne
    {
        return $this->morphOne(UserAttribute::class, 'model');
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
            ->where('model_type', static::class);
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
            ->where('model_type', static::class)
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
