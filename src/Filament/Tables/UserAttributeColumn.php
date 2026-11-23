<?php

namespace Luttje\FilamentUserAttributes\Filament\Tables;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class UserAttributeColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        self::setUpColumnState($this);
    }

    public static function setUpColumnState(Column $column): Column
    {
        $column->getStateUsing(function (?Model $record) use ($column) {
            if ($record === null) {
                return null;
            }

            if (!$record instanceof HasUserAttributesContract) {
                throw new \LogicException('The model ' . get_class($record) . ' has no user attributes. Implement ' . HasUserAttributesContract::class . ' and use the ' . HasUserAttributes::class . ' trait on it.');
            }

            return $record->getUserAttributeValue($column->getName());
        });

        return $column;
    }
}
