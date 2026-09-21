<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientRefundItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_allocation_id' => $this->order_allocation_id,
            'batch_id' => $this->whenLoaded('orderAllocation', fn () => $this->orderAllocation->batch_id),
            'quantity' => $this->quantity,
        ];
    }
}
