<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'status' => $this->status->value,
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'items' => ProviderRefundItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
