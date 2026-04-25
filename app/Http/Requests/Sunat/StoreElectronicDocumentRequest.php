<?php

namespace App\Http\Requests\Sunat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreElectronicDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'transport_service_id' => ['nullable', 'integer', 'exists:transport_services,id'],
            'quotation_id' => ['nullable', 'integer', 'exists:quotations,id'],
            'document_type' => ['required', Rule::in(['invoice', 'receipt', 'dispatch_carrier'])],
            'issue_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
