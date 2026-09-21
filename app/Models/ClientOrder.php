<?php

namespace App\Models;

use App\Enums\ClientOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'storage_id',
        'status',
        'total_amount',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClientOrderStatus::class,
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientOrderItem::class, 'order_id');
    }
}
