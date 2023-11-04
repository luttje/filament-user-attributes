<?php

namespace Luttje\FilamentUserAttributes\Tests\Models;

use Luttje\FilamentUserAttributes\Models\UserAttribute;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\User;

it('can add a custom attribute to a model', function () {
    $model = User::factory()->create();
    $attributes = UserAttribute::make(['key' => 'value']);

    $model->user_attributes = $attributes;

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray((array)$attributes);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can update a custom attribute on a model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);
    $newValues = UserAttribute::make(['key' => 'new value']);

    $model->user_attributes = $newValues;

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray((array)$newValues);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can remove a custom attribute from a model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);

    unset($model->user_attributes);

    expect($model->userAttributes()->count())->toBe(0);
});

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

    expect(User::whereUserAttribute('key', 'value')->count())->toBe(1);
    expect(User::whereUserAttributeContains('array', 'value1')->count())->toBe(1);
    expect(User::whereUserAttributeContains('array', 'value3')->count())->toBe(0);
    expect(User::whereUserAttributeLength('array', 2)->count())->toBe(1);
    expect(User::whereUserAttributeLength('array', '>', 2)->count())->toBe(0);
    expect(User::userAttributeSum('costs->monday'))->toBe(15);
});

it('can update a single attribute on a model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);

    $model->user_attributes->key = 'new value';

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray(['key' => 'new value']);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can get a single attribute from a model', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);

    expect($model->user_attributes->key)->toBe('value');
    expect(User::allUserAttributes('key')->toArray())->toMatchArray(['value']);
});

it('can check if a model has a single attribute', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);

    expect($model->hasUserAttribute('key'))->toBeTrue();
    expect($model->hasUserAttribute('key2'))->toBeFalse();
});

it('can get a single attribute from multiple models', function () {
    $model = User::factory()->create();
    $model->user_attributes = UserAttribute::make(['key' => 'value']);

    $model2 = User::factory()->create();
    $model2->user_attributes = UserAttribute::make(['key' => 'value2']);

    expect(User::allUserAttributes('key')->toArray())->toMatchArray(['value', 'value2']);
});
