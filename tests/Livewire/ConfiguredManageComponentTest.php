<?php

namespace Luttje\FilamentUserAttributes\Tests\Livewire;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire\ConfiguredManageComponent;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Livewire\Livewire;

it('can render a form with configured user attributes', function () {
    $user = User::factory()->create();

    $user->createUserAttributeConfig(
        ConfiguredManageComponent::class,
        'custom_color',
        'Custom Color'
    );

    Livewire::actingAs($user)
        ->test(ConfiguredManageComponent::class)
        ->assertSuccessful()
        ->assertSee('Custom Color');
});
