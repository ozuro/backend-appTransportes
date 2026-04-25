<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransportServiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'vehicle_plate' => $this->vehicle?->plate,
            'vehicle_unit_type' => $this->vehicle?->unit_type,
            'driver_name' => trim(collect([
                $this->driver?->first_name,
                $this->driver?->last_name,
            ])->filter()->implode(' ')),
            'service_code' => $this->service_code,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'origin_address' => $this->origin_address,
            'origin_reference' => $this->origin_reference,
            'destination_address' => $this->destination_address,
            'destination_reference' => $this->destination_reference,
            'scheduled_start_at' => $this->scheduled_start_at,
            'scheduled_end_at' => $this->scheduled_end_at,
            'actual_start_at' => $this->actual_start_at,
            'actual_end_at' => $this->actual_end_at,
            'quoted_amount' => $this->quoted_amount,
            'final_amount' => $this->final_amount,
            'payment_status' => $this->payment_status,
            'cargo_description' => $this->cargo_description,
            'observations' => $this->observations,
            'incidents' => $this->incidents,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                    'business_name' => $this->client?->business_name,
                ];
            }),
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle?->id,
                    'plate' => $this->vehicle?->plate,
                    'unit_type' => $this->vehicle?->unit_type,
                ];
            }),
            'driver' => $this->whenLoaded('driver', function () {
                return [
                    'id' => $this->driver?->id,
                    'first_name' => $this->driver?->first_name,
                    'last_name' => $this->driver?->last_name,
                ];
            }),
            'settlement' => $this->whenLoaded('settlement', function () {
                return ServiceSettlementResource::make($this->settlement);
            }),
        ];
    }
}
