<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'asset_name' => 'required|string|max:255',
            'asset_tag_no' => 'required|string|max:100|unique:assets,asset_tag_no',
            'serial_no' => 'nullable|string|max:100|unique:assets,serial_no',
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

    public function messages()
    {
        return [
            'asset_name.required' => 'Asset name is required',
            'asset_tag_no.required' => 'Asset tag number is required',
            'asset_tag_no.unique' => 'This asset tag number is already in use',
            'serial_no.unique' => 'This serial number is already in use',
            'model_type_id.required' => 'Please select a model',
            'qty.required' => 'Quantity is required',
            'status.required' => 'Status is required',
        ];
    }
}
