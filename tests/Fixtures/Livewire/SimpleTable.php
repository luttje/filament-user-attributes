<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;

class SimpleTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query())
            ->columns([
                TextColumn::make('name'),
                UserAttributeColumn::make('color'),
                UserAttributeColumn::make('stock.synthetic'),
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
