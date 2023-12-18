<?php

namespace Luttje\FilamentUserAttributes\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $model_type
 * @property string $model_id
 * @property array $values
 */
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
        return $this->morphTo();
    }

    public static function make(array $values): object
    {
        return (object) $values;
    }
}
