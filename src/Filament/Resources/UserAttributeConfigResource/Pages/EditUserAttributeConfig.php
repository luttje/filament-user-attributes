<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;

class EditUserAttributeConfig extends EditRecord
{
    public static string $injectedResource = UserAttributeConfigResource::class;

    public static function getResource(): string
    {
        return static::$injectedResource;
    }
}
