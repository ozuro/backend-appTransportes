<?php

namespace App\Http\Requests\TransportService;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransportServiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'service_type' => ['required', 'in:moving,cargo,construction_material'],
            'status' => ['nullable', 'in:pending,scheduled,on_route,completed,cancelled'],
            'origin_address' => ['required', 'string', 'max:255'],
            'origin_reference' => ['nullable', 'string', 'max:255'],
            'destination_address' => ['required', 'string', 'max:255'],
            'destination_reference' => ['nullable', 'string', 'max:255'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date'],
            'actual_start_at' => ['nullable', 'date'],
            'actual_end_at' => ['nullable', 'date'],
            'quoted_amount' => ['nullable', 'numeric', 'min:0'],
            'final_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'in:pending,partial,paid,overdue'],
            'cargo_description' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'incidents' => ['nullable', 'string'],
        ];
    }
}
