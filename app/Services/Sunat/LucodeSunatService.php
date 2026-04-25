<?php

namespace App\Services\Sunat;

use App\Models\Client;
use App\Models\ElectronicBillingConfig;
use App\Models\ElectronicDocument;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class LucodeSunatService
{
    public function send(ElectronicDocument $document, ElectronicBillingConfig $config): array
    {
        $document->loadMissing(['company', 'client', 'transportService', 'quotation']);

        $baseUrl = $this->resolveBaseUrl($config);
        $token = (string) ($config->api_token ?: config('sunat.lucode.api_token'));

        if ($baseUrl === '') {
            throw ValidationException::withMessages([
                'sunat' => ['Falta la URL base de Lucode/APISUNAT en la configuracion.'],
            ]);
        }

        if ($token === '') {
            throw ValidationException::withMessages([
                'sunat' => ['Falta el token API sandbox/produccion de Lucode en la configuracion.'],
            ]);
        }

        $payload = $this->buildPayload($document);
        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->post($baseUrl.'/api/v3/documents', $payload);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'sunat' => [
                    'No se pudo conectar con Lucode/APISUNAT para emitir el comprobante: '.$exception->getMessage(),
                ],
            ]);
        }

        $body = $this->decodeResponse($response);

        if (! $response->successful() || Arr::get($body, 'success') === false) {
            $message = Arr::get($body, 'message')
                ?: Arr::get($body, 'error')
                ?: 'Lucode/APISUNAT devolvio un error al emitir el documento.';

            throw ValidationException::withMessages([
                'sunat' => [$message],
            ]);
        }

        $statusBody = $this->consultStatus(
            $baseUrl,
            $token,
            $document->document_type,
            $document->series,
            (int) ($document->correlative ?: 1),
        );
        $effectiveBody = Arr::get($statusBody, 'success') === true ? $statusBody : $body;
        $payloadResponse = Arr::get($effectiveBody, 'payload', []);
        $estado = strtoupper((string) (
            Arr::get($payloadResponse, 'estado')
            ?: Arr::get($body, 'payload.estado')
        ));

        return [
            'provider' => 'lucode',
            'request_payload' => $payload,
            'response_body' => [
                'emission' => $body,
                'status' => $statusBody,
            ],
            'sunat_ticket' => null,
            'sunat_response_code' => $this->normalizeCode($estado),
            'sunat_response_message' => $this->normalizeMessage($effectiveBody),
            'xml_path' => Arr::get($payloadResponse, 'xml'),
            'cdr_path' => Arr::get($payloadResponse, 'cdr'),
            'pdf_path' => Arr::get($payloadResponse, 'pdf.ticket')
                ?: Arr::get($payloadResponse, 'pdf.a4')
                ?: Arr::get($payloadResponse, 'pdf'),
            'accepted' => $this->responseLooksAccepted($estado),
        ];
    }

    private function consultStatus(
        string $baseUrl,
        string $token,
        string $documentType,
        string $series,
        int $number
    ): array {
        $response = Http::acceptJson()
            ->withToken($token);

        try {
            $statusResponse = $response->post($baseUrl.'/api/v3/status', [
                'documento' => $documentType === 'invoice' ? 'factura' : 'boleta',
                'serie' => $series,
                'numero' => $number,
            ]);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'No se pudo consultar el estado Lucode/APISUNAT: '.$exception->getMessage(),
            ];
        }

        if (! $statusResponse->successful()) {
            return [];
        }

        return $this->decodeResponse($statusResponse);
    }

    private function buildPayload(ElectronicDocument $document): array
    {
        $client = $document->client;
        $payload = $document->payload ?? [];
        $documentNumber = $document->correlative ?: 1;
        $issueDate = $this->resolveIssueDate($document);

        if (! $client) {
            throw ValidationException::withMessages([
                'client' => ['El documento electronico requiere cliente asociado.'],
            ]);
        }

        $clientData = $this->resolveClientData($document, $client);

        $lucodePayload = [
            'documento' => $document->document_type === 'invoice' ? 'factura' : 'boleta',
            'serie' => $document->series,
            'numero' => $documentNumber,
            'fecha_de_emision' => $issueDate->format('Y-m-d'),
            'hora_de_emision' => CarbonImmutable::now('America/Lima')->format('H:i:s'),
            'moneda' => $document->currency_code ?: 'PEN',
            'tipo_operacion' => '0101',
            'cliente_tipo_de_documento' => $clientData['document_type'],
            'cliente_numero_de_documento' => $clientData['document_number'],
            'cliente_denominacion' => $clientData['name'],
            'cliente_direccion' => $clientData['address'],
            'items' => $this->buildItems($payload),
            'total' => number_format((float) $document->total_amount, 2, '.', ''),
        ];

        if ($document->document_type === 'invoice') {
            // APISUNAT documenta la fecha de vencimiento para factura; en contado
            // usamos la misma fecha de emision para que el payload quede completo.
            $lucodePayload['fecha_de_vencimiento'] = Arr::get(
                $payload,
                'meta.due_date',
                $issueDate->format('Y-m-d')
            );
        }

        return $lucodePayload;
    }

    private function resolveBaseUrl(ElectronicBillingConfig $config): string
    {
        $configuredUrl = $config->api_base_url ?: config('sunat.lucode.service_url');
        if (filled($configuredUrl)) {
            return rtrim((string) $configuredUrl, '/');
        }

        $environment = $config->environment === 'production' ? 'production' : 'sandbox';
        $configKey = $environment === 'production'
            ? 'sunat.lucode.production_url'
            : 'sunat.lucode.sandbox_url';

        return rtrim((string) config($configKey), '/');
    }

    private function buildItems(array $payload): array
    {
        $lines = Arr::get($payload, 'lines', []);

        if (! is_array($lines) || $lines === []) {
            throw ValidationException::withMessages([
                'payload' => ['El documento necesita al menos un item para emitirse con Lucode.'],
            ]);
        }

        return array_map(function ($line) {
            $quantity = (float) ($line['quantity'] ?? 1);
            $unitValue = (float) ($line['unit_value'] ?? 0);
            $taxAmount = (float) ($line['tax_amount'] ?? 0);
            $taxed = $taxAmount > 0;

            return [
                'unidad_de_medida' => $line['unit_code'] ?? 'NIU',
                'descripcion' => $line['description'] ?? 'Servicio de transporte',
                'cantidad' => $this->formatDecimal($quantity, 2),
                'valor_unitario' => $this->formatDecimal($unitValue, 6),
                'porcentaje_igv' => $taxed ? '18' : '0',
                'codigo_tipo_afectacion_igv' => $taxed ? '10' : '20',
                'nombre_tributo' => $taxed ? 'IGV' : 'EXO',
            ];
        }, $lines);
    }

    private function resolveClientData(ElectronicDocument $document, Client $client): array
    {
        $documentType = strtoupper(trim((string) $client->document_type));
        $documentNumber = trim((string) ($client->document_number ?? ''));
        $clientName = trim((string) ($client->business_name ?: $client->name ?: ''));
        $clientAddress = trim((string) ($client->address ?: '-'));

        if ($document->document_type === 'invoice') {
            if ($documentType !== 'RUC' || $documentNumber === '') {
                throw ValidationException::withMessages([
                    'client' => ['Para emitir factura con Lucode el cliente debe tener RUC valido.'],
                ]);
            }

            return [
                'document_type' => '6',
                'document_number' => $documentNumber,
                'name' => $clientName !== '' ? $clientName : 'CLIENTE',
                'address' => $clientAddress !== '' ? $clientAddress : '-',
            ];
        }

        if ($documentType !== '' && $documentNumber !== '') {
            return [
                'document_type' => $this->mapClientDocumentType($documentType),
                'document_number' => $documentNumber,
                'name' => $clientName !== '' ? $clientName : 'CLIENTE',
                'address' => $clientAddress !== '' ? $clientAddress : '-',
            ];
        }

        return [
            'document_type' => '1',
            'document_number' => '99999999',
            'name' => 'CLIENTE VARIOS',
            'address' => '-',
        ];
    }

    private function mapClientDocumentType(string $documentType): string
    {
        return match (strtoupper($documentType)) {
            'DNI' => '1',
            'RUC' => '6',
            'CE' => '4',
            'PASSPORT', 'PASAPORTE' => '7',
            default => '1',
        };
    }

    private function formatDecimal(float $value, int $scale): string
    {
        return number_format($value, $scale, '.', '');
    }

    private function resolveIssueDate(ElectronicDocument $document): CarbonImmutable
    {
        $today = CarbonImmutable::now('America/Lima')->startOfDay();
        $issueDate = $document->issue_date
            ? CarbonImmutable::instance($document->issue_date)->setTimezone('America/Lima')->startOfDay()
            : $today;

        if ($issueDate->greaterThan($today)) {
            return $today;
        }

        if ($issueDate->lessThan($today->subDays(3))) {
            throw ValidationException::withMessages([
                'sunat' => [
                    'La fecha_de_emision debe ser una fecha valida: puede ser hoy o hasta 3 dias previos a la fecha actual.',
                ],
            ]);
        }

        return $issueDate;
    }

    private function decodeResponse(Response $response): array
    {
        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeCode(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function normalizeMessage(array $responseBody): ?string
    {
        return Arr::get($responseBody, 'message')
            ?: Arr::get($responseBody, 'payload.estado');
    }

    private function responseLooksAccepted(string $status): bool
    {
        return $status === 'ACEPTADO';
    }
}
