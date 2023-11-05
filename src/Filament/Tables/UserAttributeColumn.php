<?php

namespace Luttje\FilamentUserAttributes\Filament\Tables;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;

class UserAttributeColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->getStateUsing(function (?Model $record) {
            /** @var HasUserAttributesContract */
            $record = $record;

            return $record->user_attributes->{$this->name};
        });
    }
}
