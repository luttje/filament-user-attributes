<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\Product;

class ConfiguredTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return FilamentUserAttributes::setupTable($table)
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
