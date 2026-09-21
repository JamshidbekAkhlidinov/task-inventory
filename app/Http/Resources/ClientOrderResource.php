<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'storage_id' => $this->storage_id,
            'status' => $this->status->value,
            'total_amount' => (float) $this->total_amount,
            'ordered_at' => $this->ordered_at?->toIso8601String(),
            'items' => ClientOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
