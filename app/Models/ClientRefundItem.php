<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRefundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_id',
        'order_allocation_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(ClientRefund::class, 'refund_id');
    }

    public function orderAllocation(): BelongsTo
    {
        return $this->belongsTo(
            ClientOrderAllocation::class,
            'order_allocation_id'
        );
    }
}
