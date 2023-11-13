<?php

namespace Luttje\FilamentUserAttributes\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class UserAttributeConfig extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'resource_type',
        'config',
    ];

    protected $casts = [
        'config' => AsArrayObject::class,
    ];

    public function owner()
    {
        return $this->morphTo(__FUNCTION__, 'owner_type', 'owner_id');
    }
}
