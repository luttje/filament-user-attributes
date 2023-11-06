<?php

namespace Luttje\FilamentUserAttributes\Tests\Models;

use Luttje\FilamentUserAttributes\Models\UserAttribute;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

it('can add a custom attribute to a new model', function () {
    $model = Product::factory()->make();
    $attributes = UserAttribute::make(['color' => 'red']);

    $model->user_attributes = $attributes;

    // It shouldn't be saved yet
    expect($model->userAttributes()->count())->toBe(0);

    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray((array) $attributes);
    expect($model->userAttributes->model->id)->toBe($model->id);
});

it('can add arrays to a new model', function () {
    $model = Product::factory()->make();

    $model->user_attributes->color = 'red';
    $model->user_attributes->sizes = ['small', 'medium', 'large'];
    $model->user_attributes->materials = ['cotton', 'polyester'];
    $model->user_attributes->stock = [
        'small' => [
            'cotton' => 10,
            'polyester' => 5,
        ],
        'medium' => [
            'cotton' => 20,
            'polyester' => 15,
        ],
    ];

    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValue('color'))->toBe('red');
    expect($model->getUserAttributeValue('sizes'))->toMatchArray(['small', 'medium', 'large']);
    expect($model->getUserAttributeValue('materials'))->toMatchArray(['cotton', 'polyester']);
    expect($model->getUserAttributeValue('stock.small.cotton'))->toBe(10);
    expect($model->getUserAttributeValue('stock.medium.polyester'))->toBe(15);
});

it('can add a custom attribute to an existing model', function () {
    $model = User::factory()->create();
    $attributes = UserAttribute::make(['key' => 'value']);

    $model->user_attributes = $attributes;
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray((array) $attributes);
    expect($model->userAttributes->model->id)->toBe($model->id);
});

it('can update a custom attribute on an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $newValues = UserAttribute::make(['key' => 'new value']);

    $model->user_attributes = $newValues;
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray((array) $newValues);
    expect($model->userAttributes->model->id)->toBe($model->id);
});

it('can remove a custom attribute from an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);

    unset($model->user_attributes);
    $model->save();

    expect($model->userAttributes()->count())->toBe(0);
});

it('throws exception when setting a custom attribute if the attributes were destroyed', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    unset($model->user_attributes);

    $model->user_attributes->key = 'new value';
})->throws(\Exception::class);

it('throws exception when setting attributes if the attributes were destroyed', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    unset($model->user_attributes);

    $model->user_attributes = UserAttribute::make(['key' => 'value2']);
})->throws(\Exception::class);

it('throws if trying to set a non-object as attributes', function (mixed $data) {
    $model = User::factory()->create();
    $model->user_attributes = $data;
})->throws(\Exception::class)->with([
    'string' => 'string',
    'int' => 1,
    'float' => 1.1,
    'array' => ['key' => 'value'],
    'null' => null,
    'bool' => true,
    'callable' => fn () => null,
]);

it('can perform json queries on custom attributes', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make([
        'key' => 'value',
        'array' => ['value1', 'value2'],
        'costs' => [
            'monday' => 10,
            'tuesday' => 20,
            'wednesday' => 30,
        ],
    ]);
    $model->save();

    $model2 = User::factory()->create();
    $model2->user_attributes = UserAttribute::make([
        'key' => 'value2',
        'array' => ['value4'],
        'costs' => [
            'monday' => 5,
            'tuesday' => 7,
            'wednesday' => 9,
        ],
    ]);
    $model2->save();

    expect(User::whereUserAttribute('key', 'value')->count())->toBe(1);
    expect(User::whereUserAttributeContains('array', 'value1')->count())->toBe(1);
    expect(User::whereUserAttributeContains('array', 'value3')->count())->toBe(0);
    expect(User::whereUserAttributeLength('array', 2)->count())->toBe(1);
    expect(User::whereUserAttributeLength('array', '>', 2)->count())->toBe(0);
    expect(User::userAttributeSum('costs->monday'))->toBe(15);
});

it('can update a single attribute on an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'value']);

    $model->user_attributes->key = 'new value';
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'new value']);
    expect($model->userAttributes->model->id)->toBe($model->id);
});

it('can get a single attribute from an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    expect($model->user_attributes->key)->toBe('value');
    expect(User::allUserAttributes('key')->toArray())->toMatchArray(['value']);
});

it('will get null when no user attributes are set on an existing model', function () {
    $model = User::factory()->create();

    expect($model->user_attributes->key)->toBeNull();
    expect(User::allUserAttributes('key')->toArray())->toMatchArray([]);
});

it('can check if an existing model has a single attribute', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    expect($model->hasUserAttribute('key'))->toBeTrue();
    expect($model->hasUserAttribute('key2'))->toBeFalse();
});

it('can get a single attribute from multiple models', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    $model2 = User::factory()->create();
    $model2->user_attributes = UserAttribute::make(['key' => 'value2']);
    $model2->save();

    expect(User::allUserAttributes('key')->toArray())->toMatchArray(['value', 'value2']);
});

it('does not break regular unset', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    unset($model->name);

    expect($model->name)->toBeNull();
});

it('can create a model with user attributes through mass assignment', function () {
    $model = User::factory()->create([
        'user_attributes' => UserAttribute::make(['key' => 'value']),
    ]);

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'value']);
    expect($model->userAttributes->model->id)->toBe($model->id);
    expect($model->user_attributes->key)->toBe('value');
});
