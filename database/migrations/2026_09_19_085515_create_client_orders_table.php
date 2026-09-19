<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('storage_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('pending');

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->dateTime('ordered_at');

            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['storage_id', 'status']);
            $table->index(['status', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_orders');
    }
};
