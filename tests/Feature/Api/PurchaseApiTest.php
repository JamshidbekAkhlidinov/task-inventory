<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_purchase_batch(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $response = $this->postJson('/api/purchases', [
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'reference' => 'PO-1001',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('reference', 'PO-1001');
        $response->assertJsonPath('items.0.quantity', 10);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/purchases', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider_id', 'storage_id', 'items']);
    }

    public function test_it_rejects_negative_unit_cost(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $response = $this->postJson('/api/purchases', [
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => -5],
            ],
        ]);

        $response->assertStatus(422);
    }
}
