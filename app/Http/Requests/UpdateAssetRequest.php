<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $assetId = $this->route('asset')->id;

        return [
            'asset_name' => 'required|string|max:255',
            'asset_tag_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('assets')->ignore($assetId),
            ],
            'serial_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets')->ignore($assetId),
            ],
            'model_type_id' => 'required|exists:model_types,id',
            'qty' => 'required|integer|min:1',
            'status' => 'required|string|in:available,assigned,active,inactive,damaged,lost',
            'description' => 'nullable|string|max:1000',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
