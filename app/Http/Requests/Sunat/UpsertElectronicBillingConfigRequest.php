<?php

namespace App\Http\Requests\Sunat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertElectronicBillingConfigRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'environment' => ['required', Rule::in(['beta', 'production'])],
            'sol_user' => ['nullable', 'string', 'max:255'],
            'sol_password' => ['nullable', 'string', 'max:255'],
            'certificate_path' => ['nullable', 'string', 'max:255'],
            'certificate_password' => ['nullable', 'string', 'max:255'],
            'office_ubigeo' => ['nullable', 'string', 'size:6'],
            'office_address' => ['nullable', 'string', 'max:255'],
            'office_urbanization' => ['nullable', 'string', 'max:255'],
            'office_district' => ['nullable', 'string', 'max:255'],
            'office_province' => ['nullable', 'string', 'max:255'],
            'office_department' => ['nullable', 'string', 'max:255'],
            'office_country_code' => ['nullable', 'string', 'size:2'],
            'invoice_series' => ['nullable', 'string', 'size:4', 'regex:/^F[A-Z0-9]{3}$/'],
            'receipt_series' => ['nullable', 'string', 'size:4', 'regex:/^B[A-Z0-9]{3}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'extra_settings' => ['nullable', 'array'],
            'extra_settings.provider' => ['nullable', Rule::in(['greenter', 'lucode'])],
            'extra_settings.api_base_url' => ['nullable', 'url', 'max:255'],
            'extra_settings.api_token' => ['nullable', 'string', 'max:500'],
            'extra_settings.initial_receipt_correlative' => ['nullable', 'integer', 'min:0'],
            'extra_settings.initial_invoice_correlative' => ['nullable', 'integer', 'min:0'],
            'extra_settings.sire_client_id' => ['nullable', 'string', 'max:255'],
            'extra_settings.sire_client_secret' => ['nullable', 'string', 'max:500'],
            'extra_settings.sire_username' => ['nullable', 'string', 'max:255'],
            'extra_settings.sire_sales_endpoint' => ['nullable', 'string', 'max:255'],
            'extra_settings.sire_purchases_endpoint' => ['nullable', 'string', 'max:255'],
        ];
    }
}
