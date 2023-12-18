<?php

namespace Luttje\FilamentUserAttributes\Filament\Tables;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;

class UserAttributeColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        self::setUpColumn($this);
    }

    public static function setUpColumn(Column $column): Column
    {
        $column->getStateUsing(function (?Model $record) use ($column) {
            /** @var HasUserAttributesContract */
            $record = $record;

            $userAttributes = $record->user_attributes;

            if ($userAttributes == null) {
                $class = get_class($record);
                throw new \Exception("User attributes not available for instance of $class. Did you forget to implement Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract with the Luttje\FilamentUserAttributes\Traits\HasUserAttributes trait on this model?");
            }

            return $record->user_attributes->{$column->name};
        });

        return $column;
    }
}
