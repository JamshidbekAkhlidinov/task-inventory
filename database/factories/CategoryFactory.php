<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->word(),
        ];
    }
}
