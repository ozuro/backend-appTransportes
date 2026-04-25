<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicBillingConfigResource extends JsonResource
{
    public function toArray($request)
    {
        $extraSettings = (array) ($this->extra_settings ?? []);
        unset(
            $extraSettings['api_token'],
            $extraSettings['sire_client_id'],
            $extraSettings['sire_client_secret']
        );

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'environment' => $this->environment,
            'provider' => $this->provider,
            'api_base_url' => $this->api_base_url,
            'initial_receipt_correlative' => $this->initial_receipt_correlative,
            'initial_invoice_correlative' => $this->initial_invoice_correlative,
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
            'has_api_token' => filled($this->api_token) || filled(config('sunat.lucode.api_token')),
            'has_sire_client_credentials' => (
                filled($this->sire_client_id) || filled(config('sunat.sire.client_id'))
            ) && (
                filled($this->sire_client_secret) || filled(config('sunat.sire.client_secret'))
            ),
            'has_sire_sales_endpoint' => filled($this->sire_sales_endpoint) || filled(config('sunat.sire.endpoints.sales')),
            'has_sire_purchases_endpoint' => filled($this->sire_purchases_endpoint) || filled(config('sunat.sire.endpoints.purchases')),
            'extra_settings' => $extraSettings,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
