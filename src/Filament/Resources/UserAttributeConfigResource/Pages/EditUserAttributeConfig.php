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

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // The record may be new (see UserAttributeConfigResource::resolveRecordRouteBinding).
        // Only save it now that access has been authorized, so that it exists
        // for the requests that follow.
        if (!$this->getRecord()->exists) {
            $this->getRecord()->save();
        }
    }
}
