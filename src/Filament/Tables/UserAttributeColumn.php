<?php

namespace Luttje\FilamentUserAttributes\Filament\Tables;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class UserAttributeColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->getStateUsing(function (?Model $record) {
            return $record->user_attributes->{$this->name};
        });
    }
}
