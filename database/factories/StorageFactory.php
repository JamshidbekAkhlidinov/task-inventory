<?php

namespace Database\Factories;

use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageFactory extends Factory
{
    protected $model = Storage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Warehouse',
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
