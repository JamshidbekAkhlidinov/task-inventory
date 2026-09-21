<?php

namespace App\Models;

use App\Enums\ClientRefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClientRefundStatus::class,
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ClientOrder::class, 'order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientRefundItem::class, 'refund_id');
    }
}
