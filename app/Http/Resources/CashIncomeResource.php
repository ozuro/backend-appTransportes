<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashIncomeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'amount' => $this->amount,
            'concept' => $this->concept,
            'note' => $this->note,
            'received_at' => optional($this->received_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
