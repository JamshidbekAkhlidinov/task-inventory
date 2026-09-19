<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Provider;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'storage_id' => Storage::factory(),
            'purchased_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'reference' => fake()->optional()->bothify('PO-####-????'),
        ];
    }
}
