<?php

namespace Luttje\FilamentUserAttributes;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Traversable;

/**
 * Gives property and array access to the user attributes of a model.
 *
 * Returned by `$model->user_attributes`. Changes are stored on the model and
 * persisted when the model is saved.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 * @implements Arrayable<string, mixed>
 */
class UserAttributeValues implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    public function __construct(
        protected HasUserAttributesContract $owner
    ) {
        //
    }

    public function __get(string $key): mixed
    {
        return $this->owner->getUserAttributeValue($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->owner->setUserAttributeValue($key, $value);
    }

    public function __unset(string $key): void
    {
        $this->owner->setUserAttributeValue($key, null);
    }

    public function __isset(string $key): bool
    {
        return $this->owner->hasUserAttribute($key);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    public function toArray(): array
    {
        return $this->owner->getUserAttributeValues()->getArrayCopy();
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
