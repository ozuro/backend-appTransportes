<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OperatingExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'vehicle_id' => $this->vehicle_id,
            'transport_service_id' => $this->transport_service_id,
            'category' => $this->category,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'supplier_name' => $this->supplier_name,
            'description' => $this->description,
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle?->id,
                    'plate' => $this->vehicle?->plate,
                ];
            }),
        ];
    }
}
