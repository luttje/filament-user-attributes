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
    public function getUserAttributeColumns(string $resource): array;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig
     */
    public function getUserAttributeComponents(string $resource): array;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig
     */
    public function createUserAttributeConfig(
        string $resource,
        string $name,
        string $label,
        string $type = 'text',
        array $options = []
    ): UserAttributeConfig;
}
