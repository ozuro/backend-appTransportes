<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperatingExpense\StoreOperatingExpenseRequest;
use App\Http\Requests\OperatingExpense\UpdateOperatingExpenseRequest;
use App\Http\Resources\OperatingExpenseResource;
use App\Models\OperatingExpense;
use App\Models\TransportService;
use App\Models\Vehicle;
use App\Services\Tenant\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperatingExpenseController extends Controller
{
    public function __construct(
        private CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $company = $this->companyContext->resolve($request, $request->user());

        return OperatingExpenseResource::collection(
            OperatingExpense::with('vehicle')->where('company_id', $company->id)->latest()->paginate(20)
        );
    }

    public function store(StoreOperatingExpenseRequest $request): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        $data = $request->validated();

        $this->validateRelations($company->id, $data);

        $expense = OperatingExpense::create([
            ...$data,
            'company_id' => $company->id,
        ]);

        return (new OperatingExpenseResource($expense->load('vehicle')))->response()->setStatusCode(201);
    }

    public function show(Request $request, OperatingExpense $expense)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($expense->company_id === $company->id, 404);

        return new OperatingExpenseResource($expense->load('vehicle'));
    }

    public function update(UpdateOperatingExpenseRequest $request, OperatingExpense $expense)
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($expense->company_id === $company->id, 404);

        $data = $request->validated();
        $this->validateRelations($company->id, $data);

        $expense->update($data);

        return new OperatingExpenseResource($expense->fresh()->load('vehicle'));
    }

    public function destroy(Request $request, OperatingExpense $expense): JsonResponse
    {
        $company = $this->companyContext->resolve($request, $request->user());
        abort_unless($expense->company_id === $company->id, 404);

        $expense->delete();

        return response()->json(['message' => 'Gasto eliminado correctamente.']);
    }

    private function validateRelations(int $companyId, array $data): void
    {
        if (! empty($data['vehicle_id']) && ! Vehicle::where('company_id', $companyId)->where('id', $data['vehicle_id'])->exists()) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['El vehículo no pertenece a la empresa activa.'],
            ]);
        }

        if (! empty($data['transport_service_id']) && ! TransportService::where('company_id', $companyId)->where('id', $data['transport_service_id'])->exists()) {
            throw ValidationException::withMessages([
                'transport_service_id' => ['El servicio no pertenece a la empresa activa.'],
            ]);
        }
    }
}
