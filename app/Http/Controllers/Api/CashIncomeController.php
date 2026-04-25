<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashIncome\StoreCashIncomeRequest;
use App\Http\Requests\CashIncome\UpdateCashIncomeRequest;
use App\Http\Resources\CashIncomeResource;
use App\Models\CashIncome;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashIncomeController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return CashIncomeResource::collection(
            CashIncome::where('company_id', $company->id)->latest()->paginate(20)
        );
    }

    public function store(StoreCashIncomeRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();

        $income = CashIncome::create(array_merge($data, [
            'company_id' => $company->id,
            'received_at' => $data['received_at'] ?? now(),
        ]));

        return (new CashIncomeResource($income))->response()->setStatusCode(201);
    }

    public function show(Request $request, CashIncome $cashIncome)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($cashIncome->company_id === $company->id, 404);

        return new CashIncomeResource($cashIncome);
    }

    public function update(UpdateCashIncomeRequest $request, CashIncome $cashIncome)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($cashIncome->company_id === $company->id, 404);

        $cashIncome->update($request->validated());

        return new CashIncomeResource($cashIncome->fresh());
    }

    public function destroy(Request $request, CashIncome $cashIncome): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($cashIncome->company_id === $company->id, 404);

        $cashIncome->delete();

        return response()->json(['message' => 'Ingreso eliminado correctamente.']);
    }
}
