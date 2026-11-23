<?php

namespace Luttje\FilamentUserAttributes\Tests\Traits;

use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\TagNotSetup;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

it('can create a user attribute config', function () {
    $user = User::factory()->create();

    $user->createUserAttributeConfig(CategoryResource::class, 'custom_test', 'Test', 'number', [
        'minimum' => 0,
        'maximum' => 10,
        'decimal_places' => 2,
    ]);

    $config = $user->userAttributesConfigs()->first();

    expect($config->resource_type)->toBe(CategoryResource::class);
    expect($config->model_type)->toBe(Category::class);
    expect($config->config)->toMatchArray([
        [
            'type' => 'number',
            'label' => 'Test',
            'name' => 'custom_test',
            'customizations' => [
                'minimum' => 0,
                'maximum' => 10,
                'decimal_places' => 2,
            ],
        ],
    ]);
});

it('can add multiple user attributes to the same resource', function () {
    $user = User::factory()->create();

    $user->createUserAttributeConfig(CategoryResource::class, 'first', 'First');
    $user->createUserAttributeConfig(CategoryResource::class, 'second', 'Second', 'text', [], [
        'required' => true,
        'order_position_form' => 'hidden',
    ]);

    expect($user->userAttributesConfigs()->count())->toBe(1);

    $config = $user->userAttributesConfigs()->first();

    expect(collect($config->config)->pluck('name')->all())->toBe(['first', 'second']);
    expect($config->config[1]['required'])->toBeTrue();

    // The hidden attribute is left out of the form right away
    $fields = $user->getUserAttributeFields(CategoryResource::class);

    expect(collect($fields)->map(fn ($field) => $field['field']->getName())->all())->toBe(['first']);
});

it('stores the morph alias of the owner', function () {
    \Illuminate\Database\Eloquent\Relations\Relation::morphMap(['user' => User::class]);

    try {
        $user = User::factory()->create();
        $user->createUserAttributeConfig(CategoryResource::class, 'first', 'First');

        $config = $user->userAttributesConfigs()->first();

        expect($config->owner_type)->toBe('user');
        expect($config->owner->is($user))->toBeTrue();
    } finally {
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([], false);
    }
});

it('builds date fields and columns without every customization set', function () {
    $user = User::factory()->create();
    $user->createUserAttributeConfig(CategoryResource::class, 'released_at', 'Released at', 'datetime', ['format' => 'unknown']);

    expect($user->getUserAttributeFields(CategoryResource::class))->toHaveCount(1)
        ->and($user->getUserAttributeColumns(CategoryResource::class))->toHaveCount(1);
});

it('explains how to register an unsupported user attribute type', function () {
    $user = User::factory()->create();
    $user->createUserAttributeConfig(CategoryResource::class, 'custom', 'Custom', 'does-not-exist');

    $user->getUserAttributeFields(CategoryResource::class);
})->throws(\InvalidArgumentException::class, "type 'does-not-exist' is not supported");

it('explains which model is missing user attributes in a column', function () {
    $column = UserAttributeColumn::make('color');

    $column->evaluate($column->getStateUsingCallback(), ['record' => new TagNotSetup()]);
})->throws(\LogicException::class, TagNotSetup::class . ' has no user attributes');
