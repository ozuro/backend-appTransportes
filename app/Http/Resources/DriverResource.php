<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'assigned_vehicle_id' => $this->assigned_vehicle_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'phone' => $this->phone,
            'email' => $this->email,
            'license_number' => $this->license_number,
            'license_category' => $this->license_category,
            'license_expires_at' => $this->license_expires_at,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'assigned_vehicle' => $this->whenLoaded('assignedVehicle', function () {
                return [
                    'id' => $this->assignedVehicle?->id,
                    'plate' => $this->assignedVehicle?->plate,
                    'unit_type' => $this->assignedVehicle?->unit_type,
                ];
            }),
        ];
    }
}
