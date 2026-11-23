<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesResourceContract;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

/**
 * A form to create or edit a product, with a field for its category.
 */
class ProductForm extends Component implements HasForms, UserAttributesResourceContract
{
    use UserAttributesResource;
    use InteractsWithForms;

    public ?Product $record = null;

    public ?array $data = [];

    public function mount(?Product $record = null): void
    {
        $this->record = $record;

        $this->form->fill($record?->attributesToArray());
    }

    public static function getModel(): string
    {
        return Product::class;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components(
                self::withUserAttributeFields([
                    TextInput::make('name'),
                    TextInput::make('category_id'),
                ])
            )
            ->statePath('data')
            ->model($this->record ?? Product::class);
    }

    public function render()
    {
        return <<<'blade'
    <div>
        {{ $this->form }}
    </div>
blade;
    }
}
