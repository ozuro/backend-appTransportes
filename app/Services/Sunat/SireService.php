<?php

namespace App\Services\Sunat;

use App\Models\Company;
use App\Models\ElectronicBillingConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SireService
{
    private array $tokenCache = [];

    public function buildSummary(Company $company, ?ElectronicBillingConfig $config, string $period): array
    {
        $sales = $this->fetchSales($company, $config, $period);
        $purchases = $this->fetchPurchases($company, $config, $period);

        return [
            'period' => $period,
            'sales_count' => count($sales['items']),
            'sales_amount' => $this->sumTotals($sales['items']),
            'purchases_count' => count($purchases['items']),
            'purchases_amount' => $this->sumTotals($purchases['items']),
            'sales_status' => $sales['status'],
            'purchases_status' => $purchases['status'],
        ];
    }

    public function fetchSales(Company $company, ?ElectronicBillingConfig $config, string $period): array
    {
        return $this->fetchBook('sales', $company, $config, $period);
    }

    public function fetchPurchases(Company $company, ?ElectronicBillingConfig $config, string $period): array
    {
        return $this->fetchBook('purchases', $company, $config, $period);
    }

    private function fetchBook(
        string $kind,
        Company $company,
        ?ElectronicBillingConfig $config,
        string $period
    ): array {
        $token = $this->getAccessToken($company, $config);
        $url = $this->resolveEndpoint($kind, $company, $config, $period);
        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout($this->timeout())
            ->retry(2, 250)
            ->get($url);

        $body = $this->decodeJson($response);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'sunat' => [$this->extractMessage($body, 'SUNAT devolvio un error al consultar SIRE.')],
            ]);
        }

        $items = $this->extractItems($body, $kind);

        return [
            'items' => $this->normalizeItems($items, $kind, $period),
            'status' => $this->extractMessage($body, 'Consulta completada.'),
            'raw' => $body,
        ];
    }

    private function getAccessToken(Company $company, ?ElectronicBillingConfig $config): string
    {
        $cacheKey = (string) ($company->id ?: $company->ruc ?: spl_object_id($company));
        if (isset($this->tokenCache[$cacheKey])) {
            return $this->tokenCache[$cacheKey];
        }

        $clientId = trim((string) ($config?->sire_client_id ?: config('sunat.sire.client_id')));
        $clientSecret = trim((string) ($config?->sire_client_secret ?: config('sunat.sire.client_secret')));
        $username = trim((string) (
            $config?->sire_username
            ?: config('sunat.sire.username')
            ?: trim((string) $company->ruc).trim((string) ($config?->sol_user ?? ''))
        ));
        $password = trim((string) ($config?->sol_password ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            throw ValidationException::withMessages([
                'sunat' => [
                    'Faltan client_id y client_secret de SIRE. Configuralos en .env o en extra_settings del modulo SUNAT.',
                ],
            ]);
        }

        if (trim((string) $company->ruc) === '' || $username === '' || $password === '') {
            throw ValidationException::withMessages([
                'sunat' => [
                    'SIRE necesita RUC, usuario SOL y clave SOL validos en la configuracion SUNAT de la empresa.',
                ],
            ]);
        }

        $urlTemplate = (string) config('sunat.sire.security_url_template');
        $url = str_replace('{client_id}', rawurlencode($clientId), $urlTemplate);

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($this->timeout())
            ->retry(2, 250)
            ->post($url, [
                'grant_type' => 'password',
                'scope' => (string) config('sunat.sire.scope', 'https://api-sire.sunat.gob.pe'),
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password,
            ]);

        $body = $this->decodeJson($response);

        if (! $response->successful() || ! filled($body['access_token'] ?? null)) {
            throw ValidationException::withMessages([
                'sunat' => [
                    $this->extractMessage($body, 'No se pudo obtener el token OAuth de SIRE.'),
                ],
            ]);
        }

        return $this->tokenCache[$cacheKey] = (string) $body['access_token'];
    }

    private function resolveEndpoint(
        string $kind,
        Company $company,
        ?ElectronicBillingConfig $config,
        string $period
    ): string {
        $template = $kind === 'sales'
            ? ($config?->sire_sales_endpoint ?: config('sunat.sire.endpoints.sales'))
            : ($config?->sire_purchases_endpoint ?: config('sunat.sire.endpoints.purchases'));

        $template = trim((string) $template);
        if ($template === '') {
            $label = $kind === 'sales' ? 'SUNAT_SIRE_SALES_ENDPOINT' : 'SUNAT_SIRE_PURCHASES_ENDPOINT';

            throw ValidationException::withMessages([
                'sunat' => [
                    'Falta configurar el endpoint '.$label.' para consultar '.($kind === 'sales' ? 'ventas' : 'compras').' en SIRE.',
                ],
            ]);
        }

        $path = strtr($template, [
            '{period}' => $period,
            '{ruc}' => (string) $company->ruc,
        ]);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $baseUrl = rtrim((string) config('sunat.sire.api_base_url', 'https://api-sire.sunat.gob.pe'), '/');

        return $baseUrl.'/'.ltrim($path, '/');
    }

    private function extractItems(array $body, string $kind): array
    {
        $candidates = $kind === 'sales'
            ? ['data', 'items', 'records', 'registros', 'sales', 'ventas', 'rvie']
            : ['data', 'items', 'records', 'registros', 'purchases', 'compras', 'rce'];

        if (array_is_list($body)) {
            return $body;
        }

        foreach ($candidates as $key) {
            $value = $body[$key] ?? null;
            if (is_array($value) && array_is_list($value)) {
                return $value;
            }
            if (is_array($value)) {
                foreach ($candidates as $nestedKey) {
                    $nested = $value[$nestedKey] ?? null;
                    if (is_array($nested) && array_is_list($nested)) {
                        return $nested;
                    }
                }
            }
        }

        return [];
    }

    private function normalizeItems(array $items, string $kind, string $period): array
    {
        return array_values(array_filter(array_map(function ($item) use ($kind, $period) {
            if (! is_array($item)) {
                return null;
            }

            $series = $this->string($item, ['serie', 'series', 'numSerie', 'serComprobante']);
            $number = $this->string($item, ['numero', 'number', 'numComprobante', 'correlativo']);
            $fullNumber = $this->string($item, ['full_number', 'numeroCompleto', 'serieNumero']);
            if ($fullNumber === '' && ($series !== '' || $number !== '')) {
                $fullNumber = trim($series.($series !== '' && $number !== '' ? '-' : '').$number);
            }

            $documentTypeCode = $this->string($item, ['codTipoCDP', 'document_type', 'tipo', 'tipoComprobante']);
            $statusText = $this->string($item, ['desEstado', 'status', 'estado', 'registry_status']);
            $statusCode = $this->string($item, ['codEstado', 'sunat_status']);
            $partnerName = $kind === 'sales'
                ? $this->string($item, ['nomRazonSocialAdquirente', 'customer_name', 'partner_name', 'contraparte_nombre'])
                : $this->string($item, ['nomRazonSocialProveedor', 'supplier_name', 'partner_name', 'contraparte_nombre']);
            $partnerDocument = $kind === 'sales'
                ? $this->string($item, ['numDocAdquirente', 'customer_document', 'partner_document', 'contraparte_documento'])
                : $this->string($item, ['numDocProveedor', 'supplier_document', 'partner_document', 'contraparte_documento']);
            $amount = $this->number($item, ['mtoTotal', 'mtoTotalCp', 'total_amount', 'total', 'importe_total']);
            $date = $this->string($item, ['fecEmision', 'issue_date', 'fecha_emision', 'date']);
            $currency = $this->string($item, ['codMoneda', 'currency_code', 'currency', 'moneda']);

            return [
                'id' => $this->string($item, ['id', 'codCar', 'car', 'ticket']) ?: md5(json_encode([$kind, $period, $fullNumber, $partnerDocument, $date, $amount])),
                'period' => $this->string($item, ['perTributario', 'period', 'periodo']) ?: $period,
                'tipo' => $this->mapDocumentType($documentTypeCode),
                'numero' => $fullNumber !== '' ? $fullNumber : '-',
                'fechaEmision' => $date !== '' ? $date : '-',
                'contraparteNombre' => $partnerName !== '' ? $partnerName : 'Sin nombre',
                'contraparteDocumento' => $partnerDocument !== '' ? $partnerDocument : '-',
                'moneda' => $currency !== '' ? $currency : 'PEN',
                'total' => $amount,
                'estado' => $statusText !== '' ? $statusText : 'Consultado',
                'estadoSunat' => $statusCode !== '' ? $statusCode : null,
                'observacion' => $this->string($item, ['observacion', 'message', 'msg', 'detalle']) ?: null,
            ];
        }, $items)));
    }

    private function mapDocumentType(string $value): string
    {
        return match (strtoupper(trim($value))) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de credito',
            '08' => 'Nota de debito',
            default => $value !== '' ? $value : 'Comprobante',
        };
    }

    private function sumTotals(array $items): float
    {
        return round(array_reduce($items, function ($carry, $item) {
            return $carry + (float) ($item['total'] ?? 0);
        }, 0.0), 2);
    }

    private function extractMessage(array $body, string $fallback): string
    {
        foreach (['message', 'msg', 'descripcion', 'status'] as $key) {
            $value = Arr::get($body, $key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $errors = Arr::get($body, 'errors');
        if (is_array($errors) && $errors !== []) {
            $messages = [];
            foreach ($errors as $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $messages[] = (string) $item;
                    }
                } else {
                    $messages[] = (string) $value;
                }
            }
            if ($messages !== []) {
                return implode(' | ', $messages);
            }
        }

        return $fallback;
    }

    private function decodeJson($response): array
    {
        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    private function string(array $source, array $keys): string
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function number(array $source, array $keys): float
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    private function timeout(): int
    {
        return (int) config('sunat.sire.timeout', 20);
    }
}
