<?php

namespace App\Services\Sunat;

use App\Models\Client;
use App\Models\Company;
use App\Models\ElectronicBillingConfig;
use App\Models\ElectronicDocument;
use App\Models\Quotation;
use App\Models\TransportService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SunatBillingService
{
    public function __construct(
        private GreenterSunatService $greenterSunatService
    ) {
    }

    public function upsertConfig(Company $company, array $data): ElectronicBillingConfig
    {
        $payload = array_merge(
            [
                'environment' => config('sunat.default_environment', 'beta'),
                'office_country_code' => 'PE',
                'invoice_series' => 'F001',
                'receipt_series' => 'B001',
                'is_active' => false,
            ],
            $data
        );

        return ElectronicBillingConfig::updateOrCreate(
            ['company_id' => $company->id],
            $payload
        );
    }

    public function createDraft(Company $company, array $data): ElectronicDocument
    {
        $this->validateOwnership($company, $data);
        $config = ElectronicBillingConfig::firstWhere('company_id', $company->id);
        $series = $this->resolveSeries($data['document_type'], $config);

        return DB::transaction(function () use ($company, $data, $series) {
            return ElectronicDocument::create([
                'company_id' => $company->id,
                'client_id' => $data['client_id'],
                'transport_service_id' => $data['transport_service_id'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'document_type' => $data['document_type'],
                'series' => $series,
                'status' => 'draft',
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'currency_code' => $data['currency_code'] ?? $company->currency_code ?? 'PEN',
                'subtotal_amount' => $data['subtotal_amount'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => $data['total_amount'],
                'payload' => $this->buildPayload($company, $data, $series),
            ]);
        });
    }

    public function summarizeConfigStatus(?ElectronicBillingConfig $config): array
    {
        if (! $config) {
            return [
                'ready_for_beta' => false,
                'ready_for_production' => false,
                'missing' => [
                    'sol_credentials',
                    'office_address',
                    'office_ubigeo',
                    'series',
                    'certificate',
                ],
            ];
        }

        $missing = [];

        if (! filled($config->sol_user) || ! filled($config->sol_password)) {
            $missing[] = 'sol_credentials';
        }

        if (! filled($config->office_address)) {
            $missing[] = 'office_address';
        }

        if (! filled($config->office_ubigeo)) {
            $missing[] = 'office_ubigeo';
        }

        if (! filled($config->invoice_series) || ! filled($config->receipt_series)) {
            $missing[] = 'series';
        }

        $certificateReady = filled($config->certificate_path) && is_file($config->certificate_path);
        if (! $certificateReady) {
            $missing[] = 'certificate';
        }

        return [
            'ready_for_beta' => empty($missing),
            'ready_for_production' => empty($missing),
            'missing' => $missing,
        ];
    }

    public function emitDraft(ElectronicDocument $document): ElectronicDocument
    {
        $document->loadMissing(['company', 'client', 'transportService', 'quotation']);
        $config = ElectronicBillingConfig::firstWhere('company_id', $document->company_id);

        if (! $config || ! $config->is_active) {
            throw ValidationException::withMessages([
                'sunat' => ['La configuracion SUNAT activa no esta completa para esta empresa.'],
            ]);
        }

        if ($document->status !== 'draft') {
            throw ValidationException::withMessages([
                'document' => ['Solo se pueden emitir documentos que aun esten en estado draft.'],
            ]);
        }

        if (! $document->correlative) {
            $document->correlative = $this->nextCorrelative($document);
            $document->save();
        }

        $xmlPreview = $this->greenterSunatService->generateSignedXml($document, $config);
        $previewPath = $this->storePreviewXml($document, $xmlPreview);
        $sendResult = $this->greenterSunatService->send($document, $config);
        $cdrResponse = $sendResult['cdr_response'];

        $document->forceFill([
            'status' => $cdrResponse?->isAccepted() ? 'accepted' : 'sent',
            'xml_path' => $sendResult['xml_path'] ?? $previewPath,
            'cdr_path' => $sendResult['cdr_path'] ?? null,
            'sunat_response_code' => $cdrResponse?->getCode(),
            'sunat_response_message' => $cdrResponse?->getDescription(),
            'sent_at' => now(),
            'accepted_at' => $cdrResponse?->isAccepted() ? now() : null,
        ])->save();

        return $document->fresh(['client', 'transportService', 'quotation']);
    }

    private function validateOwnership(Company $company, array $data): void
    {
        $client = Client::where('company_id', $company->id)->whereKey($data['client_id'])->first();
        if (! $client) {
            throw ValidationException::withMessages([
                'client_id' => ['El cliente no pertenece a la empresa activa.'],
            ]);
        }

        $this->validateDocumentType($client, $data['document_type']);

        if (! empty($data['transport_service_id']) &&
            ! TransportService::where('company_id', $company->id)->whereKey($data['transport_service_id'])->exists()) {
            throw ValidationException::withMessages([
                'transport_service_id' => ['El servicio no pertenece a la empresa activa.'],
            ]);
        }

        if (! empty($data['quotation_id']) &&
            ! Quotation::where('company_id', $company->id)->whereKey($data['quotation_id'])->exists()) {
            throw ValidationException::withMessages([
                'quotation_id' => ['La cotizacion no pertenece a la empresa activa.'],
            ]);
        }
    }

    private function resolveSeries(string $documentType, ?ElectronicBillingConfig $config): string
    {
        if ($documentType === 'invoice') {
            return $config?->invoice_series ?: 'F001';
        }

        return $config?->receipt_series ?: 'B001';
    }

    private function buildPayload(Company $company, array $data, string $series): array
    {
        return [
            'company' => Arr::only($company->toArray(), [
                'id',
                'trade_name',
                'legal_name',
                'ruc',
                'address',
                'district',
                'province',
                'department',
                'currency_code',
            ]),
            'document' => [
                'type' => $data['document_type'],
                'series' => $series,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'subtotal_amount' => $data['subtotal_amount'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => $data['total_amount'],
                'currency_code' => $data['currency_code'] ?? $company->currency_code ?? 'PEN',
            ],
            'references' => [
                'client_id' => $data['client_id'],
                'transport_service_id' => $data['transport_service_id'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
            ],
            'lines' => $data['payload']['lines'] ?? [],
            'meta' => $data['payload']['meta'] ?? [],
        ];
    }

    private function nextCorrelative(ElectronicDocument $document): int
    {
        return (int) ElectronicDocument::where('company_id', $document->company_id)
            ->where('series', $document->series)
            ->max('correlative') + 1;
    }

    private function storePreviewXml(ElectronicDocument $document, string $xml): string
    {
        $documentNumber = sprintf(
            '%s-%08d',
            $document->series,
            $document->correlative ?: 1
        );
        $path = 'sunat/'.$document->company_id.'/preview/'.$documentNumber.'.xml';
        Storage::disk('local')->put($path, $xml);

        return $path;
    }

    private function validateDocumentType(Client $client, string $documentType): void
    {
        $clientDocumentType = strtoupper((string) $client->document_type);

        if ($documentType === 'invoice' && $clientDocumentType !== 'RUC') {
            throw ValidationException::withMessages([
                'document_type' => [
                    'Para emitir factura el cliente debe tener RUC. Use boleta o actualice el cliente.',
                ],
            ]);
        }

        if ($documentType === 'receipt' && ! in_array($clientDocumentType, ['DNI', 'RUC', 'CE', 'PASSPORT', 'PASAPORTE', ''], true)) {
            throw ValidationException::withMessages([
                'document_type' => [
                    'El cliente no tiene un tipo de documento valido para emitir boleta.',
                ],
            ]);
        }
    }
}
