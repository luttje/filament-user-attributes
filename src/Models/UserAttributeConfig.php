<?php

namespace Luttje\FilamentUserAttributes\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * Limits to user attribute configs with a certain key/value pair in
     * the config field. Can be chained, e.g. onto `$owner->userAttributesConfigs()`.
     */
    public function scopeQueryByConfig(Builder $query, string $key, mixed $value): void
    {
        $query->whereJsonContains('config', [$key => $value]);
    }

    /**
     * Limits to user attribute configs with a certain key in the config field.
     * Can be chained, e.g. onto `$owner->userAttributesConfigs()`.
     */
    public function scopeQueryByConfigKey(Builder $query, string $key): void
    {
        $query->whereJsonContainsKey('config->[*]->' . $key);
    }
}
