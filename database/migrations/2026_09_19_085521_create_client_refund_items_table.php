<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_refund_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('refund_id')
                ->constrained('client_refunds')
                ->restrictOnDelete();

            $table->foreignId('order_allocation_id')
                ->constrained('client_order_allocations')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->timestamps();

            $table->index(['refund_id', 'order_allocation_id']);
            $table->index('order_allocation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_refund_items');
    }
};
