<?php

namespace Luttje\FilamentUserAttributes\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

interface HasUserAttributesConfigContract
{
    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig
     */
    public function userAttributesConfigs(): HasMany;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig
     */
    public function getUserAttributesColumns(string $model): array;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig
     */
    public function createUserAttributeConfig(
        string $model,
        string $name,
        string $label,
        string $type = 'text',
        array $options = []
    ): UserAttributeConfig;
}
