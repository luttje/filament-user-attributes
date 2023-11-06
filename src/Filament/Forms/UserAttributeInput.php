<?php

namespace Luttje\FilamentUserAttributes\Filament\Forms;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;

class UserAttributeInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath('user_attributes.' . $this->getName());

        $this->afterStateHydrated(static function (UserAttributeInput $component, string | array | null $state): void {
            $component->state(function (?Model $record) use ($component) {
                if ($record === null) {
                    return null;
                }

                $key = $component->getName();

                /** @var HasUserAttributesContract */
                $record = $record;
                return $record->user_attributes->$key;
            });
        });
    }
}
