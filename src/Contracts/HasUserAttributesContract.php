<?php

namespace Luttje\FilamentUserAttributes\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasUserAttributesContract
{
    public static function getUserAttributesConfig(): Model;
}
