<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->slug,
            'description' => $this->faker->text,
            'price' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }
}
