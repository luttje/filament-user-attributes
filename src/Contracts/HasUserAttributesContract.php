<?php

namespace Luttje\FilamentUserAttributes\Contracts;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * This interface is implemented by the HasUserAttributes trait.
 *
 * @property-read \Luttje\FilamentUserAttributes\UserAttributeValues $user_attributes
 * @property-write array|object $user_attributes Values to merge into the user attributes
 *
 * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
 */
interface HasUserAttributesContract
{
    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function userAttribute(): MorphOne;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function hasUserAttribute(string $key): bool;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function setUserAttributeValue(string $key, $value);

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function setUserAttributeValues(array|object $values);

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function destroyUserAttributes();

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function getUserAttributeValue(string $keyOrPath);

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributes
     */
    public function getUserAttributeValues(): ArrayObject;
}
