<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'client_type' => $this->client_type,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'ruc' => $this->ruc,
            'dni' => $this->dni,
            'name' => $this->name,
            'business_name' => $this->business_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'secondary_phone' => $this->secondary_phone,
            'address' => $this->address,
            'district' => $this->district,
            'province' => $this->province,
            'department' => $this->department,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
