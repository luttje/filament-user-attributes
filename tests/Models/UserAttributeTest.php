<?php

namespace Luttje\FilamentUserAttributes\Tests\Models;

use Luttje\FilamentUserAttributes\Models\UserAttribute;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\User;

it('can add a custom attribute to a model', function () {
    $model = User::factory()->create();
    $attributes = ['key' => 'value'];

    $model->addUserAttribute($attributes);

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray($attributes);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can update a custom attribute on a model', function () {
    $model = User::factory()->create();
    $attribute = $model->addUserAttribute(['key' => 'value']);
    $newValues = ['key' => 'new value'];

    $model->updateUserAttributes($attribute, $newValues);

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray($newValues);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});

it('can remove a custom attribute from a model', function () {
    $model = User::factory()->create();
    $attribute = $model->addUserAttribute(['key' => 'value']);

    $model->removeUserAttribute($attribute);

    expect($model->userAttributes()->count())->toBe(0);
});

it('can perform json queries on custom attributes', function () {
    $model = User::factory()->create();
    $model->addUserAttribute([
        'key' => 'value',
        'array' => ['value1', 'value2'],
        'costs' => [
            'monday' => 10,
            'tuesday' => 20,
            'wednesday' => 30,
        ],
    ]);

    $model->addUserAttribute([
        'key' => 'value2',
        'array' => ['value4'],
        'costs' => [
            'monday' => 5,
            'tuesday' => 7,
            'wednesday' => 9,
        ],
    ]);

    expect($model->userAttributes()->where('values->key', 'value')->count())->toBe(1);
    expect($model->userAttributes()->whereJsonContains('values->array', 'value1')->count())->toBe(1);
    expect($model->userAttributes()->whereJsonContains('values->array', 'value3')->count())->toBe(0);
    expect($model->userAttributes()->whereJsonLength('values->array', 2)->count())->toBe(1);
    expect($model->userAttributes()->whereJsonLength('values->array', '>', 2)->count())->toBe(0);
    expect($model->userAttributes()->sum('values->costs->monday'))->toEqual(15);
});

it('can update a single attribute on a model', function () {
    $model = User::factory()->create();
    $attribute = $model->addUserAttribute(['key' => 'value']);

    $model->updateUserAttribute($attribute, 'key', 'new value');

    expect($model->userAttributes()->count())->toBe(1);
    expect($model->userAttributes()->first()->values)->toMatchArray(['key' => 'new value']);
    expect($model->userAttributes()->first()->model->id)->toBe($model->id);
});
