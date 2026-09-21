<?php

namespace Tests\Unit\Services;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseService = app(PurchaseService::class);
    }

    public function test_it_can_create_purchase(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $batch = $this->purchaseService->create(
            provider: $provider,
            storage: $storage,
            items: [
                [
                    'product_id' => $product1->id,
                    'quantity' => 10,
                    'unit_cost' => 100000,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 20,
                    'unit_cost' => 200000,
                ],
            ],
            reference: 'PO-0001',
        );

        $this->assertInstanceOf(Batch::class, $batch);

        $this->assertSame(
            $provider->id,
            $batch->provider_id
        );

        $this->assertSame(
            $storage->id,
            $batch->storage_id
        );

        $this->assertSame(
            'PO-0001',
            $batch->reference
        );

        $this->assertDatabaseHas('batch_items', [
            'batch_id' => $batch->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100000,
        ]);

        $this->assertDatabaseHas('batch_items', [
            'batch_id' => $batch->id,
            'product_id' => $product2->id,
            'quantity' => 20,
            'available_quantity' => 20,
            'unit_cost' => 200000,
        ]);
    }

    public function test_it_increases_storage_stock(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->purchaseService->create(
            provider: $provider,
            storage: $storage,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 15,
                    'unit_cost' => 100000,
                ],
            ],
        );

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 15,
        ]);
    }

    public function test_it_creates_purchase_stock_movement(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = $this->purchaseService->create(
            provider: $provider,
            storage: $storage,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 50000,
                ],
            ],
        );

        $this->assertDatabaseHas('stock_movements', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'type' => StockMovementType::PURCHASE->value,
            'quantity' => 10,
            'reference_type' => $batch->getMorphClass(),
            'reference_id' => $batch->id,
        ]);
    }

    public function test_it_rejects_empty_purchase(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->purchaseService->create(
            provider: $provider,
            storage: $storage,
            items: [],
        );
    }

    public function test_it_rejects_invalid_quantity(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->purchaseService->create(
            provider: $provider,
            storage: $storage,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'unit_cost' => 100000,
                ],
            ],
        );
    }

    public function test_it_rejects_negative_unit_cost(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->purchaseService->create(
            provider: $provider,
            storage: $storage,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => -100,
                ],
            ],
        );
    }
}
