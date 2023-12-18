<?php

namespace Luttje\FilamentUserAttributes\Tests\Filament;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Illuminate\Support\Str;

function createUserWithAttributeConfigs($attributesConfig)
{
    $user = User::factory()->create();

    $user->userAttributesConfigs()->create(
        array_merge(
            [
                'resource_type' => CategoryResource::class,
                // TODO: Use a scope for always set this:
                'owner_type' => get_class($user),
            ],
            $attributesConfig
        )
    );

    return $user;
}

function createCategories($names, $attributes = [])
{
    return Category::factory()
        ->count(count($names))
        ->sequence(
            fn ($sequence) => array_merge(
                [
                    'name' => $names[$sequence->index],
                    'slug' => Str::of($names[$sequence->index])->slug('-'),
                    'description' => 'DESCRIPTION #' . $sequence->index
                ],
                $attributes[$sequence->index] ?? []
            )
        )
        ->create();
}

function addAttributesToCategories($categories, $attributes)
{
    $categories->each(function ($category, $index) use ($attributes) {
        foreach ($attributes[$index] as $key => $value) {
            $category->user_attributes->$key = $value;
        }

        $category->save();
    });
}

it('can render a resource with configured user attributes', function () {
    $user = createUserWithAttributeConfigs([
        'config' => [
            [
                'name' => 'color',
                'label' => 'Color',
                'type' => 'text',
            ],
        ],
    ]);

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
    $categories = createCategories($names);
    addAttributesToCategories($categories, $userAttributes);

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Test Category', 'Another cat', 'yetanothercategory'])
        ->assertSeeInOrder(['red', 'blue', 'green']);

    // Double-check that it's actually added to the database, with the polymorphic relation:
    $product = Category::with('userAttribute')->first();

    expect($product->user_attributes->color)
        ->toBe('red');
});

it('can render a resource with configured user attribute which is hidden in the table', function () {
    $user = createUserWithAttributeConfigs([
        'config' => [
            [
                'name' => 'color',
                'label' => 'Color',
                'type' => 'text',
                'order_position_table' => 'hidden'
            ],
        ],
    ]);

    $names = ['Test Category', 'Another cat', 'yetanothercategory'];
    $categories = createCategories($names);

    $categories->each(function ($category, $index) {
        $category->user_attributes->color = 'red';
        $category->save();
    });

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Test Category', 'Another cat', 'yetanothercategory'])
        ->assertDontSee('red');

    // Double-check that it's actually added to the database, with the polymorphic relation:
    $product = Category::with('userAttribute')->first();

    expect($product->user_attributes->color)
        ->toBe('red');
});

it('can render a resource with configured text input user attribute which is to appear after the name in the table', function () {
    $user = createUserWithAttributeConfigs([
        'config' => [
            [
                'name' => 'color',
                'label' => 'Color',
                'type' => 'text',
                'order_position_table' => 'after',
                'order_sibling_table' => 'Name',
            ],
        ],
    ]);

    $names = ['Test Category', 'Another cat', 'yetanothercategory'];
    $categories = createCategories($names);

    $categories->each(function ($category, $index) {
        $category->user_attributes->color = 'red';
        $category->save();
    });

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Test Category', 'red', 'DESCRIPTION #0'])
        ->assertSeeInOrder(['Another cat', 'red', 'DESCRIPTION #1'])
        ->assertSeeInOrder(['yetanothercategory', 'red', 'DESCRIPTION #2']);

    // Double-check that it's actually added to the database, with the polymorphic relation:
    $product = Category::with('userAttribute')->first();

    expect($product->user_attributes->color)
        ->toBe('red');
});

it('can render a resource with configured checkbox input user attribute which is to be hidden in the table', function () {
    $user = createUserWithAttributeConfigs([
        'config' => [
            [
                'name' => 'terms',
                'label' => 'Terms of service',
                'type' => 'checkbox',
                'order_position_table' => 'hidden',
            ],
        ],
    ]);

    $names = ['Test Category', 'Another cat', 'yetanothercategory'];
    $categories = createCategories($names);

    $categories->each(function ($category, $index) {
        $category->user_attributes->terms = true;
        $category->save();
    });

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Test Category', 'Another cat', 'yetanothercategory'])
        ->assertDontSee('Yes')
        ->assertDontSee('Terms of service');

    // Double-check that it's actually added to the database, with the polymorphic relation:
    $product = Category::with('userAttribute')->first();

    expect($product->user_attributes->terms)
        ->toBe(true);
});

// Test the filament form custom attributes
it('can configure a text input user attribute for a resource', function () {
    $user = createUserWithAttributeConfigs([
        'config' => [
            [
                'name' => 'terms',
                'label' => 'Terms of service',
                'type' => 'checkbox',
            ],
            [
                'name' => 'color',
                'label' => 'Color',
                'type' => 'text',
                'customizations' => [
                    'placeholder' => 'Enter your color here',
                ],
            ],
            [
                'name' => 'multiple_choice',
                'label' => 'Multiple Choice',
                'type' => 'radio',
                'customizations' => [
                    [
                        'id' => '1',
                        'label' => 'Option 1',
                    ],
                    [
                        'id' => '2',
                        'label' => 'Option 2',
                    ],
                    [
                        'id' => '3',
                        'label' => 'Option 3',
                    ],
                ],
            ],
            [
                'name' => 'select',
                'label' => 'Select',
                'type' => 'select',
                'customizations' => [
                    'options' => [
                        [
                            'id' => '4',
                            'label' => 'Option 4',
                        ],
                        [
                            'id' => '5',
                            'label' => 'Option 5',
                        ],
                        [
                            'id' => '6',
                            'label' => 'Option 6',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'richeditor',
                'label' => 'Richeditor',
                'type' => 'richeditor',
                'order_position_form' => 'before',
                'order_sibling_form' => 'Basic Information > Name',
            ],
            [
                'name' => 'number',
                'label' => 'Number',
                'type' => 'number',
                'customizations' => [
                    'minimum' => 0,
                    'maximum' => 100,
                    'decimal_places' => 2,
                ],
            ],
            [
                'name' => 'datetime',
                'label' => 'Datetime',
                'type' => 'datetime',
                'customizations' => [
                    'format' => 'datetime',
                    'allow_before_now' => true,
                ],
            ],
            [
                'name' => 'date',
                'label' => 'Date',
                'type' => 'datetime',
                'customizations' => [
                    'format' => 'date',
                    'allow_before_now' => true,
                ],
            ],
            [
                'name' => 'time',
                'label' => 'Time',
                'type' => 'datetime',
                'order_position_form' => 'hidden',
                'customizations' => [
                    'format' => 'time',
                    'allow_before_now' => true,
                ],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('edit', ['record' => Category::factory()->create()]))
        ->assertSeeInOrder([
            // Specifically configured to be in front of name:
            'Richeditor',
            'Name',
            // The rest are in order of configuration:
            'Terms of service',
            'Color',
            'Multiple Choice',
            'Select',
            'Number',
            'Datetime',
            'Date',
        ])
        ->assertDontSee('data.user_attributes.time');
});
