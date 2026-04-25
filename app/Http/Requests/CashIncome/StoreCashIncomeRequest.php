<?php

namespace App\Http\Requests\CashIncome;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashIncomeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'concept' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string'],
            'received_at' => ['nullable', 'date'],
        ];
    }
}
