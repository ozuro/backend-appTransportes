<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Http\Requests\Quotation\UpdateQuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Models\Client;
use App\Models\Quotation;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return QuotationResource::collection(
            Quotation::with('client')->where('company_id', $company->id)->latest()->paginate(20)
        );
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();

        if (! Client::where('company_id', $company->id)->where('id', $data['client_id'])->exists()) {
            throw ValidationException::withMessages([
                'client_id' => ['El cliente no pertenece a la empresa activa.'],
            ]);
        }

        $quotation = Quotation::create(array_merge(
            $data,
            [
                'company_id' => $company->id,
                'quotation_code' => $this->generateQuotationCode($company->id),
                'status' => $data['status'] ?? 'pending',
            ]
        ));

        return (new QuotationResource($quotation->load('client')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Quotation $quotation)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($quotation->company_id === $company->id, 404);

        return new QuotationResource($quotation->load('client'));
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($quotation->company_id === $company->id, 404);

        $quotation->update($request->validated());

        return new QuotationResource($quotation->fresh()->load('client'));
    }

    public function destroy(Request $request, Quotation $quotation): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($quotation->company_id === $company->id, 404);

        $quotation->delete();

        return response()->json(['message' => 'Cotización eliminada correctamente.']);
    }

    private function generateQuotationCode(int $companyId): string
    {
        $prefix = 'COT-' . str_pad((string) $companyId, 3, '0', STR_PAD_LEFT);
        $nextId = (Quotation::max('id') ?? 0) + 1;

        return $prefix . '-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
