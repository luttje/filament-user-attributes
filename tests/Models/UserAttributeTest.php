<?php

namespace Luttje\FilamentUserAttributes\Tests\Models;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\ProductButGuarded;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

it('can add a custom attribute to a new model', function () {
    $model = Product::factory()->make([
        'name' => 'Test Product',
    ]);
    $attributes = (['color' => 'red']);

    $model->user_attributes = $attributes;

    // It shouldn't be saved yet
    expect($model->userAttribute()->count())->toBe(0);

    $model->save();

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray($attributes);
    expect($model->userAttribute->model->id)->toBe($model->id);
    expect($model->name)->toBe('Test Product');
});

it('can add arrays to a new model', function () {
    $model = Product::factory()->make([
        'name' => 'Test Product',
    ]);

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

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValue('color'))->toBe('red');
    expect($model->getUserAttributeValue('sizes'))->toMatchArray(['small', 'medium', 'large']);
    expect($model->getUserAttributeValue('materials'))->toMatchArray(['cotton', 'polyester']);
    expect($model->getUserAttributeValue('stock.small.cotton'))->toBe(10);
    expect($model->getUserAttributeValue('stock.medium.polyester'))->toBe(15);
    expect($model->name)->toBe('Test Product');
});

it('can add a custom attribute to an existing model', function () {
    $model = User::factory()->create();
    $attributes = (['key' => 'value']);

    $model->user_attributes = $attributes;
    $model->save();

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray($attributes);
    expect($model->userAttribute->model->id)->toBe($model->id);
});

it('can update a custom attribute on an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);
    $newValues = (['key' => 'new value']);

    $model->user_attributes = $newValues;
    $model->save();

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray($newValues);
    expect($model->userAttribute->model->id)->toBe($model->id);
});

it('can remove a custom attribute from an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);

    unset($model->user_attributes);
    $model->save();

    expect($model->userAttribute()->count())->toBe(0);
});

it('throws exception when setting a custom attribute if the attributes were destroyed', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);
    $model->save();

    unset($model->user_attributes);

    $model->user_attributes->key = 'new value';
})->throws(\LogicException::class);

it('throws exception when setting attributes if the attributes were destroyed', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);
    $model->save();

    unset($model->user_attributes);

    $model->user_attributes = (['key' => 'value2']);
})->throws(\LogicException::class);

it('throws if trying to set a non-object as attributes', function (mixed $data) {
    $model = User::factory()->create();
    $model->user_attributes = $data;
})->throws(\InvalidArgumentException::class)->with([
    'string' => 'string',
    'int' => 1,
    'float' => 1.1,
    'null' => null,
    'bool' => true,
    'callable' => fn () => null,
]);

it('can perform json queries on custom attributes', function () {
    $model = User::factory()->create();
    $model->user_attributes = ([
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
    $model2->user_attributes = ([
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
    $model->user_attributes = (['key' => 'value']);
    $model->save();

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'value']);

    $model->user_attributes->key = 'new value';
    $model->save();

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'new value']);
    expect($model->userAttribute->model->id)->toBe($model->id);
});

it('can get a single attribute from an existing model', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);
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
    $model->user_attributes = (['key' => 'value']);
    $model->save();

    expect($model->hasUserAttribute('key'))->toBeTrue();
    expect($model->hasUserAttribute('key2'))->toBeFalse();
});

it('can get a single attribute from multiple models', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);
    $model->save();

    $model2 = User::factory()->create();
    $model2->user_attributes = (['key' => 'value2']);
    $model2->save();

    expect(User::allUserAttributes('key')->toArray())->toMatchArray(['value', 'value2']);
});

it('does not break regular unset', function () {
    $model = User::factory()->create();
    $model->user_attributes = (['key' => 'value']);
    $model->save();

    unset($model->name);

    expect($model->name)->toBeNull();
});

it('can create a model with user attributes through mass assignment', function () {
    $model = User::factory()->create([
        'user_attributes' => (['key' => 'value']),
    ]);

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'value']);
    expect($model->userAttribute->model->id)->toBe($model->id);
    expect($model->user_attributes->key)->toBe('value');
});

