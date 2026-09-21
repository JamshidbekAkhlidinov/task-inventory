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
        | Providers
        |--------------------------------------------------------------------------
        */

        $provider = Provider::factory()->create([
            'name' => 'Test Supplier',
            'phone' => '+998901234567',
            'email' => 'supplier@example.com',
            'address' => 'Tashkent, Uzbekistan',
        ]);

        $secondProvider = Provider::factory()->create([
            'name' => 'Global Parts Trading',
            'phone' => '+998907654321',
            'email' => 'sales@globalparts.example.com',
            'address' => 'Samarkand, Uzbekistan',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Categories (with a parent/child hierarchy)
        |--------------------------------------------------------------------------
        */

        $category = Category::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Electronics',
        ]);

        $peripheralsCategory = Category::factory()->create([
            'provider_id' => $provider->id,
            'parent_id' => $category->id,
            'name' => 'Peripherals',
        ]);

        $secondCategory = Category::factory()->create([
            'provider_id' => $secondProvider->id,
            'name' => 'Office Supplies',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $product1 = Product::factory()->create([
            'category_id' => $peripheralsCategory->id,
            'name' => 'Wireless Mouse',
            'sale_price' => 150000,
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $peripheralsCategory->id,
            'name' => 'Mechanical Keyboard',
            'sale_price' => 650000,
        ]);

        $product3 = Product::factory()->create([
            'category_id' => $secondCategory->id,
            'name' => 'A4 Paper Ream',
            'sale_price' => 45000,
        ]);

        $product4 = Product::factory()->create([
            'category_id' => $secondCategory->id,
            'name' => 'Stapler',
            'sale_price' => 35000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Storages
        |--------------------------------------------------------------------------
        */

        $storage = Storage::factory()->create([
            'name' => 'Main Warehouse',
            'address' => 'Tashkent, Uzbekistan',
        ]);

        $secondStorage = Storage::factory()->create([
            'name' => 'Regional Warehouse',
            'address' => 'Samarkand, Uzbekistan',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Batch #1 (oldest, cheapest cost)
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
        | Batch #2 (newer, higher cost — for FIFO testing)
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
        | Batch #3 (second provider, second storage, different cost)
        |--------------------------------------------------------------------------
        */

        $batch3 = Batch::factory()->create([
            'provider_id' => $secondProvider->id,
            'storage_id' => $secondStorage->id,
            'purchased_at' => now()->subDays(15),
            'reference' => 'PO-0003',
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch3->id,
            'product_id' => $product3->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'unit_cost' => 25000,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch3->id,
            'product_id' => $product4->id,
            'quantity' => 50,
            'available_quantity' => 50,
            'unit_cost' => 18000,
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

        StorageStock::factory()->create([
            'storage_id' => $secondStorage->id,
            'product_id' => $product3->id,
            'quantity' => 100,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $secondStorage->id,
            'product_id' => $product4->id,
            'quantity' => 50,
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

        StockMovement::factory()->create([
            'storage_id' => $secondStorage->id,
            'product_id' => $product3->id,
            'batch_id' => $batch3->id,
            'type' => StockMovementType::PURCHASE,
            'quantity' => 100,
            'reference_type' => 'batch',
            'reference_id' => $batch3->id,
        ]);

        StockMovement::factory()->create([
            'storage_id' => $secondStorage->id,
            'product_id' => $product4->id,
            'batch_id' => $batch3->id,
            'type' => StockMovementType::PURCHASE,
            'quantity' => 50,
            'reference_type' => 'batch',
            'reference_id' => $batch3->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Clients
        |--------------------------------------------------------------------------
        */

        $client = Client::factory()->create([
            'name' => 'Test Client',
        ]);

        Client::factory()->create([
            'name' => 'Northgate Retail LLC',
        ]);

        Client::factory()->create([
            'name' => 'Aziz Karimov',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Client Order (demonstrates FIFO consuming both batches)
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
