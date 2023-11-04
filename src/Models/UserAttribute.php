<?php

namespace Luttje\FilamentUserAttributes\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model
{
    protected $fillable = [
        'values',
    ];

    protected $casts = [
        'values' => AsArrayObject::class,
    ];

    public function model()
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }

    public static function make(array $values): object
    {
        return (object) $values;
    }
}
