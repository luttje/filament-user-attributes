<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Tests\Mocks\Database\Factories\CategoryFactory;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class Category extends Model implements HasUserAttributesContract
{
    use HasFactory;
    use HasUserAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
    ];

    public static function getUserAttributesConfig(): ?HasUserAttributesConfigContract
    {
        /** @var \Luttje\FilamentUserAttributes\Tests\Mocks\Models\User */
        $user = Auth::user();

        return $user;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return CategoryFactory::new();
    }
}
