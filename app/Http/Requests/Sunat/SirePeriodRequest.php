<?php

namespace App\Http\Requests\Sunat;

use Illuminate\Foundation\Http\FormRequest;

class SirePeriodRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'period' => ['required', 'regex:/^\d{4}(0[1-9]|1[0-2])$/'],
        ];
    }
}
