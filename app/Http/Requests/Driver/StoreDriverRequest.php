<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:20'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'license_number' => ['required', 'string', 'max:30'],
            'license_category' => ['nullable', 'string', 'max:10'],
            'license_expires_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:available,on_trip,rest,unavailable'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
