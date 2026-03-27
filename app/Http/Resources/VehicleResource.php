<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'unit_type' => $this->unit_type,
            'plate' => $this->plate,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'capacity_value' => $this->capacity_value,
            'capacity_unit' => $this->capacity_unit,
            'mileage' => $this->mileage,
            'soat_expires_at' => $this->soat_expires_at,
            'technical_review_expires_at' => $this->technical_review_expires_at,
            'operational_status' => $this->operational_status,
            'estimated_cost_per_km' => $this->estimated_cost_per_km,
            'estimated_cost_per_service' => $this->estimated_cost_per_service,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
