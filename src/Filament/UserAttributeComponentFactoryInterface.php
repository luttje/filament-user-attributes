<?php

namespace Luttje\FilamentUserAttributes\Filament;

use Filament\Forms\Components\Field;
use Filament\Tables\Columns\Column;

interface UserAttributeComponentFactoryInterface
{
    public function makeColumn(array $userAttribute, array $customizations): Column;

    public function makeField(array $userAttribute, array $customizations): Field;

    public function makeDefaultValue(array $userAttribute, array $customizations): mixed;

    public function makeConfigurationSchema(): array;
}
