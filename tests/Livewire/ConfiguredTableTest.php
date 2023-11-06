<?php

namespace Luttje\FilamentUserAttributes\Tests\Livewire;

use Luttje\FilamentUserAttributes\Tests\Mocks\Livewire\ConfiguredTable;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\User;
use Livewire\Livewire;

it('can render a table with configured user attributes', function () {
    $names = ['Foo', 'Bar', 'Baz'];
    $userAttributes = [
        [
            'color' => 'red',
        ],
        [
            'color' => 'blue',
        ],
        [
            'color' => 'green',
        ],
    ];
    $products = Product::factory()
        ->count(3)
        ->sequence(
            fn ($sequence) => ['name' => $names[$sequence->index]],
        )
        ->create();

    $user = User::factory()
        ->create();

    // Pretend that the user configured what attributes exist for products
    $user->userAttributesConfigs()->create(
        [
            'model_type' => Product::class,
            // TODO: Use a scope for always set this:
            'owner_type' => get_class($user),
            'config' => [
                [
                    'name' => 'color',
                    'label' => 'Color',
                    'type' => 'text',
                ],
            ],
        ]
    );

    // TODO: Test this through the livewire components
    $products->each(function ($product, $index) use ($userAttributes) {
        foreach ($userAttributes[$index] as $key => $value) {
            $product->user_attributes->$key = $value;
        }

        $product->save();
    });

    Livewire::actingAs($user)
        ->test(ConfiguredTable::class)
        ->assertSuccessful()
        ->assertSeeInOrder($names)
        ->assertSeeInOrder(array_column($userAttributes, 'color'));
});
