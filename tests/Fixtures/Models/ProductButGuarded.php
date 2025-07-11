<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class ProductButGuarded extends Model implements HasUserAttributesContract
{
    use HasUserAttributes;
    use HasUuids;

    protected $table = 'products';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['some_process_data'];
}
