<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'sale_price' => (float) $this->sale_price,
            'is_active' => $this->is_active,
            'available_quantity' => $this->whenLoaded(
                'storageStocks',
                fn () => (int) $this->storageStocks->sum('quantity')
            ),
        ];
    }
}
