<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns;

class UserAttributeInput extends Component
{
    use Concerns\HasName;

    protected string $view = 'filament-user-attributes::forms.user-attribute-input';

    public static function make(string $name): static
    {
        return app(static::class, ['name' => $name]);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->name($this->name);
    }
}
