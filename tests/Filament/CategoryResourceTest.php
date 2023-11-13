<?php

namespace Luttje\FilamentUserAttributes\Tests\Livewire;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Illuminate\Support\Str;

it('can render a resource with configured user attributes', function () {
    $user = User::factory()
        ->create();

    // Pretend that the user configured what attributes exist for products
    $user->userAttributesConfigs()->create(
        [
            'resource_type' => Category::class,
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

    $names = ['Test Category', 'Another cat', 'yetanothercategory'];
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
    $categories = Category::factory()
        ->count(3)
        ->sequence(
            fn ($sequence) => [
                'name' => $names[$sequence->index],
                'slug' => Str::of($names[$sequence->index])
                    ->slug('-')
            ],
        )
        ->create();

    $categories->each(function ($category, $index) use ($userAttributes) {
        foreach ($userAttributes[$index] as $key => $value) {
            $category->user_attributes->$key = $value;
        }

        $category->save();
    });

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Test Category', 'Another cat', 'yetanothercategory'])
        ->assertSeeInOrder(['red', 'blue', 'green']);

    // Double-check that it's actually added to the database, with the polymorphic relation:
    $product = Category::with('userAttributes')->first();

    expect($product->user_attributes->color)
        ->toBe('red');
});
