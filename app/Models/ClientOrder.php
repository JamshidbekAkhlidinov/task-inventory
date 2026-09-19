<?php

namespace App\Models;

use App\Enums\ClientOrderStatus;
use Illuminate\Database\Eloquent\Model;

class ClientOrder extends Model
{
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
}
