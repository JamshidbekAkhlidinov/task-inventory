<?php

namespace Database\Factories;

use App\Models\BatchItem;
use App\Models\ProviderRefund;
use App\Models\ProviderRefundItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderRefundItemFactory extends Factory
{
    protected $model = ProviderRefundItem::class;

    public function definition(): array
    {
        return [
            'refund_id' => ProviderRefund::factory(),
            'batch_item_id' => BatchItem::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
