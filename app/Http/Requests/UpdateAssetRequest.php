<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_name' => 'required|string|max:255',
            'asset_tag_no' => 'required|string|max:50|unique:assets,asset_tag_no,' . $this->asset->id,
            'serial_no' => 'nullable|string|max:100|unique:assets,serial_no,' . $this->asset->id,
            'category_type_id' => 'required|exists:category_types,id',
            'model_type_id' => 'nullable|exists:model_types,id',
            'status' => 'required|integer|in:0,1',
            'location' => 'required|string|max:255',
            'purchase_date' => 'nullable|date',
            'qty' => 'required|integer|min:1',
            'purchase_cost' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
