<?php

namespace App\Http\Controllers\Api\Sunat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthUserResource;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\Request;

class SunatDemoController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function prepareCompany(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        $company->forceFill([
            'ruc' => '20000000001',
            'legal_name' => $company->legal_name ?: 'EMPRESA DEMO SUNAT SAC',
            'trade_name' => $company->trade_name ?: 'Empresa Demo',
            'address' => $company->address ?: 'Av. Demo 123 - Lima',
            'district' => $company->district ?: 'LIMA',
            'province' => $company->province ?: 'LIMA',
            'department' => $company->department ?: 'LIMA',
            'country_code' => $company->country_code ?: 'PE',
            'currency_code' => $company->currency_code ?: 'PEN',
        ])->save();

        return response()->json([
            'message' => 'Empresa preparada para demo SUNAT beta.',
            'company' => $company,
            'user' => new AuthUserResource($request->user()->fresh()->load('companies')),
        ]);
    }
}
