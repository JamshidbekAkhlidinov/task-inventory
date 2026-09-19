<?php

namespace Database\Factories;

use App\Models\ClientOrder;
use App\Models\ClientOrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientOrderItemFactory extends Factory
{
    protected $model = ClientOrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 10, 1000);

        return [
            'order_id' => ClientOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
        ];
    }
}
