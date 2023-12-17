<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Models\SomeSubFolder;

use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class PriceRange extends Model implements HasUserAttributesContract
{
    use HasUserAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];
}
