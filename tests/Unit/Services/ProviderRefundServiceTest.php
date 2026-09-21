<?php

namespace Tests\Unit\Services;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Models\StorageStock;
use App\Services\ProviderRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createStockScenario(): array
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
            'available_quantity' => 20,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        return compact('provider', 'category', 'product', 'storage', 'batch', 'batchItem');
    }

    public function test_it_can_partially_refund_a_batch_to_the_provider(): void
    {
        $data = $this->createStockScenario();

        $refund = app(ProviderRefundService::class)->create(
            batch: $data['batch'],
            items: [
                ['product_id' => $data['product']->id, 'quantity' => 5],
            ],
        );

        $this->assertDatabaseHas('provider_refunds', [
            'id' => $refund->id,
            'batch_id' => $data['batch']->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('provider_refund_items', [
            'refund_id' => $refund->id,
            'batch_item_id' => $data['batchItem']->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('batch_items', [
            'id' => $data['batchItem']->id,
            'available_quantity' => 15,
        ]);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $data['storage']->id,
            'product_id' => $data['product']->id,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'storage_id' => $data['storage']->id,
            'product_id' => $data['product']->id,
            'batch_id' => $data['batch']->id,
            'type' => StockMovementType::PROVIDER_REFUND->value,
            'quantity' => 5,
        ]);
    }

    public function test_it_can_fully_refund_a_batch_item(): void
    {
        $data = $this->createStockScenario();

        app(ProviderRefundService::class)->create(
            batch: $data['batch'],
            items: [
                ['product_id' => $data['product']->id, 'quantity' => 20],
            ],
        );

        $this->assertDatabaseHas('batch_items', [
            'id' => $data['batchItem']->id,
            'available_quantity' => 0,
        ]);
    }

    public function test_it_rejects_a_refund_exceeding_available_batch_quantity(): void
    {
        $data = $this->createStockScenario();

        $this->expectException(\RuntimeException::class);

        app(ProviderRefundService::class)->create(
            batch: $data['batch'],
            items: [
                ['product_id' => $data['product']->id, 'quantity' => 100],
            ],
        );
    }

    public function test_it_rolls_back_when_one_item_in_the_refund_fails(): void
    {
        $data = $this->createStockScenario();

        try {
            app(ProviderRefundService::class)->create(
                batch: $data['batch'],
                items: [
                    ['product_id' => $data['product']->id, 'quantity' => 5],
                    ['product_id' => $data['product']->id, 'quantity' => 100],
                ],
            );
        } catch (\RuntimeException) {
            // Expected exception.
        }

        $this->assertDatabaseCount('provider_refunds', 0);
        $this->assertDatabaseCount('provider_refund_items', 0);

        $this->assertDatabaseHas('batch_items', [
            'id' => $data['batchItem']->id,
            'available_quantity' => 20,
        ]);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $data['storage']->id,
            'product_id' => $data['product']->id,
            'quantity' => 20,
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_it_rejects_an_empty_refund(): void
    {
        $data = $this->createStockScenario();

        $this->expectException(\InvalidArgumentException::class);

        app(ProviderRefundService::class)->create(
            batch: $data['batch'],
            items: [],
        );
    }
}
