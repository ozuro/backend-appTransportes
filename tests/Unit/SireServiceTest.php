<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\ElectronicBillingConfig;
use App\Services\Sunat\SireService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SireServiceTest extends TestCase
{
    public function test_fetch_sales_uses_secure_oauth_flow_and_normalizes_records(): void
    {
        Config::set('sunat.sire.security_url_template', 'https://api-seguridad.sunat.gob.pe/v1/clientessol/{client_id}/oauth2/token/');
        Config::set('sunat.sire.api_base_url', 'https://api-sire.sunat.gob.pe');
        Config::set('sunat.sire.scope', 'https://api-sire.sunat.gob.pe');
        Config::set('sunat.sire.endpoints.sales', '/ventas/{period}');

        Http::fake([
            'https://api-seguridad.sunat.gob.pe/v1/clientessol/client-sire/oauth2/token/' => Http::response([
                'access_token' => 'token-sire-test',
                'token_type' => 'Bearer',
            ], 200),
            'https://api-sire.sunat.gob.pe/ventas/202604' => Http::response([
                'message' => 'RVIE consultado correctamente.',
                'items' => [[
                    'serie' => 'F001',
                    'numero' => '15',
                    'fecEmision' => '2026-04-01',
                    'nomRazonSocialAdquirente' => 'Cliente Demo SAC',
                    'numDocAdquirente' => '20111111111',
                    'codTipoCDP' => '01',
                    'mtoTotal' => '118.50',
                    'codEstado' => '1',
                    'desEstado' => 'ACEPTADO',
                ]],
            ], 200),
        ]);

        $company = new Company([
            'ruc' => '20123456789',
        ]);

        $config = new ElectronicBillingConfig([
            'sol_user' => 'MODDATOS',
            'sol_password' => 'clave-sol-segura',
            'extra_settings' => [
                'sire_client_id' => 'client-sire',
                'sire_client_secret' => 'secret-sire',
            ],
        ]);

        $result = app(SireService::class)->fetchSales($company, $config, '202604');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api-seguridad.sunat.gob.pe/v1/clientessol/client-sire/oauth2/token/'
                && ($data['grant_type'] ?? null) === 'password'
                && ($data['scope'] ?? null) === 'https://api-sire.sunat.gob.pe'
                && ($data['client_id'] ?? null) === 'client-sire'
                && ($data['client_secret'] ?? null) === 'secret-sire'
                && ($data['username'] ?? null) === '20123456789MODDATOS'
                && ($data['password'] ?? null) === 'clave-sol-segura';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-sire.sunat.gob.pe/ventas/202604'
                && $request->hasHeader('Authorization', 'Bearer token-sire-test');
        });

        $this->assertSame('RVIE consultado correctamente.', $result['status']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('Factura', $result['items'][0]['tipo']);
        $this->assertSame('F001-15', $result['items'][0]['numero']);
        $this->assertSame('Cliente Demo SAC', $result['items'][0]['contraparteNombre']);
        $this->assertSame(118.50, $result['items'][0]['total']);
        $this->assertSame('ACEPTADO', $result['items'][0]['estado']);
    }

    public function test_build_summary_aggregates_sales_and_purchases(): void
    {
        Config::set('sunat.sire.security_url_template', 'https://api-seguridad.sunat.gob.pe/v1/clientessol/{client_id}/oauth2/token/');
        Config::set('sunat.sire.api_base_url', 'https://api-sire.sunat.gob.pe');
        Config::set('sunat.sire.scope', 'https://api-sire.sunat.gob.pe');
        Config::set('sunat.sire.endpoints.sales', '/ventas/{period}');
        Config::set('sunat.sire.endpoints.purchases', '/compras/{period}');

        Http::fake([
            'https://api-seguridad.sunat.gob.pe/v1/clientessol/client-sire/oauth2/token/' => Http::response([
                'access_token' => 'token-sire-test',
            ], 200),
            'https://api-sire.sunat.gob.pe/ventas/202604' => Http::response([
                'message' => 'RVIE listo.',
                'items' => [
                    ['serie' => 'B001', 'numero' => '1', 'mtoTotal' => '50.00'],
                    ['serie' => 'B001', 'numero' => '2', 'mtoTotal' => '70.00'],
                ],
            ], 200),
            'https://api-sire.sunat.gob.pe/compras/202604' => Http::response([
                'message' => 'RCE listo.',
                'items' => [
                    ['numSerie' => 'E001', 'numComprobante' => '10', 'mtoTotalCp' => '35.40'],
                ],
            ], 200),
        ]);

        $company = new Company([
            'ruc' => '20123456789',
        ]);

        $config = new ElectronicBillingConfig([
            'sol_user' => 'MODDATOS',
            'sol_password' => 'clave-sol-segura',
            'extra_settings' => [
                'sire_client_id' => 'client-sire',
                'sire_client_secret' => 'secret-sire',
            ],
        ]);

        $summary = app(SireService::class)->buildSummary($company, $config, '202604');

        $this->assertSame('202604', $summary['period']);
        $this->assertSame(2, $summary['sales_count']);
        $this->assertSame(120.00, $summary['sales_amount']);
        $this->assertSame(1, $summary['purchases_count']);
        $this->assertSame(35.40, $summary['purchases_amount']);
        $this->assertSame('RVIE listo.', $summary['sales_status']);
        $this->assertSame('RCE listo.', $summary['purchases_status']);
    }

    public function test_fetch_sales_requires_sire_client_credentials(): void
    {
        Config::set('sunat.sire.client_id', null);
        Config::set('sunat.sire.client_secret', null);
        Config::set('sunat.sire.endpoints.sales', '/ventas/{period}');

        $company = new Company([
            'ruc' => '20123456789',
        ]);

        $config = new ElectronicBillingConfig([
            'sol_user' => 'MODDATOS',
            'sol_password' => 'clave-sol-segura',
            'extra_settings' => [],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('client_id');

        app(SireService::class)->fetchSales($company, $config, '202604');
    }
}
