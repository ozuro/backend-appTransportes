<?php

namespace App\Http\Controllers\Api\Sunat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sunat\SirePeriodRequest;
use App\Models\ElectronicBillingConfig;
use App\Services\Sunat\SireService;
use App\Services\Tenant\CompanyContextService;

class SireController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext,
        private SireService $sireService
    ) {
    }

    public function summary(SirePeriodRequest $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $config = ElectronicBillingConfig::firstWhere('company_id', $company->id);

        return response()->json([
            'data' => $this->sireService->buildSummary(
                $company,
                $config,
                (string) $request->string('period')
            ),
        ]);
    }

    public function sales(SirePeriodRequest $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $config = ElectronicBillingConfig::firstWhere('company_id', $company->id);
        $result = $this->sireService->fetchSales(
            $company,
            $config,
            (string) $request->string('period')
        );

        return response()->json([
            'data' => $result['items'],
            'meta' => [
                'status' => $result['status'],
            ],
        ]);
    }

    public function purchases(SirePeriodRequest $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $config = ElectronicBillingConfig::firstWhere('company_id', $company->id);
        $result = $this->sireService->fetchPurchases(
            $company,
            $config,
            (string) $request->string('period')
        );

        return response()->json([
            'data' => $result['items'],
            'meta' => [
                'status' => $result['status'],
            ],
        ]);
    }
}
