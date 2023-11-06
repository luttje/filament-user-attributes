<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;

interface UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute): Column;

    public function makeField(array $userAttribute): Field;

    public function makeConfigurationSchema(): array;
}
