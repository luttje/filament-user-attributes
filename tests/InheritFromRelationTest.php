<?php

namespace Luttje\FilamentUserAttributes\Tests;

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Filament\UserAttributeComponentFactoryRegistry;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Livewire\ProductForm;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

beforeEach(function () {
    Config::set('filament-user-attributes.discover_resources', false);

    FilamentUserAttributes::registerResources([
        CategoryResource::class => 'Category Page',
        ProductForm::class => 'Product Form',
    ]);
});

function inheritCategoryName(User $user, string $relation, string $name = 'category_name'): void
{
    $user->createUserAttributeConfig(ProductForm::class, $name, 'Category name', 'text', options: [
        'inherit' => true,
        'inherit_relation' => $relation,
        'inherit_attribute' => 'name',
    ]);
}

it('inherits from the related record of a saved record', function () {
    $user = User::factory()->create();
    inheritCategoryName($user, 'category');

    $category = Category::factory()->create(['name' => 'Shoes']);
    $product = Product::factory()->create(['category_id' => $category->id]);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['record' => $product])
        ->assertSet('data.user_attributes.category_name', 'Shoes');
});

it('inherits from the related record chosen in the form of a new record', function (string $relation) {
    $user = User::factory()->create();
    inheritCategoryName($user, $relation);

    $category = Category::factory()->create(['name' => 'Shoes']);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->assertSet('data.user_attributes.category_name', null)
        ->set('data.category_id', $category->id)
        ->assertSet('data.user_attributes.category_name', 'Shoes');
})->with([
    'a foreign key named after the relation' => 'category',
    'a foreign key not named after the relation' => 'mainCategory',
]);

it('updates every attribute that inherits through the same field', function () {
    $user = User::factory()->create();
    inheritCategoryName($user, 'category', 'first_name');
    inheritCategoryName($user, 'mainCategory', 'second_name');

    $category = Category::factory()->create(['name' => 'Shoes']);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('data.category_id', $category->id)
        ->assertSet('data.user_attributes.first_name', 'Shoes')
        ->assertSet('data.user_attributes.second_name', 'Shoes');
});

it('only offers to inherit from relations with a single related record', function () {
    $productOptions = UserAttributeComponentFactoryRegistry::getInheritRelationOptions(
        (new UserAttributeConfig())->forceFill(['model_type' => Product::class])
    );
    $categoryOptions = UserAttributeComponentFactoryRegistry::getInheritRelationOptions(
        (new UserAttributeConfig())->forceFill(['model_type' => Category::class])
    );

    expect(array_keys($productOptions))->toEqualCanonicalizing(['__self', 'category', 'mainCategory'])
        ->and(array_keys($categoryOptions))->toBe(['__self']);
});
