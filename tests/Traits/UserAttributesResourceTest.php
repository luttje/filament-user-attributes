<?php

namespace Luttje\FilamentUserAttributes\Tests\Traits;

use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\SomeSubFolder\ProductResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

it('uses the authenticated user as owner by default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(CategoryResource::getUserAttributesOwner())->toBe($user);
});

it('has no owner when nobody is authenticated', function () {
    expect(CategoryResource::getUserAttributesOwner())->toBeNull();
});

it('throws a helpful error when no owner is found while rendering fields', function () {
    FilamentUserAttributes::getUserAttributeFields(CategoryResource::class);
})->throws(\LogicException::class, 'resolveOwnerUsing');

it('can resolve the owner for all resources', function () {
    $owner = User::factory()->create();
    $this->actingAs(User::factory()->create());

    FilamentUserAttributes::resolveOwnerUsing(function (string $resource) use ($owner) {
        expect($resource)->toBe(CategoryResource::class);

        return $owner;
    });

    expect(CategoryResource::getUserAttributesOwner())->toBe($owner);
});

it('lets a resource override the owner', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    FilamentUserAttributes::resolveOwnerUsing(fn () => User::factory()->create());

    // ProductResource implements getUserAttributesOwner() itself
    expect(ProductResource::getUserAttributesOwner())->toBe($user);
});

it('throws when the owner resolver returns something that cannot configure user attributes', function () {
    FilamentUserAttributes::resolveOwnerUsing(fn () => new Category());

    CategoryResource::getUserAttributesOwner();
})->throws(\UnexpectedValueException::class);

it('refuses to ignore the renamed getUserAttributesConfig method', function () {
    $resource = new class () {
        use UserAttributesResource;

        public static function getUserAttributesConfig()
        {
            return null;
        }
    };

    $resource::getUserAttributesOwner();
})->throws(\LogicException::class, 'renamed to getUserAttributesOwner()');
