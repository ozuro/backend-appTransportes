<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return VehicleResource::collection(
            Vehicle::where('company_id', $company->id)->latest()->paginate(20)
        );
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();
        $plate = strtoupper($data['plate']);

        if (Vehicle::where('plate', $plate)->exists()) {
            throw ValidationException::withMessages([
                'plate' => ['La placa ya se encuentra registrada.'],
            ]);
        }

        $vehicle = Vehicle::create([
            ...$data,
            'company_id' => $company->id,
            'plate' => $plate,
            'operational_status' => $data['operational_status'] ?? 'active',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return (new VehicleResource($vehicle))->response()->setStatusCode(201);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($vehicle->company_id === $company->id, 404);

        return new VehicleResource($vehicle);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($vehicle->company_id === $company->id, 404);

        $data = $request->validated();

        if (isset($data['plate'])) {
            $plate = strtoupper($data['plate']);
            if (Vehicle::where('plate', $plate)->where('id', '!=', $vehicle->id)->exists()) {
                throw ValidationException::withMessages([
                    'plate' => ['La placa ya se encuentra registrada.'],
                ]);
            }
            $data['plate'] = $plate;
        }

        $vehicle->update($data);

        return new VehicleResource($vehicle->fresh());
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($vehicle->company_id === $company->id, 404);

        $vehicle->delete();

        return response()->json(['message' => 'Vehículo eliminado correctamente.']);
    }
}
