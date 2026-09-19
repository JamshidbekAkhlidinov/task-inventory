<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchItemFactory extends Factory
{
    protected $model = BatchItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(10, 100);

        return [
            'batch_id' => Batch::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'available_quantity' => $quantity,
            'unit_cost' => fake()->randomFloat(2, 5, 500),
        ];
    }
}
