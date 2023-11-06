<?php

namespace Luttje\FilamentUserAttributes\Tests\Livewire;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire\SimpleTable;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;

use function Pest\Livewire\livewire;

it('can render a table', function () {
    $names = ['Foo', 'Bar', 'Baz'];
    $userAttributes = [
        [
            'color' => 'red',
            'stock' => [
                'synthetic' => 'there are 100',
                'cotton' => 'there are 50',
            ],
        ],
        [
            'color' => 'blue',
            'stock' => [
                'synthetic' => 'there are 200',
                'cotton' => 'there are 50',
            ],
        ],
        [
            'color' => 'green',
            'stock' => [
                'synthetic' => 'there are 300',
                'cotton' => 'there are 50',
            ],
        ],
    ];
    $products = Product::factory()
        ->count(3)
        ->sequence(
            fn ($sequence) => ['name' => $names[$sequence->index]],
        )
        ->create();

    $products->each(function ($product, $index) use ($userAttributes) {
        foreach ($userAttributes[$index] as $key => $value) {
            $product->user_attributes->$key = $value;
        }

        $product->save();
    });

    livewire(SimpleTable::class)
        ->assertSuccessful()
        ->assertSeeInOrder($names)
        ->assertSeeInOrder(array_column($userAttributes, 'color'))
        ->assertSeeInOrder(array_column($userAttributes, 'synthetic'));
});

it('can render a table even if no attributes are available', function () {
    $names = ['Foo', 'Bar', 'Baz'];
    Product::factory()
        ->count(3)
        ->sequence(
            fn ($sequence) => ['name' => $names[$sequence->index]],
        )
        ->create();

    livewire(SimpleTable::class)
        ->assertSuccessful()
        ->assertSeeInOrder($names);
});
