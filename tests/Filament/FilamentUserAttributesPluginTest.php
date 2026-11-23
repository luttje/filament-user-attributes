<?php

namespace Luttje\FilamentUserAttributes\Tests\Filament;

use Filament\Facades\Filament;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use Luttje\FilamentUserAttributes\FilamentUserAttributesPlugin;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;

it('registers the config resource on the panel', function () {
    expect(Filament::getPanel('test')->getResources())->toContain(UserAttributeConfigResource::class);
});

it('applies the navigation settings of the plugin', function () {
    expect(UserAttributeConfigResource::getNavigationGroup())->toBe('Settings');

    FilamentUserAttributesPlugin::get()
        ->navigationIcon('heroicon-o-cog')
        ->navigationSort(5);

    expect(UserAttributeConfigResource::getNavigationIcon())->toBe('heroicon-o-cog');
    expect(UserAttributeConfigResource::getNavigationSort())->toBe(5);
});

it('falls back to the defaults of the resource', function () {
    FilamentUserAttributesPlugin::get()->navigationGroup(null);

    expect(UserAttributeConfigResource::getNavigationGroup())->toBeNull();
    expect(UserAttributeConfigResource::getNavigationIcon())->toBe('heroicon-o-adjustments-horizontal');
});

it('only accepts resources extending the config resource', function () {
    FilamentUserAttributesPlugin::make()->resource(CategoryResource::class);
})->throws(\InvalidArgumentException::class);
