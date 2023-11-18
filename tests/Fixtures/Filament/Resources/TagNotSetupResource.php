<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\TagNotSetupResource\Pages;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\TagNotSetup;

class TagNotSetupResource extends Resource
{
    protected static ?string $model = TagNotSetup::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'TagsNotSetup';

    protected static ?string $modelLabel = 'TagNotSetup';

    protected static ?string $pluralModelLabel = 'TagsNotSetup';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Tabs::make()
                            ->persistTabInQueryString()
                            ->tabs([
                                Tabs\Tab::make('Basic Information')
                                    ->schema([
                                        TextInput::make('name')
                                            ->maxLength(255)
                                            ->required(),
                                        Textarea::make('description')
                                            ->maxLength(5000),
                                    ]),
                                Tabs\Tab::make('Additional Settings')
                                    ->schema([
                                            // TODO: Have some User Attributes show here automatically based on user configuration
                                        ]),

                                Tabs\Tab::make('Attachments')
                                    ->schema([
                                        // TODO: Have some User Attributes show here automatically based on user configuration
                                    ])
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTagsNotSetup::route('/'),
        ];
    }
}
