<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return DriverResource::collection(
            Driver::with('assignedVehicle')->where('company_id', $company->id)->latest()->paginate(20)
        );
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();

        $this->validateAssignedVehicle($company->id, $data['assigned_vehicle_id'] ?? null);
        $this->validateLicenseNumber($data['license_number']);

        $driver = Driver::create(array_merge(
            $data,
            [
                'company_id' => $company->id,
                'status' => $data['status'] ?? 'available',
                'is_active' => $data['is_active'] ?? true,
            ]
        ));

        return (new DriverResource($driver->load('assignedVehicle')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Driver $driver)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($driver->company_id === $company->id, 404);

        return new DriverResource($driver->load('assignedVehicle'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($driver->company_id === $company->id, 404);

        $data = $request->validated();
        $this->validateAssignedVehicle($company->id, $data['assigned_vehicle_id'] ?? null);

        if (isset($data['license_number'])) {
            $this->validateLicenseNumber($data['license_number'], $driver->id);
        }

        $driver->update($data);

        return new DriverResource($driver->fresh()->load('assignedVehicle'));
    }

    public function destroy(Request $request, Driver $driver): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($driver->company_id === $company->id, 404);

        $driver->delete();

        return response()->json(['message' => 'Chofer eliminado correctamente.']);
    }

    private function validateAssignedVehicle(int $companyId, ?int $vehicleId): void
    {
        if (! $vehicleId) {
            return;
        }

        if (! Vehicle::where('company_id', $companyId)->where('id', $vehicleId)->exists()) {
            throw ValidationException::withMessages([
                'assigned_vehicle_id' => ['El vehículo asignado no pertenece a la empresa activa.'],
            ]);
        }
    }

    private function validateLicenseNumber(string $licenseNumber, ?int $ignoreDriverId = null): void
    {
        $query = Driver::where('license_number', $licenseNumber);

        if ($ignoreDriverId) {
            $query->where('id', '!=', $ignoreDriverId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'license_number' => ['La licencia ya se encuentra registrada.'],
            ]);
        }
    }
}
