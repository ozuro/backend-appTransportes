<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $documentType = strtoupper(trim((string) $this->input('document_type', '')));
        $documentNumber = preg_replace('/\D+/', '', (string) $this->input('document_number', '')) ?: null;
        $ruc = preg_replace('/\D+/', '', (string) $this->input('ruc', '')) ?: null;
        $dni = preg_replace('/\D+/', '', (string) $this->input('dni', '')) ?: null;

        if ($ruc !== null) {
            $this->merge([
                'client_type' => 'company',
                'document_type' => 'RUC',
                'document_number' => $ruc,
                'business_name' => $this->input('business_name') ?: $this->input('name'),
            ]);

            return;
        }

        if ($dni !== null) {
            $this->merge([
                'document_type' => 'DNI',
                'document_number' => $dni,
            ]);

            return;
        }

        if ($documentType !== '') {
            $normalizedDocumentType = match ($documentType) {
                '6' => 'RUC',
                '1' => 'DNI',
                default => $documentType,
            };

            $this->merge([
                'client_type' => $normalizedDocumentType === 'RUC'
                    ? 'company'
                    : $this->input('client_type'),
                'document_type' => $normalizedDocumentType,
                'document_number' => $documentNumber,
                'business_name' => $normalizedDocumentType === 'RUC'
                    ? ($this->input('business_name') ?: $this->input('name'))
                    : $this->input('business_name'),
            ]);
        }
    }

    public function rules()
    {
        return [
            'client_type' => ['required', 'in:person,company'],
            'document_type' => ['nullable', 'string', 'max:20'],
            'document_number' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $type = strtoupper((string) $this->input('document_type'));
                    $digits = preg_replace('/\D+/', '', (string) $value);

                    if ($type === 'RUC' && strlen($digits) !== 11) {
                        $fail('El RUC del cliente debe tener 11 digitos.');
                    }

                    if ($type === 'DNI' && strlen($digits) !== 8) {
                        $fail('El DNI del cliente debe tener 8 digitos.');
                    }
                },
            ],
            'ruc' => ['nullable', 'digits:11'],
            'dni' => ['nullable', 'digits:8'],
            'name' => ['required', 'string', 'max:150'],
            'business_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'secondary_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'in:corporate,frequent,occasional'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
