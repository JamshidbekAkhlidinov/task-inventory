<?php

namespace Database\Factories;

use App\Models\ClientOrderAllocation;
use App\Models\ClientRefund;
use App\Models\ClientRefundItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientRefundItemFactory extends Factory
{
    protected $model = ClientRefundItem::class;

    public function definition(): array
    {
        return [
            'refund_id' => ClientRefund::factory(),
            'order_allocation_id' => ClientOrderAllocation::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
