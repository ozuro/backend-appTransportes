<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicBillingConfigResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'environment' => $this->environment,
            'office_ubigeo' => $this->office_ubigeo,
            'office_address' => $this->office_address,
            'office_urbanization' => $this->office_urbanization,
            'office_district' => $this->office_district,
            'office_province' => $this->office_province,
            'office_department' => $this->office_department,
            'office_country_code' => $this->office_country_code,
            'invoice_series' => $this->invoice_series,
            'receipt_series' => $this->receipt_series,
            'is_active' => $this->is_active,
            'has_sol_credentials' => filled($this->sol_user) && filled($this->sol_password),
            'has_certificate' => filled($this->certificate_path),
            'has_certificate_password' => filled($this->certificate_password),
            'extra_settings' => $this->extra_settings ?? [],
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
