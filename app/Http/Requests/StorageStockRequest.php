<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorageStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storage_id' => ['required', 'integer', 'exists:storages,id'],
            'date' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
