<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Component;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesComponent;

class ConfiguredManageComponent extends Component implements HasForms, HasTable
{
    use HasUserAttributesComponent {
        HasUserAttributesComponent::form insteadof InteractsWithForms;
        HasUserAttributesComponent::table insteadof InteractsWithTable;
    }

    use InteractsWithForms;
    use InteractsWithTable;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function resourceTable(Table $table): Table
    {
        return $table
            ->query(Product::query())
            ->columns([
                TextColumn::make('slug'),
                TextColumn::make('name'),
            ]);
    }

    public function resourceForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
            ])
            ->statePath('data')
            ->model(Product::class);
    }

    public function create(): void
    {
        $validated = $this->form->getState();
        $this->reset();
        $validated['slug'] = Str::of($validated['name'])
            ->slug('-');

        Product::create($validated);
    }

    public function render()
    {
        return <<<'blade'
    <div>
        {{ $this->table }}

        <hr>

        <form wire:submit="create">
            {{ $this->form }}

            <button type="submit">
                Submit
            </button>
        </form>

        <x-filament-actions::modals />
    </div>
blade;
    }
}