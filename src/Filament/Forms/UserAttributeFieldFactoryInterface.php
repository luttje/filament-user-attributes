<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

use Filament\Forms\Components\Field;

interface UserAttributeFieldFactoryInterface
{
    public function makeField(array $userAttribute): Field;
    public function makeConfigurationSchema(): array;
}
