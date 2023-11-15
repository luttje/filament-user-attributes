<?php

namespace Luttje\FilamentUserAttributes\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

interface ConfiguresUserAttributesContract
{
    /**
     * @see \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes
     */
    public function userAttributesConfigs(): HasMany;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes
     */
    public function getUserAttributeColumns(string $resource): array;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes
     */
    public function getUserAttributeFields(string $resource): array;

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes
     */
    public function createUserAttributeConfig(
        string $resource,
        string $name,
        string $label,
        string $type = 'text',
        array $options = []
    ): UserAttributeConfig;
}
