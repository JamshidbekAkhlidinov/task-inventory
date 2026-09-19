<?php

namespace Database\Seeders;

use App\Enums\ClientOrderStatus;
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
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\StorageStock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Provider
        |--------------------------------------------------------------------------
        */

        $provider = Provider::factory()->create([
            'name' => 'Test Supplier',
            'phone' => '+998901234567',
            'email' => 'supplier@example.com',
            'address' => 'Tashkent, Uzbekistan',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $category = Category::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Electronics',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Wireless Mouse',
            'sale_price' => 150000,
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'sale_price' => 650000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Storage
        |--------------------------------------------------------------------------
        */

        $storage = Storage::factory()->create([
            'name' => 'Main Warehouse',
            'address' => 'Tashkent, Uzbekistan',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Batch #1
        |--------------------------------------------------------------------------
        */

        $batch1 = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'purchased_at' => now()->subDays(20),
            'reference' => 'PO-0001',
        ]);

        $batch1Item1 = BatchItem::factory()->create([
            'batch_id' => $batch1->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100000,
        ]);

        $batch1Item2 = BatchItem::factory()->create([
            'batch_id' => $batch1->id,
            'product_id' => $product2->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 500000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Batch #2
        |--------------------------------------------------------------------------
        */

        $batch2 = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'purchased_at' => now()->subDays(10),
            'reference' => 'PO-0002',
        ]);

        $batch2Item1 = BatchItem::factory()->create([
            'batch_id' => $batch2->id,
            'product_id' => $product1->id,
            'quantity' => 20,
            'available_quantity' => 20,
            'unit_cost' => 110000,
        ]);

        $batch2Item2 = BatchItem::factory()->create([
            'batch_id' => $batch2->id,
            'product_id' => $product2->id,
            'quantity' => 20,
            'available_quantity' => 20,
            'unit_cost' => 520000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Storage Stock
        |--------------------------------------------------------------------------
        */

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product1->id,
            'quantity' => 30,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product2->id,
            'quantity' => 30,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Stock Movements - Purchases
        |--------------------------------------------------------------------------
        */

        StockMovement::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product1->id,
            'batch_id' => $batch1->id,
            'type' => StockMovementType::PURCHASE,
            'quantity' => 10,
            'reference_type' => 'batch',
            'reference_id' => $batch1->id,
        ]);

        StockMovement::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product1->id,
            'batch_id' => $batch2->id,
            'type' => StockMovementType::PURCHASE,
            'quantity' => 20,
            'reference_type' => 'batch',
            'reference_id' => $batch2->id,
        ]);

        StockMovement::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product2->id,
            'batch_id' => $batch1->id,
            'type' => StockMovementType::PURCHASE,
            'quantity' => 10,
            'reference_type' => 'batch',
            'reference_id' => $batch1->id,
        ]);

        StockMovement::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product2->id,
            'batch_id' => $batch2->id,
            'type' => StockMovementType::PURCHASE,
            'quantity' => 20,
            'reference_type' => 'batch',
            'reference_id' => $batch2->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Client
        |--------------------------------------------------------------------------
        */

        $client = Client::factory()->create([
            'name' => 'Test Client',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Client Order
        |--------------------------------------------------------------------------
        */

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
            'status' => ClientOrderStatus::COMPLETED,
            'total_amount' => 0,
            'ordered_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Order Item #1
        |--------------------------------------------------------------------------
        |
        | Buy 15 Wireless Mouse.
        |
        | FIFO:
        |
        | Batch #1 → 10 × 100000
        | Batch #2 →  5 × 110000
        |
        */

        $orderItem1 = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 15,
            'unit_price' => 150000,
            'total_price' => 2250000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | FIFO Allocation
        |--------------------------------------------------------------------------
        */

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem1->id,
            'batch_id' => $batch1->id,
            'quantity' => 10,
            'unit_cost' => 100000,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem1->id,
            'batch_id' => $batch2->id,
            'quantity' => 5,
            'unit_cost' => 110000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Batch Available Quantities
        |--------------------------------------------------------------------------
        */

        $batch1Item1->update([
            'available_quantity' => 0,
        ]);

        $batch2Item1->update([
            'available_quantity' => 15,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Storage Stock
        |--------------------------------------------------------------------------
        */

        StorageStock::where('storage_id', $storage->id)
            ->where('product_id', $product1->id)
            ->update([
                'quantity' => 15,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Sale Stock Movement
        |--------------------------------------------------------------------------
        */

        StockMovement::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product1->id,
            'batch_id' => $batch1->id,
            'type' => StockMovementType::SALE,
            'quantity' => 10,
            'reference_type' => 'client_order',
            'reference_id' => $order->id,
        ]);

        StockMovement::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product1->id,
            'batch_id' => $batch2->id,
            'type' => StockMovementType::SALE,
            'quantity' => 5,
            'reference_type' => 'client_order',
            'reference_id' => $order->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Order Total
        |--------------------------------------------------------------------------
        */

        $order->update([
            'total_amount' => $orderItem1->total_price,
        ]);
    }
}
