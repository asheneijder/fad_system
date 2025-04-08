<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_name' => 'required|string|max:255',
            'asset_tag_no' => 'required|string|max:50|unique:assets,asset_tag_no',
            'serial_no' => 'nullable|string|max:100|unique:assets,serial_no',
            'category_type_id' => 'required|exists:category_types,id',
            'model_type_id' => 'nullable|exists:model_types,id',
            'status' => 'required|integer|in:0,1', // Assuming status is either 0 or 1
            'location' => 'required|string|max:255',
            'purchase_date' => 'nullable|date',
            'qty' => 'required|integer|min:1',
            'purchase_cost' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_name.required' => 'The asset name is required.',
            'asset_tag_no.required' => 'The asset tag number is required.',
            'asset_tag_no.unique' => 'This asset tag number already exists.',
            'serial_no.unique' => 'This serial number already exists.',
            'category_type_id.required' => 'Please select a category.',
            'category_type_id.exists' => 'The selected category is invalid.',
            'model_type_id.exists' => 'The selected model is invalid.',
            'status.required' => 'The asset status is required.',
            'status.in' => 'The status must be either active (1) or inactive (0).',
            'location.required' => 'The location is required.',
            'qty.required' => 'Quantity is required.',
            'qty.min' => 'Quantity must be at least 1.',
            'purchase_cost.required' => 'Purchase cost is required.',
            'purchase_cost.min' => 'Purchase cost must be at least 0.',
            'current_value.required' => 'Current value is required.',
            'current_value.min' => 'Current value must be at least 0.',
        ];
    }
}
