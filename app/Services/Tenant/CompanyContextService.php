<?php

namespace App\Services\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompanyContextService
{
    public function resolve(Request $request, User $user): Company
    {
        $companyId = $request->header('X-Company-Id');
        $query = $user->companies()->wherePivot('is_active', true)->where('companies.is_active', true);

        if ($companyId) {
            $company = $query->where('companies.id', $companyId)->first();

            if (! $company) {
                throw ValidationException::withMessages([
                    'company' => ['La empresa seleccionada no pertenece al usuario autenticado.'],
                ]);
            }

            return $company;
        }

        $company = $query->orderBy('companies.id')->first();

        if (! $company) {
            throw ValidationException::withMessages([
                'company' => ['El usuario no tiene empresas activas asociadas.'],
            ]);
        }

        return $company;
    }
}
