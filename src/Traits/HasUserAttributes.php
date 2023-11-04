<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Luttje\FilamentUserAttributes\Models\UserAttribute;

/**
 * @property object $user_attributes
 */
trait HasUserAttributes
{
    public function userAttributes(): MorphOne
    {
        return $this->morphOne(UserAttribute::class, 'model');
    }

    public function hasUserAttribute(string $key): bool
    {
        return $this->userAttributes()->where('values->' . $key, '!=', null)->exists();
    }

    public function setUserAttributeValues(object $values)
    {
        return $this->userAttributes()->updateOrCreate([], ['values' => $values]);
    }

    public function destroyUserAttributes()
    {
        return $this->userAttributes?->delete();
    }

    public function getUserAttributeValue(string $key)
    {
        return $this->userAttributes()->first()->values[$key] ?? null;
    }

    public function setUserAttributeValue(string $key, $value)
    {
        if ($this->userAttributes !== null) {
            $this->userAttributes()->update(['values->' . $key => $value]);
            return;
        }

        $this->userAttributes()->create(['values' => [$key => $value]]);
    }

    /**
     *
     * Static Getters
     *
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
     *
     * Aggregates
     *
     */

    public static function userAttributeSum(string $path): int
    {
        return UserAttribute::query()
            ->where('model_type', static::class)
            ->sum('values->' . $path);
    }

    /**
     *
     * Where filters
     *
     */

    public static function whereUserAttribute(string $key, $value)
    {
        return static::whereHas('userAttributes', function ($query) use ($key, $value) {
            $query->where('values->' . $key, $value);
        });
    }

    public static function whereUserAttributeContains(string $key, $value)
    {
        return static::whereHas('userAttributes', function ($query) use ($key, $value) {
            $query->whereJsonContains('values->' . $key, $value);
        });
    }

    public static function whereUserAttributeLength(string $key, $operator, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return static::whereHas('userAttributes', function ($query) use ($key, $operator, $value) {
            $query->whereJsonLength('values->' . $key, $operator, $value);
        });
    }

    /**
     *
     * Magic Methods
     *
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
                $this->__userAttributesInstance = new class($this)
                {
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
     * @param mixed $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        if ($key === 'user_attributes') {
            if (!is_object($value)) {
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
     * @param mixed $key
     */
    public function __unset($key)
    {
        if ($key === 'user_attributes') {
            $this->destroyUserAttributes();
            return;
        }

        parent::__unset($key);
    }
}
