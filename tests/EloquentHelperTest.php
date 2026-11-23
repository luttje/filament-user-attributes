<?php

namespace Luttje\FilamentUserAttributes\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Luttje\FilamentUserAttributes\EloquentHelper;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

class RelationDiscoveryModel extends Model
{
    public static array $called = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function untypedRelation()
    {
        static::$called[] = __FUNCTION__;

        return $this->belongsTo(User::class);
    }

    public function sendInvoice()
    {
        static::$called[] = __FUNCTION__;
    }

    public function archive(): bool
    {
        static::$called[] = __FUNCTION__;

        return true;
    }

    public static function purgeEverything()
    {
        static::$called[] = __FUNCTION__;
    }

    public static function staticRelation(): BelongsTo
    {
        static::$called[] = __FUNCTION__;

        return (new static())->belongsTo(User::class);
    }

    public function scopedProducts(string $scope): HasMany
    {
        static::$called[] = __FUNCTION__;

        return $this->hasMany(Product::class);
    }
}

beforeEach(function () {
    RelationDiscoveryModel::$called = [];
});

it('discovers relations that declare a relation return type', function () {
    $relations = collect(EloquentHelper::discoverRelations(RelationDiscoveryModel::class))
        ->keyBy('name');

    expect($relations->keys()->sort()->values()->all())->toBe(['category', 'products']);

    expect($relations['category']->relationTypeShort)->toBe('belongsTo');
    expect($relations['category']->relationType)->toBe(BelongsTo::class);
    expect($relations['category']->relatedType)->toBe(Category::class);

    expect($relations['products']->relationTypeShort)->toBe('hasMany');
    expect($relations['products']->relatedType)->toBe(Product::class);
});

it('never calls methods that are not declared as a relation while discovering', function () {
    EloquentHelper::discoverRelations(RelationDiscoveryModel::class);
    EloquentHelper::discoverRelations(new RelationDiscoveryModel());

    expect(RelationDiscoveryModel::$called)->toBe([]);
});

it('can get the info for a single relation', function () {
    expect(EloquentHelper::getRelationInfo(RelationDiscoveryModel::class, 'category')->relatedType)
        ->toBe(Category::class);

    expect(EloquentHelper::getRelationInfo(RelationDiscoveryModel::class, 'untypedRelation'))
        ->toBeNull();

    expect(EloquentHelper::getRelationInfo(RelationDiscoveryModel::class, 'doesNotExist'))
        ->toBeNull();
});

class VisibleOnlyModel extends Model
{
    protected $visible = ['name'];
}

it('knows which attributes a model hides', function () {
    expect(EloquentHelper::isHiddenAttribute(User::class, 'password'))->toBeTrue();
    expect(EloquentHelper::isHiddenAttribute(new User(), 'remember_token'))->toBeTrue();
    expect(EloquentHelper::isHiddenAttribute(User::class, 'password.nested'))->toBeTrue();
    expect(EloquentHelper::isHiddenAttribute(User::class, 'name'))->toBeFalse();

    expect(EloquentHelper::isHiddenAttribute(VisibleOnlyModel::class, 'name'))->toBeFalse();
    expect(EloquentHelper::isHiddenAttribute(VisibleOnlyModel::class, 'secret'))->toBeTrue();
});
