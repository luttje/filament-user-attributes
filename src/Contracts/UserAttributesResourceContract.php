<?php

namespace Luttje\FilamentUserAttributes\Contracts;

/**
 * This interface is implemented by the UserAttributesResource trait.
 *
 * @see \Luttje\FilamentUserAttributes\Traits\UserAttributesResource
 */
interface UserAttributesResourceContract
{
    /**
     * Returns the model that owns the user attribute configurations for this
     * resource, e.g. the current user or tenant.
     */
    public static function getUserAttributesOwner(): ?ConfiguresUserAttributesContract;

    public static function getAllFieldComponents(): array;

    public static function getAllTableColumns(): array;

    public static function getFieldsForOrdering(): array;

    public static function getColumnsForOrdering(): array;

    public static function getModel(): string;
}
