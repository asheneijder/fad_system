<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->route('user'),
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'office_location' => 'nullable|string|max:255',
            'graph_id' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'mail' => 'nullable|email|max:255',
            'given_name' => 'nullable|string|max:255',
        ];
    }
}
