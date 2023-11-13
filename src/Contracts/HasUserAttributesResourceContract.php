<?php

namespace Luttje\FilamentUserAttributes\Contracts;

interface HasUserAttributesResourceContract
{
    public static function getUserAttributesConfig(): ?HasUserAttributesConfigContract;

    public static function getFieldsForOrdering(): array;

    public static function getColumnsForOrdering(): array;
}
