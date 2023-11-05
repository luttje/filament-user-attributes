<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

// use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\TextInput;

class UserAttributeInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath('user_attributes.' . $this->getName());

        $this->afterStateHydrated(static function (UserAttributeInput $component, string | array | null $state): void {
            $component->state(function($record) use($component) {
                $key = $component->getName();
                return $record->user_attributes->$key;
            });
        });
    }
}
