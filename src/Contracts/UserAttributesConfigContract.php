<?php

namespace Luttje\FilamentUserAttributes\Contracts;

interface UserAttributesConfigContract
{
    public static function getUserAttributesConfig(): ?ConfiguresUserAttributesContract;

    public static function getFieldsForOrdering(): array;

    public static function getColumnsForOrdering(): array;
}
