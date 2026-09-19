<?php

namespace Database\Factories;

use App\Enums\ClientOrderStatus;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientOrderFactory extends Factory
{
    protected $model = ClientOrder::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'storage_id' => Storage::factory(),
            'status' => ClientOrderStatus::PENDING,
            'total_amount' => 0,
            'ordered_at' => now(),
        ];
    }
}
