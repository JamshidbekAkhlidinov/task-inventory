<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_order_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_item_id')
                ->constrained('client_order_items')
                ->restrictOnDelete();

            $table->foreignId('batch_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->decimal('unit_cost', 15, 2);

            $table->timestamps();

            $table->index(['order_item_id', 'batch_id']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_order_allocations');
    }
};
