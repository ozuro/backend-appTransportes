<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'service_type' => ['required', 'in:moving,cargo,construction_material'],
            'status' => ['nullable', 'in:pending,approved,rejected,converted'],
            'origin_address' => ['required', 'string', 'max:255'],
            'destination_address' => ['required', 'string', 'max:255'],
            'estimated_distance_km' => ['nullable', 'numeric', 'min:0'],
            'quoted_amount' => ['required', 'numeric', 'min:0'],
            'cargo_description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
