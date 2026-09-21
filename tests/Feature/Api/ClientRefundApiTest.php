<?php

namespace Tests\Feature\Api;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Models\StorageStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRefundApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refunds_products_from_a_client_order(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $client = Client::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 5,
            'unit_cost' => 100,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 150,
            'total_price' => 750,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
            'unit_cost' => 100,
        ]);

        $response = $this->postJson('/api/client-refunds', [
            'order_id' => $order->id,
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('items.0.quantity', 2);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);
    }

    public function test_it_rejects_refund_above_refundable_quantity(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $batch = Batch::factory()->create(['storage_id' => $storage->id]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
        ]);

        $response = $this->postJson('/api/client-refunds', [
            'order_id' => $order->id,
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 999],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('client_refunds', 0);
    }
}
