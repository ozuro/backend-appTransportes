<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\TransportServiceController;
use App\Http\Resources\TransportServiceResource;
use App\Models\ServiceSettlement;
use App\Models\TransportService;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class TransportServiceResourceTest extends TestCase
{
    public function test_service_relations_skip_settlement_when_table_is_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('service_settlements')
            ->andReturn(false);

        $controller = new TransportServiceController(app(CompanyContextService::class));
        $method = new ReflectionMethod($controller, 'serviceRelations');
        $method->setAccessible(true);

        $this->assertSame(['client', 'vehicle', 'driver'], $method->invoke($controller));
    }

    public function test_resource_omits_settlement_when_relation_is_not_loaded(): void
    {
        $payload = $this->resolveResource($this->fakeTransportService());

        $this->assertArrayNotHasKey('settlement', $payload);
    }

    public function test_resource_includes_settlement_when_relation_is_loaded(): void
    {
        $service = $this->fakeTransportService();
        $settlement = new ServiceSettlement();
        $settlement->forceFill([
            'id' => 7,
            'company_id' => 1,
            'transport_service_id' => 15,
            'gross_amount' => 120,
            'company_amount' => 80,
            'owner_amount' => 40,
            'driver_amount' => 0,
            'expense_amount' => 0,
            'status' => 'draft',
        ]);

        $service->setRelation('settlement', $settlement);

        $payload = $this->resolveResource($service);

        $this->assertSame(7, $payload['settlement']['id']);
        $this->assertSame('draft', $payload['settlement']['status']);
    }

    private function fakeTransportService(): TransportService
    {
        $service = new TransportService();
        $service->forceFill([
            'id' => 15,
            'company_id' => 1,
            'client_id' => 2,
            'vehicle_id' => null,
            'driver_id' => null,
            'service_code' => 'SRV-001-000015',
            'service_type' => 'cargo',
            'status' => 'pending',
            'origin_address' => 'Lima',
            'destination_address' => 'Callao',
            'quoted_amount' => 120,
            'final_amount' => 120,
            'payment_status' => 'pending',
        ]);

        return $service;
    }

    /**
     * Resuelve el resource completo para que Laravel filtre MissingValue.
     *
     * @return array<string, mixed>
     */
    private function resolveResource(TransportService $service): array
    {
        return (new TransportServiceResource($service))->resolve(
            Request::create('/api/services', 'GET')
        );
    }
}
