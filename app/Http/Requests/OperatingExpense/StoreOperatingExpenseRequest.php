<?php

namespace App\Http\Requests\OperatingExpense;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperatingExpenseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'transport_service_id' => ['nullable', 'integer', 'exists:transport_services,id'],
            'category' => ['required', 'in:peaje,viatico,mantenimiento,reparacion,alimentacion,hospedaje,estiba,multa,compra_operativa,otro'],
            'payment_method' => ['nullable', 'in:cash,transfer,yape,plin,card,other'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ];
    }
}
