<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'quotation_code' => $this->quotation_code,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'origin_address' => $this->origin_address,
            'destination_address' => $this->destination_address,
            'estimated_distance_km' => $this->estimated_distance_km,
            'quoted_amount' => $this->quoted_amount,
            'cargo_description' => $this->cargo_description,
            'notes' => $this->notes,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                ];
            }),
        ];
    }
}
