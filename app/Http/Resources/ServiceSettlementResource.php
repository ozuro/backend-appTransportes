<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceSettlementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'transport_service_id' => $this->transport_service_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'gross_amount' => $this->gross_amount,
            'company_amount' => $this->company_amount,
            'owner_amount' => $this->owner_amount,
            'driver_amount' => $this->driver_amount,
            'expense_amount' => $this->expense_amount,
            'status' => $this->status,
            'settled_at' => $this->settled_at,
            'notes' => $this->notes,
        ];
    }
}
