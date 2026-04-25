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

        if ($document->document_type === 'dispatch_carrier') {
            return $this->sendDispatchCarrier($document, $config, $baseUrl, $token);
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
            'pdf_path' => $this->resolvePdfPath($payloadResponse, $document->document_type),
            'accepted' => $this->responseLooksAccepted($estado),
        ];
    }

    private function sendDispatchCarrier(
        ElectronicDocument $document,
        ElectronicBillingConfig $config,
        string $baseUrl,
        string $token
    ): array
    {
        $payload = $this->buildDispatchCarrierPayload($document, $config);

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->post($baseUrl.'/api/v1/gr-transportista', $payload);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'sunat' => [
                    'No se pudo conectar con Lucode/APISUNAT para emitir la guia de transportista: '.$exception->getMessage(),
                ],
            ]);
        }

        $body = $this->decodeResponse($response);

        if (! $response->successful() || Arr::get($body, 'success') === false) {
            $message = Arr::get($body, 'message')
                ?: Arr::get($body, 'error')
                ?: 'Lucode/APISUNAT devolvio un error al emitir la guia de transportista.';

            throw ValidationException::withMessages([
                'sunat' => [$message],
            ]);
        }

        $payloadResponse = Arr::get($body, 'payload', []);

        return [
            'provider' => 'lucode',
            'request_payload' => $payload,
            'response_body' => [
                'emission' => $body,
                'status' => [],
            ],
            'sunat_ticket' => null,
            'sunat_response_code' => 'ACEPTADO',
            'sunat_response_message' => Arr::get($body, 'message', 'Guia de transportista emitida correctamente.'),
            'xml_path' => Arr::get($payloadResponse, 'xml'),
            'cdr_path' => Arr::get($payloadResponse, 'cdr'),
            'pdf_path' => $this->resolvePdfPath($payloadResponse, $document->document_type),
            'accepted' => true,
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

    private function buildDispatchCarrierPayload(ElectronicDocument $document, ElectronicBillingConfig $config): array
    {
        $payload = $document->payload ?? [];
        $guide = Arr::get($payload, 'guide', []);
        $issueDate = $this->resolveIssueDate($document);

        if (! $document->company || ! filled($document->company->ruc)) {
            throw ValidationException::withMessages([
                'company' => ['La empresa transportista debe tener RUC antes de emitir la guia.'],
            ]);
        }

        if (! filled($this->guideValue($guide, 'ubigeo_transportista', $config->office_ubigeo))) {
            throw ValidationException::withMessages([
                'company' => ['Configura el ubigeo fiscal SUNAT de la empresa antes de emitir guias.'],
            ]);
        }

        $required = [
            'numero_registro_MTC_transportista' => ['numero_registro_MTC_transportista'],
            'remitente_numero_de_documento' => ['remitente_numero_de_documento', 'numero_documento_identidad_remitente'],
            'remitente_denominacion' => ['remitente_denominacion', 'denominacion_remitente'],
            'destinatario_numero_de_documento' => ['destinatario_numero_de_documento', 'numero_documento_identidad_destinatario'],
            'destinatario_denominacion' => ['destinatario_denominacion', 'denominacion_destinatario'],
            'punto_de_partida_ubigeo' => ['punto_de_partida_ubigeo', 'ubigeo_punto_partida'],
            'punto_de_partida_direccion' => ['punto_de_partida_direccion', 'direccion_punto_partida'],
            'punto_de_llegada_ubigeo' => ['punto_de_llegada_ubigeo', 'ubigeo_punto_llegada'],
            'punto_de_llegada_direccion' => ['punto_de_llegada_direccion', 'direccion_punto_llegada'],
            'numero_de_placa' => ['numero_de_placa', 'numero_placa_vehiculo_principal'],
            'conductor_numero_de_documento' => ['conductor_numero_de_documento', 'numero_documento_identidad_conductor_principal'],
            'conductor_nombres' => ['conductor_nombres', 'nombres_conductor_principal'],
            'conductor_apellidos' => ['conductor_apellidos', 'apellidos_conductor_principal'],
            'conductor_numero_de_licencia' => ['conductor_numero_de_licencia', 'numero_licencia_conducir_conductor_principal'],
            'peso_bruto_total' => ['peso_bruto_total', 'peso_bruto_total_carga'],
        ];

        foreach ($required as $field => $keys) {
            if (! filled($this->guideValue($guide, $keys))) {
                throw ValidationException::withMessages([
                    'guide' => ["La guia de transportista necesita el campo {$field}."],
                ]);
            }
        }

        $observaciones = $this->guideValue($guide, ['observaciones', 'observations'], '');
        $observaciones = filled($observaciones) ? [(string) $observaciones] : [];
        $documentNumber = (int) ($document->correlative ?: 1);

        return array_filter([
            'numeracion' => $document->series.'-'.$documentNumber,
            'fecha_emision' => $issueDate->format('Y-m-d'),
            'hora_emision' => CarbonImmutable::now('America/Lima')->format('H:i:s'),
            'observaciones' => $observaciones,
            'documentos_relacionados' => [],
            'numero_ruc_transportista' => $this->digits($document->company->ruc),
            'denominacion_transportista' => $document->company->legal_name ?: $document->company->trade_name,
            'direccion_transportista' => $document->company->address ?: '-',
            'urbanizacion_transportista' => $this->guideValue($guide, 'urbanizacion_transportista', '-'),
            'provincia_transportista' => $this->guideValue($guide, 'provincia_transportista', $config->office_province ?: $document->company->province),
            'ubigeo_transportista' => $this->guideValue($guide, 'ubigeo_transportista', $config->office_ubigeo),
            'departamento_transportista' => $this->guideValue($guide, 'departamento_transportista', $config->office_department ?: $document->company->department),
            'distrito_transportista' => $this->guideValue($guide, 'distrito_transportista', $config->office_district ?: $document->company->district),
            'numero_registro_MTC_transportista' => $this->guideValue($guide, 'numero_registro_MTC_transportista'),
            'numero_autorizacion_transportista' => $this->guideValue($guide, 'numero_autorizacion_transportista'),
            'codigo_entidad_autorizadora_transportista' => $this->guideValue($guide, 'codigo_entidad_autorizadora_transportista'),
            'tipo_documento_identidad_remitente' => $this->guideValue($guide, ['remitente_tipo_de_documento', 'tipo_documento_identidad_remitente'], '6'),
            'numero_documento_identidad_remitente' => $this->digits($this->guideValue($guide, ['remitente_numero_de_documento', 'numero_documento_identidad_remitente'])),
            'denominacion_remitente' => $this->guideValue($guide, ['remitente_denominacion', 'denominacion_remitente']),
            'tipo_documento_identidad_destinatario' => $this->guideValue($guide, ['destinatario_tipo_de_documento', 'tipo_documento_identidad_destinatario'], '6'),
            'numero_documento_identidad_destinatario' => $this->digits($this->guideValue($guide, ['destinatario_numero_de_documento', 'numero_documento_identidad_destinatario'])),
            'denominacion_destinatario' => $this->guideValue($guide, ['destinatario_denominacion', 'denominacion_destinatario']),
            'items' => $this->buildDispatchItems($payload),
            'ubigeo_punto_partida' => $this->guideValue($guide, ['punto_de_partida_ubigeo', 'ubigeo_punto_partida']),
            'direccion_punto_partida' => $this->guideValue($guide, ['punto_de_partida_direccion', 'direccion_punto_partida']),
            'ubigeo_punto_llegada' => $this->guideValue($guide, ['punto_de_llegada_ubigeo', 'ubigeo_punto_llegada']),
            'direccion_punto_llegada' => $this->guideValue($guide, ['punto_de_llegada_direccion', 'direccion_punto_llegada']),
            'numero_placa_vehiculo_principal' => strtoupper((string) $this->guideValue($guide, ['numero_de_placa', 'numero_placa_vehiculo_principal'])),
            'tipo_documento_identidad_conductor_principal' => $this->guideValue($guide, ['conductor_tipo_de_documento', 'tipo_documento_identidad_conductor_principal'], '1'),
            'numero_documento_identidad_conductor_principal' => $this->digits($this->guideValue($guide, ['conductor_numero_de_documento', 'numero_documento_identidad_conductor_principal'])),
            'nombres_conductor_principal' => $this->guideValue($guide, ['conductor_nombres', 'nombres_conductor_principal']),
            'apellidos_conductor_principal' => $this->guideValue($guide, ['conductor_apellidos', 'apellidos_conductor_principal']),
            'numero_licencia_conducir_conductor_principal' => $this->guideValue($guide, ['conductor_numero_de_licencia', 'numero_licencia_conducir_conductor_principal']),
            'fecha_inicio_traslado' => $this->guideValue($guide, ['fecha_inicio_de_traslado', 'fecha_inicio_traslado'], $issueDate->format('Y-m-d')),
            'anotacion_opcional_sobre_bienes_transportar' => $this->guideValue($guide, ['anotacion_opcional_sobre_bienes_transportar', 'observaciones']),
            'peso_bruto_total_carga' => $this->formatDecimal((float) $this->guideValue($guide, ['peso_bruto_total', 'peso_bruto_total_carga']), 3),
            'unidad_medida_peso_bruto_total_carga' => $this->guideValue($guide, ['peso_bruto_unidad_de_medida', 'unidad_medida_peso_bruto_total_carga'], 'KGM'),
            'indicador_traslado_total' => (bool) $this->guideValue($guide, 'indicador_traslado_total', true),
            'indicador_retorno_vehiculo_envase_vacio' => (bool) $this->guideValue($guide, 'indicador_retorno_vehiculo_envase_vacio', false),
            'indicador_retorno_vehiculo_vacio' => (bool) $this->guideValue($guide, 'indicador_retorno_vehiculo_vacio', false),
            'indicador_transbordo_programado' => (bool) $this->guideValue($guide, 'indicador_transbordo_programado', false),
            'indicador_trasporte_subcontratado' => (bool) $this->guideValue($guide, 'indicador_trasporte_subcontratado', false),
            'indicador_pagador_flete_remitente' => (bool) $this->guideValue($guide, 'indicador_pagador_flete_remitente', true),
            'indicador_pagador_flete_subcontratador' => (bool) $this->guideValue($guide, 'indicador_pagador_flete_subcontratador', false),
            'indicador_pagador_flete_tercero' => (bool) $this->guideValue($guide, 'indicador_pagador_flete_tercero', false),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function buildDispatchItems(array $payload): array
    {
        $items = Arr::get($payload, 'items', []);
        if (! is_array($items) || $items === []) {
            $items = Arr::get($payload, 'lines', []);
        }

        if (! is_array($items) || $items === []) {
            throw ValidationException::withMessages([
                'payload' => ['La guia de transportista necesita al menos un bien transportado.'],
            ]);
        }

        return array_map(function ($item) {
            return [
                'cantidad' => $this->formatDecimal((float) ($item['quantity'] ?? $item['cantidad'] ?? 1), 2),
                'unidad_medida' => $item['unit_code'] ?? $item['unidad_medida'] ?? 'NIU',
                'descripcion' => $item['description'] ?? $item['descripcion'] ?? 'Carga transportada',
                'codigo' => $item['code'] ?? $item['codigo'] ?? 'ITEM-1',
                'codigo_producto_sunat' => $item['sunat_code'] ?? $item['codigo_producto_sunat'] ?? '78101803',
            ];
        }, $items);
    }

    private function guideValue(array $guide, array|string $keys, mixed $default = null): mixed
    {
        foreach ((array) $keys as $key) {
            $value = Arr::get($guide, $key);
            if (filled($value)) {
                return $value;
            }
        }

        return $default;
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
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
        $documentNumber = preg_replace('/\D+/', '', (string) ($client->document_number ?? ''));
        $documentType = $this->normalizeClientDocumentType($client->document_type, $documentNumber);
        $clientName = trim((string) ($client->business_name ?: $client->name ?: ''));
        $clientAddress = trim((string) ($client->address ?: '-'));

        if ($document->document_type === 'invoice') {
            if ($documentType !== 'RUC' || strlen($documentNumber) !== 11) {
                throw ValidationException::withMessages([
                    'client' => ['Para emitir factura con Lucode el cliente debe tener RUC valido de 11 digitos.'],
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
            '1' => '1',
            '4' => '4',
            '6' => '6',
            '7' => '7',
            'DNI' => '1',
            'RUC' => '6',
            'CE' => '4',
            'PASSPORT', 'PASAPORTE' => '7',
            default => '1',
        };
    }

    private function resolvePdfPath(array $payloadResponse, string $documentType): mixed
    {
        if ($documentType === 'invoice') {
            return Arr::get($payloadResponse, 'pdf.a4')
                ?: Arr::get($payloadResponse, 'pdf')
                ?: Arr::get($payloadResponse, 'pdf.ticket');
        }

        return Arr::get($payloadResponse, 'pdf.ticket')
            ?: Arr::get($payloadResponse, 'pdf.a4')
            ?: Arr::get($payloadResponse, 'pdf');
    }

    private function normalizeClientDocumentType(?string $documentType, ?string $documentNumber = null): string
    {
        $type = strtoupper(trim((string) $documentType));
        $digits = preg_replace('/\D+/', '', (string) $documentNumber);

        if (in_array($type, ['6', 'RUC'], true) || strlen($digits) === 11) {
            return 'RUC';
        }

        if (in_array($type, ['1', 'DNI'], true) || strlen($digits) === 8) {
            return 'DNI';
        }

        return $type;
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
