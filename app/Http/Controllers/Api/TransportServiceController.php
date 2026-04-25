<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransportService\StoreTransportServiceRequest;
use App\Http\Requests\TransportService\UpdateTransportServiceRequest;
use App\Http\Resources\TransportServiceResource;
use App\Models\Client;
use App\Models\Driver;
use App\Models\TransportService;
use App\Models\Vehicle;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TransportServiceController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        try {
            return TransportServiceResource::collection(
                $this->servicesQuery($company->id, $this->serviceRelations())
                    ->paginate(20)
            );
        } catch (QueryException $exception) {
            if (! $this->isMissingSettlementTableException($exception)) {
                throw $exception;
            }

            return TransportServiceResource::collection(
                $this->servicesQuery($company->id, $this->baseServiceRelations())
                    ->paginate(20)
            );
        }
    }

    public function store(StoreTransportServiceRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();

        $this->validateRelations($company->id, $data);

        $service = TransportService::create(array_merge(
            $data,
            [
                'company_id' => $company->id,
                'service_code' => $this->generateServiceCode($company->id),
                'status' => $data['status'] ?? 'pending',
                'payment_status' => $data['payment_status'] ?? 'pending',
            ]
        ));

        return (new TransportServiceResource($this->loadServiceRelations($service)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, TransportService $service)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($service->company_id === $company->id, 404);

        return new TransportServiceResource($this->loadServiceRelations($service));
    }

    public function update(UpdateTransportServiceRequest $request, TransportService $service)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($service->company_id === $company->id, 404);

        $data = $request->validated();
        $this->validateRelations($company->id, $data);

        $service->update($data);

        return new TransportServiceResource($this->loadServiceRelations($service->fresh()));
    }

    public function destroy(Request $request, TransportService $service): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($service->company_id === $company->id, 404);

        $service->delete();

        return response()->json(['message' => 'Servicio eliminado correctamente.']);
    }

    private function validateRelations(int $companyId, array $data): void
    {
        if (! Client::where('company_id', $companyId)->where('id', $data['client_id'])->exists()) {
            throw ValidationException::withMessages([
                'client_id' => ['El cliente no pertenece a la empresa activa.'],
            ]);
        }

        if (! empty($data['vehicle_id']) && ! Vehicle::where('company_id', $companyId)->where('id', $data['vehicle_id'])->exists()) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['El vehículo no pertenece a la empresa activa.'],
            ]);
        }

        if (! empty($data['driver_id']) && ! Driver::where('company_id', $companyId)->where('id', $data['driver_id'])->exists()) {
            throw ValidationException::withMessages([
                'driver_id' => ['El chofer no pertenece a la empresa activa.'],
            ]);
        }
    }

    private function generateServiceCode(int $companyId): string
    {
        $prefix = 'SRV-' . str_pad((string) $companyId, 3, '0', STR_PAD_LEFT);
        $nextId = (TransportService::max('id') ?? 0) + 1;

        return $prefix . '-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mantiene compatibilidad mientras las tablas nuevas se despliegan.
     * Si service_settlements aun no existe, la API sigue respondiendo sin esa relacion.
     *
     * @return array<int, string>
     */
    private function serviceRelations(): array
    {
        $relations = $this->baseServiceRelations();

        if ($this->settlementsTableExists()) {
            $relations[] = 'settlement';
        }

        return $relations;
    }

    /**
     * @return array<int, string>
     */
    private function baseServiceRelations(): array
    {
        return ['client', 'vehicle', 'driver'];
    }

    private function servicesQuery(int $companyId, array $relations)
    {
        return TransportService::with($relations)
            ->where('company_id', $companyId)
            ->latest();
    }

    private function loadServiceRelations(?TransportService $service): TransportService
    {
        abort_unless($service instanceof TransportService, 404);

        try {
            return $service->load($this->serviceRelations());
        } catch (QueryException $exception) {
            if (! $this->isMissingSettlementTableException($exception)) {
                throw $exception;
            }

            return $service->load($this->baseServiceRelations());
        }
    }

    private function settlementsTableExists(): bool
    {
        try {
            return Schema::hasTable('service_settlements');
        } catch (\Throwable) {
            return false;
        }
    }

    private function isMissingSettlementTableException(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'service_settlements');
    }
}
