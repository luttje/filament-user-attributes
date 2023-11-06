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
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesResource;

class ConfiguredTable extends Component implements HasForms, HasTable
{
    use HasUserAttributesResource {
        HasUserAttributesResource::table insteadof InteractsWithTable;
        HasUserAttributesResource::form insteadof InteractsWithForms;
    }

    use InteractsWithForms;
    use InteractsWithTable;

    public static function resourceTable(Table $table): Table
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
