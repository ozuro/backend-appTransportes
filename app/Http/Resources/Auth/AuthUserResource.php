<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'companies' => $this->companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'trade_name' => $company->trade_name,
                    'legal_name' => $company->legal_name,
                    'ruc' => $company->ruc,
                    'currency_code' => $company->currency_code,
                    'timezone' => $company->timezone,
                    'is_active' => $company->is_active,
                    'role_id' => $company->pivot->role_id,
                    'is_owner' => $company->pivot->is_owner,
                ];
            }),
        ];
    }
}
