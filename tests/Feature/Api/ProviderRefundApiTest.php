<?php

namespace Tests\Feature\Api;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Models\StorageStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRefundApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refunds_products_to_the_provider(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $response = $this->postJson('/api/provider-refunds', [
            'batch_id' => $batch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('items.0.quantity', 4);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 6,
        ]);
    }

    public function test_it_rejects_a_refund_exceeding_batch_stock(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 3,
            'unit_cost' => 100,
        ]);

        $response = $this->postJson('/api/provider-refunds', [
            'batch_id' => $batch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('provider_refunds', 0);
    }
}
