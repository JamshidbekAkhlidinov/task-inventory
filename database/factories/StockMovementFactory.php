<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'storage_id' => Storage::factory(),
            'product_id' => Product::factory(),
            'batch_id' => Batch::factory(),
            'type' => fake()->randomElement(StockMovementType::cases()),
            'quantity' => fake()->numberBetween(1, 50),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}
