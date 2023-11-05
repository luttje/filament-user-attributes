<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\Product;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesTable;

class ConfiguredTable extends Component implements HasForms, HasTable
{
    use HasUserAttributesTable;
    use InteractsWithForms;
    use InteractsWithTable;

    // TODO: Not static here? This is a filament thing, find out whats going on
    public function resourceTable(Table $table): Table
    {
        return $table
            ->query(Product::query())
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function render()
    {
        return <<<'blade'
    <div>
        {{ $this->table }}
    </div>
blade;
    }
}
