<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return ClientResource::collection(
            Client::where('company_id', $company->id)->latest()->paginate(20)
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();

        $client = Client::create(array_merge(
            $data,
            [
                'company_id' => $company->id,
                'category' => $data['category'] ?? 'occasional',
                'is_active' => $data['is_active'] ?? true,
            ]
        ));

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Request $request, Client $client)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($client->company_id === $company->id, 404);

        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($client->company_id === $company->id, 404);

        $client->update($request->validated());

        return new ClientResource($client->fresh());
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($client->company_id === $company->id, 404);

        $client->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente.']);
    }
}
