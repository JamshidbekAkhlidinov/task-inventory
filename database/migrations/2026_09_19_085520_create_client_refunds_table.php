<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('client_orders')
                ->restrictOnDelete();

            $table->string('status')->default('pending');

            $table->dateTime('refunded_at');

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_refunds');
    }
};
