<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRefundItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_item_id' => $this->batch_item_id,
            'product_id' => $this->whenLoaded('batchItem', fn () => $this->batchItem->product_id),
            'product_name' => $this->whenLoaded('batchItem', fn () => $this->batchItem->product?->name),
            'quantity' => $this->quantity,
        ];
    }
}
