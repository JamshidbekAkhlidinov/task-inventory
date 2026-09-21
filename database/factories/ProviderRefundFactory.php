<?php

namespace Database\Factories;

use App\Enums\ProviderRefundStatus;
use App\Models\Batch;
use App\Models\ProviderRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderRefundFactory extends Factory
{
    protected $model = ProviderRefund::class;

    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'status' => ProviderRefundStatus::COMPLETED,
            'refunded_at' => now(),
        ];
    }
}
