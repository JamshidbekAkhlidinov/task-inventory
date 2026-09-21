<?php

namespace Database\Factories;

use App\Enums\ClientRefundStatus;
use App\Models\ClientOrder;
use App\Models\ClientRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientRefundFactory extends Factory
{
    protected $model = ClientRefund::class;

    public function definition(): array
    {
        return [
            'order_id' => ClientOrder::factory(),
            'status' => ClientRefundStatus::COMPLETED,
            'refunded_at' => now(),
        ];
    }
}
