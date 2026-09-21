<?php

namespace Tests\Unit\Services;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Models\StorageStock;
use App\Services\ClientRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createSoldOrderScenario(): array
    {
        $provider = Provider::factory()->create();

        $category = Category::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sale_price' => 150000,
        ]);

        $storage = Storage::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        $batchItem = BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'available_quantity' => 15,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 15,
        ]);

        $client = Client::factory()->create();

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 150000,
            'total_price' => 750000,
        ]);

        $allocation = ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
            'unit_cost' => 100000,
        ]);

        return compact(
            'provider',
            'product',
            'storage',
            'batch',
            'batchItem',
            'client',
            'order',
            'orderItem',
            'allocation',
        );
    }

    public function test_it_can_partially_refund_a_client_order_item(): void
    {
        $data = $this->createSoldOrderScenario();

        $refund = app(ClientRefundService::class)->create(
            order: $data['order'],
            items: [
                ['order_item_id' => $data['orderItem']->id, 'quantity' => 3],
            ],
        );

        $this->assertDatabaseHas('client_refunds', [
            'id' => $refund->id,
            'order_id' => $data['order']->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('client_refund_items', [
            'refund_id' => $refund->id,
            'order_allocation_id' => $data['allocation']->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('batch_items', [
            'id' => $data['batchItem']->id,
            'available_quantity' => 18,
        ]);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $data['storage']->id,
            'product_id' => $data['product']->id,
            'quantity' => 18,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'storage_id' => $data['storage']->id,
            'product_id' => $data['product']->id,
            'batch_id' => $data['batch']->id,
            'type' => StockMovementType::CLIENT_REFUND->value,
            'quantity' => 3,
        ]);
    }

    public function test_it_restores_units_to_the_original_batch_across_multiple_allocations(): void
    {
        $data = $this->createSoldOrderScenario();

        $secondBatch = Batch::factory()->create([
            'provider_id' => $data['provider']->id,
            'storage_id' => $data['storage']->id,
        ]);

        $secondBatchItem = BatchItem::factory()->create([
            'batch_id' => $secondBatch->id,
            'product_id' => $data['product']->id,
            'quantity' => 20,
            'available_quantity' => 18,
            'unit_cost' => 120000,
        ]);

        $secondAllocation = ClientOrderAllocation::factory()->create([
            'order_item_id' => $data['orderItem']->id,
            'batch_id' => $secondBatch->id,
            'quantity' => 2,
            'unit_cost' => 120000,
        ]);

        $data['orderItem']->update(['quantity' => 7]);

        app(ClientRefundService::class)->create(
            order: $data['order'],
            items: [
                ['order_item_id' => $data['orderItem']->id, 'quantity' => 6],
            ],
        );

        $this->assertDatabaseHas('client_refund_items', [
            'order_allocation_id' => $data['allocation']->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('client_refund_items', [
            'order_allocation_id' => $secondAllocation->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('batch_items', [
            'id' => $data['batchItem']->id,
            'available_quantity' => 20,
        ]);

        $this->assertDatabaseHas('batch_items', [
            'id' => $secondBatchItem->id,
            'available_quantity' => 19,
        ]);
    }

    public function test_it_rejects_refund_above_order_quantity(): void
    {
        $data = $this->createSoldOrderScenario();

        $this->expectException(\RuntimeException::class);

        app(ClientRefundService::class)->create(
            order: $data['order'],
            items: [
                ['order_item_id' => $data['orderItem']->id, 'quantity' => 10],
            ],
        );
    }

    public function test_it_rejects_refunding_the_same_units_twice(): void
    {
        $data = $this->createSoldOrderScenario();

        app(ClientRefundService::class)->create(
            order: $data['order'],
            items: [
                ['order_item_id' => $data['orderItem']->id, 'quantity' => 5],
            ],
        );

        $this->expectException(\RuntimeException::class);

        app(ClientRefundService::class)->create(
            order: $data['order'],
            items: [
                ['order_item_id' => $data['orderItem']->id, 'quantity' => 1],
            ],
        );
    }

    public function test_it_rejects_an_empty_refund(): void
    {
        $data = $this->createSoldOrderScenario();

        $this->expectException(\InvalidArgumentException::class);

        app(ClientRefundService::class)->create(
            order: $data['order'],
            items: [],
        );
    }
}
