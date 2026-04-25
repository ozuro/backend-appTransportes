<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\Sunat\SunatBillingService;
use ReflectionMethod;
use Tests\TestCase;

class SunatBillingServiceTest extends TestCase
{
    public function test_build_payload_preserves_dispatch_carrier_guide_data(): void
    {
        $company = new Company([
            'id' => 9,
            'trade_name' => 'Transportes Demo',
            'legal_name' => 'TRANSPORTES DEMO SAC',
            'ruc' => '20123456789',
        ]);

        $service = app(SunatBillingService::class);
        $method = new ReflectionMethod($service, 'buildPayload');
        $method->setAccessible(true);

        $payload = $method->invoke($service, $company, [
            'client_id' => 3,
            'transport_service_id' => 7,
            'document_type' => 'dispatch_carrier',
            'issue_date' => '2026-04-02',
            'currency_code' => 'PEN',
            'subtotal_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '0.00',
            'payload' => [
                'guide' => [
                    'remitente_numero_de_documento' => '20601234567',
                    'numero_de_placa' => 'ABC123',
                ],
                'items' => [
                    [
                        'description' => 'Carga transportada',
                        'quantity' => 1,
                    ],
                ],
            ],
        ], 'V001');

        $this->assertSame('dispatch_carrier', $payload['document']['type']);
        $this->assertSame('20601234567', $payload['guide']['remitente_numero_de_documento']);
        $this->assertSame('ABC123', $payload['guide']['numero_de_placa']);
        $this->assertSame('Carga transportada', $payload['items'][0]['description']);
        $this->assertSame([], $payload['lines']);
    }
}
