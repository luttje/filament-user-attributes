<?php

namespace Luttje\FilamentUserAttributes\Tests\Traits;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
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
