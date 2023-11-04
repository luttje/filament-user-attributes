<?php

namespace Luttje\FilamentUserAttributes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Luttje\FilamentUserAttributes\FilamentUserAttributes
 */
class FilamentUserAttributes extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Luttje\FilamentUserAttributes\FilamentUserAttributes::class;
    }
}
