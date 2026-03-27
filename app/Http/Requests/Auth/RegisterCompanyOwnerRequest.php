<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyOwnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'company.trade_name' => ['required', 'string', 'max:150'],
            'company.legal_name' => ['nullable', 'string', 'max:200'],
            'company.ruc' => ['nullable', 'digits:11', 'unique:companies,ruc'],
            'company.email' => ['nullable', 'email', 'max:150'],
            'company.phone' => ['nullable', 'string', 'max:30'],
            'company.address' => ['nullable', 'string', 'max:255'],
            'company.district' => ['nullable', 'string', 'max:120'],
            'company.province' => ['nullable', 'string', 'max:120'],
            'company.department' => ['nullable', 'string', 'max:120'],
            'company.country_code' => ['nullable', 'string', 'size:2'],
            'company.currency_code' => ['nullable', 'string', 'size:3'],
            'company.timezone' => ['nullable', 'string', 'max:50'],
            'user.name' => ['required', 'string', 'max:150'],
            'user.first_name' => ['nullable', 'string', 'max:100'],
            'user.last_name' => ['nullable', 'string', 'max:100'],
            'user.email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'user.phone' => ['nullable', 'string', 'max:30'],
            'user.document_type' => ['nullable', 'string', 'max:20'],
            'user.document_number' => ['nullable', 'string', 'max:20'],
            'user.password' => ['required', 'string', 'min:6', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
