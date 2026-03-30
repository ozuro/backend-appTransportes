<?php

namespace App\Http\Controllers\Api\Sunat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sunat\StoreElectronicDocumentRequest;
use App\Http\Resources\ElectronicDocumentResource;
use App\Models\ElectronicDocument;
use App\Services\Sunat\SunatBillingService;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\Request;

class ElectronicDocumentController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext,
        private SunatBillingService $billingService
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return ElectronicDocumentResource::collection(
            ElectronicDocument::with(['client', 'transportService', 'quotation'])
                ->where('company_id', $company->id)
                ->latest()
                ->paginate(20)
        );
    }

    public function store(StoreElectronicDocumentRequest $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $document = $this->billingService->createDraft($company, $request->validated());

        return (new ElectronicDocumentResource(
            $document->load(['client', 'transportService', 'quotation'])
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, ElectronicDocument $document)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($document->company_id === $company->id, 404);

        return new ElectronicDocumentResource(
            $document->load(['client', 'transportService', 'quotation'])
        );
    }

    public function send(Request $request, ElectronicDocument $document)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($document->company_id === $company->id, 404);

        $issuedDocument = $this->billingService->emitDraft($document);

        return response()->json([
            'message' => 'Documento enviado a SUNAT correctamente.',
            'data' => new ElectronicDocumentResource($issuedDocument),
        ]);
    }
}
