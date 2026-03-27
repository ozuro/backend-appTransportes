<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'unit_type' => ['required', 'in:truck,dump_truck,van,pickup,other'],
            'plate' => ['required', 'string', 'max:15'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'capacity_value' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:20'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'soat_expires_at' => ['nullable', 'date'],
            'technical_review_expires_at' => ['nullable', 'date'],
            'operational_status' => ['nullable', 'in:active,maintenance,out_of_service,inactive'],
            'estimated_cost_per_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost_per_service' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
