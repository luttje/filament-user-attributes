<?php

namespace Luttje\FilamentUserAttributes\Traits;

use Filament\Forms\Form;

trait HasUserAttributesForm
{
    /**
     * Overrides the default form function to add user attributes.
     *
     * @param Form $form
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form;
    }
}
