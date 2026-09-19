<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'provider_id',
        'storage_id',
        'purchased_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BatchItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ClientOrderAllocation::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function providerRefunds(): HasMany
    {
        return $this->hasMany(ProviderRefund::class);
    }
}
