<?php

namespace Luttje\FilamentUserAttributes\Tests\Livewire;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire\ConfiguredManageComponent;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Livewire\Livewire;

it('can render a form with configured user attributes', function () {
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

    Livewire::actingAs($user)
        ->test(ConfiguredManageComponent::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Test product',
            'user_attributes.color' => 'red',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        // Make sure we're not seeing the attributes in the form:
        ->assertFormSet([
            'name' => '',
            'user_attributes.color' => '',
        ])
        // But in the table:
        ->assertSee('test-product')
        ->assertSee('Test product')
        ->assertSee('red');

    // Double-check that it's actually added to the database, with the polymorphic relation:
    $product = Product::with('userAttributes')->first();

    expect($product->user_attributes->color)
        ->toBe('red');
});
