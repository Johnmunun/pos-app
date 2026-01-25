<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permission checked in middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:3',
            'manufacturer' => 'nullable|string|max:255',
            'prescription_required' => 'boolean',
            'stock_alert_level' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'image_type' => 'nullable|in:upload,url',
            'image_file' => 'nullable|image|max:2048',
        ];
    }
}
