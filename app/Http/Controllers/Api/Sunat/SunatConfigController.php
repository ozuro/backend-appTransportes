<?php

namespace App\Http\Controllers\Api\Sunat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sunat\UpsertElectronicBillingConfigRequest;
use App\Http\Resources\ElectronicBillingConfigResource;
use App\Models\ElectronicBillingConfig;
use App\Services\Sunat\SunatBillingService;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\Request;

class SunatConfigController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext,
        private SunatBillingService $billingService
    ) {
    }

    public function show(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $config = ElectronicBillingConfig::firstWhere('company_id', $company->id);

        return response()->json([
            'data' => $config ? new ElectronicBillingConfigResource($config) : null,
            'status' => $this->billingService->summarizeConfigStatus($config),
        ]);
    }

    public function update(UpsertElectronicBillingConfigRequest $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $config = $this->billingService->upsertConfig($company, $request->validated());

        return response()->json([
            'message' => 'Configuracion SUNAT guardada correctamente.',
            'data' => new ElectronicBillingConfigResource($config),
            'status' => $this->billingService->summarizeConfigStatus($config),
        ]);
    }
}
