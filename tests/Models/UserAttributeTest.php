<?php

namespace Luttje\FilamentUserAttributes\Tests\Models;

use Luttje\FilamentUserAttributes\Models\UserAttribute;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\User;

it('can add a custom attribute to a new model', function () {
    $model = Product::factory()->make();
    $attributes = UserAttribute::make(['color' => 'red']);

    $model->user_attributes = $attributes;

    // It shouldn't be saved yet
    expect($model->userAttributes()->count())->toBe(0);

    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray((array) $attributes);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can add a custom attribute to an existing model', function () {
    $model = User::factory()->create();
    $attributes = UserAttribute::make(['key' => 'value']);

    $model->user_attributes = $attributes;
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray((array) $attributes);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can update a custom attribute on an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $newValues = UserAttribute::make(['key' => 'new value']);

    $model->user_attributes = $newValues;
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray((array) $newValues);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
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
    expect($model->userAttributes()->first()->values)->toMatchArray(['key' => 'value']);

    $model->user_attributes->key = 'new value';
    $model->save();

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray(['key' => 'new value']);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can get a single attribute from an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $model->save();

    expect($model->user_attributes->key)->toBe('value');
    expect(User::allUserAttributes('key')->toArray())->toMatchArray(['value']);
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
