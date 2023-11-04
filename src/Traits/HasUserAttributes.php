<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Luttje\FilamentUserAttributes\Models\UserAttribute;

trait HasUserAttributes
{
    public function userAttributes(): MorphOne
    {
        return $this->morphOne(UserAttribute::class, 'model');
    }

    public function addUserAttribute(array $values)
    {
        return $this->userAttributes()->create(['values' => $values]);
    }

    public function updateUserAttributes(UserAttribute $attribute, array $values)
    {
        return $attribute->update(['values' => $values]);
    }

    public function updateUserAttribute(UserAttribute $attribute, string $path, mixed $value): int
    {
        return $attribute->query()
            ->update([
                'values->' . $path => $value,
            ]);
    }

    public function removeUserAttribute(UserAttribute $attribute)
    {
        return $attribute->delete();
    }
}
