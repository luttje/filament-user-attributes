<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Tests\Mocks\Database\Factories\ProductFactory;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class Product extends Model
{
    use HasFactory;
    use HasUserAttributes;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return ProductFactory::new();
    }
}
