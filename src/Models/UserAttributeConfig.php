<?php

namespace Luttje\FilamentUserAttributes\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $owner_type
 * @property string $owner_id
 * @property string $resource_type
 * @property string $model_type
 * @property array $config
 */
class UserAttributeConfig extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'resource_type',
        'model_type',
        'config',
    ];

    protected $casts = [
        'config' => AsArrayObject::class,
    ];

    public function owner()
    {
        return $this->morphTo(__FUNCTION__, 'owner_type', 'owner_id');
    }

    public function userAttributes()
    {
        return $this->hasMany(UserAttribute::class, 'model_type', 'model_type');
    }

    /**
     * Gets all user attribute configs with a certain key/value pair in
     * the config field.
     */
    public static function queryByConfig(string $key, mixed $value)
    {
        return static::query()
            ->whereJsonContains('config', [$key => $value]);
    }

    /**
     * Gets all user attribute configs with a certain key in the config field.
     */
    public static function queryByConfigKey(string $key)
    {
        return static::query()
            ->whereJsonContainsKey('config->[*]->' . $key);
    }
}
