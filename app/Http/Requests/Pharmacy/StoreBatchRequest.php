<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permission checked in middleware
    }

    public function rules(): array
    {
        return [
            'batch_number' => 'required|string|max:100',
            'manufacturing_date' => 'nullable|date',
            'expiration_date' => 'required|date|after:manufacturing_date',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'nullable|numeric|min:0',
        ];
    }
}
