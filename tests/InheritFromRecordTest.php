<?php

namespace Luttje\FilamentUserAttributes\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

/**
 * A resource that only gives access to some of the categories, like a
 * resource scoped to a tenant would.
 */
class ScopedCategoryResource extends CategoryResource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('name', 'like', 'mine%');
    }
}

class CategoryViewPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->name === 'admin';
    }
}

function registerCategoryResource(string $resource)
{
    Config::set('filament-user-attributes.discover_resources', false);

    FilamentUserAttributes::registerResources([
        $resource => 'Category Page',
    ]);
}

it('finds the record to inherit from through the resource', function () {
    registerCategoryResource(CategoryResource::class);
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    expect(FilamentUserAttributes::findRecordToInheritFrom(Category::class, $category->id)?->id)
        ->toBe($category->id);
    expect(FilamentUserAttributes::findRecordToInheritFrom(Category::class, (string) $category->id)?->id)
        ->toBe($category->id);
});

it('does not find records outside the scope of the resource', function () {
    registerCategoryResource(ScopedCategoryResource::class);
    $this->actingAs(User::factory()->create());

    $mine = Category::factory()->create(['name' => 'mine 1']);
    $theirs = Category::factory()->create(['name' => 'theirs 1']);

    expect(FilamentUserAttributes::findRecordToInheritFrom(Category::class, $mine->id)?->id)
        ->toBe($mine->id);
    expect(FilamentUserAttributes::findRecordToInheritFrom(Category::class, $theirs->id))
        ->toBeNull();
});

it('does not find records the user may not view', function (string $name, bool $allowed) {
    registerCategoryResource(CategoryResource::class);
    Gate::policy(Category::class, CategoryViewPolicy::class);
    $this->actingAs(User::factory()->create(['name' => $name]));

    $category = Category::factory()->create();

    expect(FilamentUserAttributes::findRecordToInheritFrom(Category::class, $category->id) !== null)
        ->toBe($allowed);
})->with([
    'allowed by the policy' => ['admin', true],
    'denied by the policy' => ['someone else', false],
]);

it('does not find records for invalid keys', function (mixed $key) {
    registerCategoryResource(CategoryResource::class);
    $this->actingAs(User::factory()->create());

    Category::factory()->create();

    expect(FilamentUserAttributes::findRecordToInheritFrom(Category::class, $key))
        ->toBeNull();
})->with([
    'null' => [null],
    'an array' => [[1]],
    'an empty string' => [''],
    'a boolean' => [true],
    'a missing id' => [999999],
]);
