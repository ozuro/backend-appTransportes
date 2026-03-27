<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_type' => ['required', 'in:person,company'],
            'document_type' => ['nullable', 'string', 'max:20'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'business_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'secondary_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'in:corporate,frequent,occasional'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
