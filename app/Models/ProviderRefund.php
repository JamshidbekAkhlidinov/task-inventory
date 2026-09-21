<?php

namespace App\Models;

use App\Enums\ProviderRefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'status',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProviderRefundStatus::class,
            'refunded_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProviderRefundItem::class, 'refund_id');
    }
}
