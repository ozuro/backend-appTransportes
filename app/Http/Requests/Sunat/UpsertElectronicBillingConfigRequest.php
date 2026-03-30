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
            'invoice_series' => ['nullable', 'string', 'size:4'],
            'receipt_series' => ['nullable', 'string', 'size:4'],
            'is_active' => ['sometimes', 'boolean'],
            'extra_settings' => ['nullable', 'array'],
        ];
    }
}
