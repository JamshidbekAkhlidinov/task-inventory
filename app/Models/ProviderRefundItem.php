<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderRefundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_id',
        'batch_item_id',
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
        return $this->belongsTo(ProviderRefund::class, 'refund_id');
    }

    public function batchItem(): BelongsTo
    {
        return $this->belongsTo(BatchItem::class);
    }
}
