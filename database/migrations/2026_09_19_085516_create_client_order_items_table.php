<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('client_orders')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            // Customer sale price snapshot at the time of order.
            $table->decimal('unit_price', 15, 2);

            $table->decimal('total_price', 15, 2);

            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_order_items');
    }
};
