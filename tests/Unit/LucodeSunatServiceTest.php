<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Company;
use App\Models\ElectronicBillingConfig;
use App\Models\ElectronicDocument;
use App\Services\Sunat\LucodeSunatService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LucodeSunatServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00', 'America/Lima'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 10:00:00', 'America/Lima'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_send_uses_official_apisunat_contract_and_env_fallbacks(): void
    {
        Config::set('sunat.lucode.service_url', 'https://sandbox.apisunat.test');
        Config::set('sunat.lucode.api_token', 'token-env-test');

        Http::fake([
            'https://sandbox.apisunat.test/api/v3/documents' => Http::response([
                'success' => true,
                'message' => 'Boleta B001-1 emitida correctamente',
                'payload' => [
                    'estado' => 'PENDIENTE',
                ],
            ], 200),
            'https://sandbox.apisunat.test/api/v3/status' => Http::response([
                'success' => true,
                'message' => 'Boleta B001-1 se encuentra registrado en SUNAT.',
                'payload' => [
                    'estado' => 'ACEPTADO',
                    'xml' => 'https://files.test/doc.xml',
                    'cdr' => 'https://files.test/doc.zip',
                    'pdf' => [
                        'ticket' => 'https://files.test/ticket.pdf',
                    ],
                ],
            ], 200),
        ]);

        $company = new Company([
            'trade_name' => 'Transportes Demo',
            'legal_name' => 'Transportes Demo SAC',
            'ruc' => '20123456789',
            'currency_code' => 'PEN',
            'address' => 'Av. Peru 123',
            'district' => 'LIMA',
            'province' => 'LIMA',
            'department' => 'LIMA',
        ]);

        $client = new Client([
            'name' => 'Cliente DNI',
            'document_type' => 'DNI',
            'document_number' => '12345678',
            'address' => 'Jr. Cliente 456',
            'email' => 'cliente@example.com',
        ]);

        $document = new ElectronicDocument([
            'document_type' => 'receipt',
            'series' => 'B001',
            'correlative' => 1,
            'issue_date' => Carbon::parse('2026-04-01'),
            'currency_code' => 'PEN',
            'subtotal_amount' => 100,
            'tax_amount' => 18,
            'total_amount' => 118,
            'payload' => [
                'meta' => ['description' => 'Servicio de transporte local'],
                'lines' => [
                    [
                        'code' => 'SRV-1',
                        'description' => 'Servicio de transporte local',
                        'quantity' => 1,
                        'unit_value' => 100,
                        'unit_price' => 118,
                        'sale_value' => 100,
                        'tax_amount' => 18,
                        'unit_code' => 'ZZ',
                        'sunat_code' => '78101803',
                    ],
                ],
            ],
        ]);
        $document->setRelation('company', $company);
        $document->setRelation('client', $client);

        $config = new ElectronicBillingConfig([
            'environment' => 'beta',
            'invoice_series' => 'F001',
            'receipt_series' => 'B001',
            'extra_settings' => [
                'provider' => 'lucode',
            ],
        ]);

        $result = app(LucodeSunatService::class)->send($document, $config);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sandbox.apisunat.test/api/v3/documents'
                && $request->hasHeader('Authorization', 'Bearer token-env-test')
                && ($data['documento'] ?? null) === 'boleta'
                && ($data['serie'] ?? null) === 'B001'
                && ($data['numero'] ?? null) === 1
                && ($data['cliente_tipo_de_documento'] ?? null) === '1'
                && ($data['cliente_numero_de_documento'] ?? null) === '12345678'
                && ($data['total'] ?? null) === '118.00'
                && ($data['items'][0]['valor_unitario'] ?? null) === '100.000000';
        });
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sandbox.apisunat.test/api/v3/status'
                && $request->hasHeader('Authorization', 'Bearer token-env-test')
                && ($data['documento'] ?? null) === 'boleta'
                && ($data['serie'] ?? null) === 'B001'
                && ($data['numero'] ?? null) === 1;
        });

        $this->assertSame('lucode', $result['provider']);
        $this->assertSame('ACEPTADO', $result['sunat_response_code']);
        $this->assertSame('Boleta B001-1 se encuentra registrado en SUNAT.', $result['sunat_response_message']);
        $this->assertSame('https://files.test/doc.xml', $result['xml_path']);
        $this->assertSame('https://files.test/doc.zip', $result['cdr_path']);
        $this->assertSame('https://files.test/ticket.pdf', $result['pdf_path']);
        $this->assertTrue($result['accepted']);
    }

    public function test_send_builds_invoice_payload_for_lucode_sandbox(): void
    {
        Config::set('sunat.lucode.service_url', null);
        Config::set('sunat.lucode.sandbox_url', 'https://sandbox.apisunat.test');
        Config::set('sunat.lucode.api_token', 'token-env-test');

        Http::fake([
            'https://sandbox.apisunat.test/api/v3/documents' => Http::response([
                'success' => true,
                'message' => 'Factura F001-12 emitida correctamente',
                'payload' => [
                    'estado' => 'PENDIENTE',
                ],
            ], 200),
            'https://sandbox.apisunat.test/api/v3/status' => Http::response([
                'success' => true,
                'message' => 'Factura F001-12 se encuentra registrado en SUNAT.',
                'payload' => [
                    'estado' => 'ACEPTADO',
                    'xml' => 'https://files.test/f001-12.xml',
                    'cdr' => 'https://files.test/f001-12.zip',
                    'pdf' => [
                        'a4' => 'https://files.test/f001-12-a4.pdf',
                    ],
                ],
            ], 200),
        ]);

        $company = new Company([
            'trade_name' => 'Transportes Demo',
            'legal_name' => 'Transportes Demo SAC',
            'ruc' => '20123456789',
            'currency_code' => 'PEN',
        ]);

        $client = new Client([
            'name' => 'Empresa Cliente',
            'business_name' => 'EMPRESA CLIENTE SAC',
            'document_type' => 'RUC',
            'document_number' => '20601234567',
            'address' => 'Av. Factura 100',
        ]);

        $document = new ElectronicDocument([
            'document_type' => 'invoice',
            'series' => 'F001',
            'correlative' => 12,
            'issue_date' => Carbon::parse('2026-04-02'),
            'currency_code' => 'PEN',
            'subtotal_amount' => 100,
            'tax_amount' => 18,
            'total_amount' => 118,
            'payload' => [
                'meta' => ['description' => 'Servicio de transporte empresarial'],
                'lines' => [
                    [
                        'code' => 'SRV-20',
                        'description' => 'Servicio de transporte empresarial',
                        'quantity' => 1,
                        'unit_value' => 100,
                        'unit_price' => 118,
                        'sale_value' => 100,
                        'tax_amount' => 18,
                        'unit_code' => 'NIU',
                    ],
                ],
            ],
        ]);
        $document->setRelation('company', $company);
        $document->setRelation('client', $client);

        $config = new ElectronicBillingConfig([
            'environment' => 'beta',
            'invoice_series' => 'F001',
            'receipt_series' => 'B001',
            'extra_settings' => [
                'provider' => 'lucode',
            ],
        ]);

        $result = app(LucodeSunatService::class)->send($document, $config);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sandbox.apisunat.test/api/v3/documents'
                && $request->hasHeader('Authorization', 'Bearer token-env-test')
                && ($data['documento'] ?? null) === 'factura'
                && ($data['serie'] ?? null) === 'F001'
                && ($data['numero'] ?? null) === 12
                && ($data['fecha_de_vencimiento'] ?? null) === '2026-04-02'
                && ($data['cliente_tipo_de_documento'] ?? null) === '6'
                && ($data['cliente_numero_de_documento'] ?? null) === '20601234567'
                && ($data['cliente_denominacion'] ?? null) === 'EMPRESA CLIENTE SAC'
                && ($data['total'] ?? null) === '118.00'
                && ($data['items'][0]['codigo_tipo_afectacion_igv'] ?? null) === '10';
        });
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sandbox.apisunat.test/api/v3/status'
                && ($data['documento'] ?? null) === 'factura'
                && ($data['serie'] ?? null) === 'F001'
                && ($data['numero'] ?? null) === 12;
        });

        $this->assertSame('ACEPTADO', $result['sunat_response_code']);
        $this->assertSame('https://files.test/f001-12.xml', $result['xml_path']);
        $this->assertSame('https://files.test/f001-12.zip', $result['cdr_path']);
        $this->assertSame('https://files.test/f001-12-a4.pdf', $result['pdf_path']);
        $this->assertTrue($result['accepted']);
    }

    public function test_send_requires_api_token(): void
    {
        Config::set('sunat.lucode.service_url', 'https://sandbox.apisunat.test');
        Config::set('sunat.lucode.api_token', null);

        $company = new Company([
            'trade_name' => 'Transportes Demo',
            'legal_name' => 'Transportes Demo SAC',
            'ruc' => '20123456789',
            'currency_code' => 'PEN',
        ]);

        $client = new Client([
            'name' => 'Cliente DNI',
            'document_type' => 'DNI',
            'document_number' => '12345678',
        ]);

        $document = new ElectronicDocument([
            'document_type' => 'receipt',
            'series' => 'B001',
            'correlative' => 1,
            'issue_date' => Carbon::parse('2026-04-01'),
            'currency_code' => 'PEN',
            'subtotal_amount' => 100,
            'tax_amount' => 18,
            'total_amount' => 118,
            'payload' => [
                'lines' => [
                    [
                        'description' => 'Servicio',
                        'quantity' => 1,
                        'unit_value' => 100,
                        'unit_price' => 118,
                        'sale_value' => 100,
                        'tax_amount' => 18,
                    ],
                ],
            ],
        ]);
        $document->setRelation('company', $company);
        $document->setRelation('client', $client);

        $config = new ElectronicBillingConfig([
            'extra_settings' => ['provider' => 'lucode'],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('token API');

        app(LucodeSunatService::class)->send($document, $config);
    }

    public function test_send_uses_boleta_sin_documento_defaults_when_client_has_no_document(): void
    {
        Config::set('sunat.lucode.service_url', 'https://sandbox.apisunat.test');
        Config::set('sunat.lucode.api_token', 'token-env-test');

        Http::fake([
            'https://sandbox.apisunat.test/api/v3/documents' => Http::response([
                'success' => true,
                'message' => 'Boleta EB01-57 emitida correctamente',
                'payload' => [
                    'estado' => 'PENDIENTE',
                ],
            ], 200),
            'https://sandbox.apisunat.test/api/v3/status' => Http::response([
                'success' => true,
                'message' => 'Boleta EB01-57 se encuentra registrado en SUNAT.',
                'payload' => [
                    'estado' => 'PENDIENTE',
                    'xml' => 'https://files.test/eb01-57.xml',
                    'cdr' => null,
                    'pdf' => [
                        'ticket' => 'https://files.test/eb01-57.pdf',
                    ],
                ],
            ], 200),
        ]);

        $company = new Company([
            'trade_name' => 'Transportes Demo',
            'legal_name' => 'Transportes Demo SAC',
            'ruc' => '20123456789',
            'currency_code' => 'PEN',
        ]);

        $client = new Client([
            'name' => 'Cliente sin documento',
            'document_type' => null,
            'document_number' => null,
            'address' => null,
        ]);

        $document = new ElectronicDocument([
            'document_type' => 'receipt',
            'series' => 'EB01',
            'correlative' => 57,
            'issue_date' => Carbon::parse('2026-04-02'),
            'currency_code' => 'PEN',
            'subtotal_amount' => 127.12,
            'tax_amount' => 22.88,
            'total_amount' => 150.00,
            'payload' => [
                'meta' => ['description' => 'Traslado de carga'],
                'lines' => [
                    [
                        'description' => 'Traslado de carga',
                        'quantity' => 1,
                        'unit_value' => 127.118644,
                        'unit_price' => 150,
                        'tax_amount' => 22.88,
                        'unit_code' => 'NIU',
                    ],
                ],
            ],
        ]);
        $document->setRelation('company', $company);
        $document->setRelation('client', $client);

        $config = new ElectronicBillingConfig([
            'extra_settings' => ['provider' => 'lucode'],
        ]);

        app(LucodeSunatService::class)->send($document, $config);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sandbox.apisunat.test/api/v3/documents'
                && ($data['serie'] ?? null) === 'EB01'
                && ($data['numero'] ?? null) === 57
                && ($data['cliente_tipo_de_documento'] ?? null) === '1'
                && ($data['cliente_numero_de_documento'] ?? null) === '99999999'
                && ($data['cliente_denominacion'] ?? null) === 'CLIENTE VARIOS'
                && ($data['cliente_direccion'] ?? null) === '-'
                && ($data['total'] ?? null) === '150.00';
        });
    }
}
