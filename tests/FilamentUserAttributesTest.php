<?php

namespace Luttje\FilamentUserAttributes\Tests;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Config;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\FilamentUserAttributes as FilamentUserAttributesImpl;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;

it('can get resources by registering them, and then getting them', function () {
    Config::set('filament-user-attributes.discover_resources', false);

    FilamentUserAttributes::registerResources([
        'App\\Filament\\Resources\\UserResource',
        'App\\Filament\\Resources\\PostResource',
    ]);

    $resources = FilamentUserAttributes::getConfigurableResources();

    expect($resources)->toBe([
        'App\\Filament\\Resources\\UserResource',
        'App\\Filament\\Resources\\PostResource',
    ]);
});

it('throws when trying to register a resource while discovering is enabled', function () {
    Config::set('filament-user-attributes.discover_resources', []);

    FilamentUserAttributes::registerResources([
        'App\\Filament\\Resources\\UserResource',
        'App\\Filament\\Resources\\PostResource',
    ]);
})->throws(\Exception::class);

it('can discover resources from specified app directories', function () {
    Config::set('filament-user-attributes.discover_resources', [
        'Resources',
    ]);

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/Fixtures/Filament'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures\Filament',
    ));

    $resources = FilamentUserAttributes::getConfigurableResources();

    expect($resources)->toMatchArray([
        CategoryResource::class => 'Category Page',
    ]);
});

it('can transform names of discovered resources to labels', function () {
    $label = FilamentUserAttributes::classNameToLabel(CategoryResource::class);
    Config::set('filament-user-attributes.discover_resources', [
        'Resources',
    ]);

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/Fixtures/Filament'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures\Filament',
    ));

    config::set('filament-user-attributes.discovery_resource_name_transformer', function ($name) {
        $name = basename($name);
        return "xyz$name|abc";
    });

    $resources = FilamentUserAttributes::getConfigurableResources();

    expect($label)->toBe('Category Page');
    expect($resources[CategoryResource::class])->toBe('xyzCategoryResource|abc');
});

it('can insert a text input before another in a form schema', function () {
    $componentName = TextInput::make('name')->label('Name');
    $componentEmail = TextInput::make('email');
    $componentAge = TextInput::make('age');

    $components = [$componentName, $componentEmail];
    $updatedComponents = FilamentUserAttributes::addFieldBesidesField($components, 'Name', 'after', $componentAge);

    expect($updatedComponents)->toHaveLength(3);
    expect($updatedComponents)->toMatchArray([
        $componentName,
        $componentAge,
        $componentEmail,
    ]);
});

it('adds a field at the end if it cannot find the field to insert besides', function () {
    $componentName = TextInput::make('name');
    $componentEmail = TextInput::make('email');
    $componentAge = TextInput::make('age');

    $components = [$componentName, $componentEmail];
    $updatedComponents = FilamentUserAttributes::addFieldBesidesField($components, 'non-existing', 'after', $componentAge);

    expect($updatedComponents)->toHaveLength(3);
    expect($updatedComponents)->toMatchArray([
        $componentName,
        $componentEmail,
        $componentAge,
    ]);
});

it('adds a field into a tab if the sibling is in a tab', function () {
    $componentName = TextInput::make('name');
    $componentEmail = TextInput::make('email')->label('Email');
    $componentAge = TextInput::make('age');
    $tabParent = Tab::make('tab-parent')
        ->label('Tab Parent');
    $tabParent->schema([
        $componentName,
        $componentEmail,
    ]);

    $components = [$tabParent];
    $updatedComponents = FilamentUserAttributes::addFieldBesidesField($components, 'Tab Parent > Email', 'before', $componentAge);

    expect($updatedComponents[0]->getChildComponents())->toHaveLength(3);
    expect($updatedComponents[0]->getChildComponents())->toMatchArray([
        $componentName,
        $componentAge,
        $componentEmail,
    ]);
});

it('adds a field into a section in a tab if that is how deep it is nested', function () {
    $componentName = TextInput::make('name');
    $componentEmail = TextInput::make('email')->label('Email');
    $componentAge = TextInput::make('age');
    $tabParent = Tab::make('tab-parent')
        ->label('Tab Parent');
    $sectionParent = Section::make('section-parent')
        ->label('Section Parent');
    $tabParent->schema([
        $sectionParent,
    ]);
    $sectionParent->schema([
        $componentName,
        $componentEmail,
    ]);

    $components = [$tabParent];
    $updatedComponents = FilamentUserAttributes::addFieldBesidesField($components, 'Tab Parent > Section Parent > Email', 'before', $componentAge);

    expect($updatedComponents[0]->getChildComponents()[0]->getChildComponents())->toHaveLength(3);
    expect($updatedComponents[0]->getChildComponents()[0]->getChildComponents())->toMatchArray([
        $componentName,
        $componentAge,
        $componentEmail,
    ]);
});

it('can insert a text column after another in a table', function () {
    $componentName = TextColumn::make('name')->label('Name');
    $componentEmail = TextColumn::make('email');
    $componentAge = TextColumn::make('age');

    $components = [$componentName, $componentEmail];
    $updatedComponents = FilamentUserAttributes::addColumnBesidesColumn($components, 'Name', 'after', $componentAge);

    expect($updatedComponents)->toHaveLength(3);
    expect($updatedComponents)->toMatchArray([
        $componentName,
        $componentAge,
        $componentEmail,
    ]);
});

it('can insert a text column before another in a table', function () {
    $componentName = TextColumn::make('name')->label('Name');
    $componentEmail = TextColumn::make('email');
    $componentAge = TextColumn::make('age');

    $components = [$componentName, $componentEmail];
    $updatedComponents = FilamentUserAttributes::addColumnBesidesColumn($components, 'Name', 'before', $componentAge);

    expect($updatedComponents)->toHaveLength(3);
    expect($updatedComponents)->toMatchArray([
        $componentAge,
        $componentName,
        $componentEmail,
    ]);
});
