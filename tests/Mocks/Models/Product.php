<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class Product extends Model
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
}
