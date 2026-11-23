<?php

namespace Luttje\FilamentUserAttributes\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;

/**
 * This interface is implemented by the ConfiguresUserAttributes trait.
 *
 * @see \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes
 */
interface ConfiguresUserAttributesContract
{
    /**
     * @see \Illuminate\Database\Eloquent\Model::getKey()
     */
    public function getKey();

    /**
     * @see \Illuminate\Database\Eloquent\Model::getMorphClass()
     */
    public function getMorphClass();

    /**
     * @see \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes
     */
    public function userAttributesConfigs(): MorphMany;

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
        array $customizations = [],
        array $options = []
    ): UserAttributeConfig;
}
