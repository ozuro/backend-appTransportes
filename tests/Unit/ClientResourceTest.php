<?php

namespace Tests\Unit;

use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClientResourceTest extends TestCase
{
    public function test_client_resource_exposes_ruc_and_dni_aliases(): void
    {
        $companyClient = new Client([
            'client_type' => 'company',
            'document_type' => 'RUC',
            'document_number' => '20601234567',
            'name' => 'Empresa Demo SAC',
        ]);

        $personClient = new Client([
            'client_type' => 'person',
            'document_type' => 'DNI',
            'document_number' => '12345678',
            'name' => 'Cliente Persona',
        ]);

        $companyPayload = $this->resolveResource($companyClient);
        $personPayload = $this->resolveResource($personClient);

        $this->assertSame('20601234567', $companyPayload['ruc']);
        $this->assertNull($companyPayload['dni']);
        $this->assertSame('12345678', $personPayload['dni']);
        $this->assertNull($personPayload['ruc']);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveResource(Client $client): array
    {
        return (new ClientResource($client))->resolve(
            Request::create('/api/clients', 'GET')
        );
    }
}
