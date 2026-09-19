<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientOrderAllocationFactory extends Factory
{
    protected $model = ClientOrderAllocation::class;

    public function definition(): array
    {
        return [
            'order_item_id' => ClientOrderItem::factory(),
            'batch_id' => Batch::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_cost' => fake()->randomFloat(2, 5, 500),
        ];
    }
}