// Since filament will use mass assignment to create or update models, we need to ensure that the user attributes can be set even if the mass assignable filter is guarded.
it('can add a custom attribute when mass assignable filter is guarded', function () {
    $model = new ProductButGuarded([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'description' => 'A cool product',
        'price' => 10.22,
        'some_process_data' => 'This is process data that should not be mass assignable',
        'user_attributes' => [
            'key' => 'value',
        ]
    ]);

    $model->save();

    expect($model->userAttribute()->count())->toBe(1);
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'value']);
    expect($model->userAttribute->model->id)->toBe($model->id);
    expect($model->user_attributes->key)->toBe('value');
    expect($model->name)->toBe('Test Product');
});

it('can read user attributes that are set but not yet saved', function () {
    $model = User::factory()->create();
    $model->user_attributes = ['key' => 'value'];
    $model->save();

    $model->user_attributes->key = 'new value';
    $model->user_attributes->other = 'other value';

    expect($model->user_attributes->key)->toBe('new value');
    expect($model->user_attributes->other)->toBe('other value');
    expect($model->hasUserAttribute('other'))->toBeTrue();
    expect($model->getUserAttributeValues())->toMatchArray(['key' => 'new value', 'other' => 'other value']);
});

it('can use the user attributes like an array', function () {
    $model = User::factory()->create();
    $model->user_attributes['color'] = 'red';
    $model->user_attributes['size'] = 'large';
    $model->save();

    $model = User::find($model->id);

    expect($model->user_attributes['color'])->toBe('red');
    expect(isset($model->user_attributes['size']))->toBeTrue();
    expect(isset($model->user_attributes['missing']))->toBeFalse();
    expect(count($model->user_attributes))->toBe(2);
    expect(iterator_to_array($model->user_attributes))->toEqual(['color' => 'red', 'size' => 'large']);
    expect($model->user_attributes->toArray())->toEqual(['color' => 'red', 'size' => 'large']);
});

it('keeps the user attributes in sync after first saving them', function () {
    $model = User::factory()->create();

    // Loads the (still missing) relation
    expect($model->user_attributes->key)->toBeNull();

    $model->user_attributes->key = 'value';
    $model->save();

    expect($model->user_attributes->key)->toBe('value');
});

it('does not query the database to check if a user attribute is set', function () {
    $model = User::factory()->create();
    $model->user_attributes = ['key' => 'value'];
    $model->save();

    $model = User::with('userAttribute')->find($model->id);

    \Illuminate\Support\Facades\DB::enableQueryLog();
    \Illuminate\Support\Facades\DB::flushQueryLog();

    expect($model->hasUserAttribute('key'))->toBeTrue();
    expect(isset($model->user_attributes->key))->toBeTrue();
    expect(isset($model->user_attributes->missing))->toBeFalse();

    expect(\Illuminate\Support\Facades\DB::getQueryLog())->toBeEmpty();
});

it('can chain user attribute query scopes', function () {
    $model = User::factory()->create(['name' => 'Alice']);
    $model->user_attributes = ['key' => 'value', 'amount' => 10, 'nested' => ['deep' => 'yes']];
    $model->save();

    $model2 = User::factory()->create(['name' => 'Bob']);
    $model2->user_attributes = ['key' => 'value', 'amount' => 5];
    $model2->save();

    expect(User::where('name', 'Alice')->whereUserAttribute('key', 'value')->count())->toBe(1);
    expect(User::whereUserAttribute('amount', '>', 6)->pluck('name')->all())->toBe(['Alice']);
    expect(User::whereUserAttribute('nested.deep', 'yes')->count())->toBe(1);
    expect(User::query()->whereUserAttribute('key', 'value')->whereUserAttribute('amount', 5)->pluck('name')->all())->toBe(['Bob']);
});

it('sums decimal user attributes without losing precision', function () {
    $model = User::factory()->create();
    $model->user_attributes = ['price' => 1.25];
    $model->save();

    $model2 = User::factory()->create();
    $model2->user_attributes = ['price' => 2.5];
    $model2->save();

    expect(User::userAttributeSum('price'))->toBe(3.75);
});
