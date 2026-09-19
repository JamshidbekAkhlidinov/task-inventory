<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Storage;
use App\Models\StorageStock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageStockFactory extends Factory
{
    protected $model = StorageStock::class;

    public function definition(): array
    {
        return [
            'storage_id' => Storage::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(0, 100),
        ];
    }
}
