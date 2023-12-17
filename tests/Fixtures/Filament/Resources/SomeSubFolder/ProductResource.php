<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\SomeSubFolder;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductResource extends Resource implements UserAttributesConfigContract
{
    use UserAttributesResource;

    protected static ?string $model = Product::class;

    protected static bool $isDiscovered = false;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Product';

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Products';

    public static function getUserAttributesConfig(): ?ConfiguresUserAttributesContract
    {
        /** @var \Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User */
        $user = Auth::user();

        return $user;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
            ]);
    }
}
