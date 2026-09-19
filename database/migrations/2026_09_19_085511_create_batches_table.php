<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('storage_id')
                ->constrained()
                ->restrictOnDelete();

            $table->dateTime('purchased_at');
            $table->string('reference')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'purchased_at']);
            $table->index(['storage_id', 'purchased_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
