<?php

namespace Luttje\FilamentUserAttributes\Tests\Mocks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Luttje\FilamentUserAttributes\Tests\Mocks\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->slug,
            'description' => $this->faker->text,
        ];
    }
}
