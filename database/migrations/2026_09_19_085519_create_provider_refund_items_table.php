<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_refund_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('refund_id')
                ->constrained('provider_refunds')
                ->restrictOnDelete();

            $table->foreignId('batch_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->timestamps();

            $table->index(['refund_id', 'batch_item_id']);
            $table->index('batch_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_refund_items');
    }
};
