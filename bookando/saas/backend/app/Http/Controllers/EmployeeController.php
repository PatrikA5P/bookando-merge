<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/employees
     *
     * Paginated list with optional filters and search.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Employee::where('tenant_id', $tenantId);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Search by name, email, or position
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/employees
     *
     * Create a new employee.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees')->where('tenant_id', $tenantId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'ON_LEAVE', 'TERMINATED'])],
            'hire_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'salary_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'color' => ['nullable', 'string', 'max:7'],
            'avatar' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status'] = $validated['status'] ?? 'ACTIVE';

        $employee = Employee::create($validated);

        return response()->json([
            'data' => $this->transformModel($employee),
        ], 201);
    }

    /**
     * GET /api/v1/employees/{id}
     *
     * Single employee.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($employee),
        ]);
    }

    /**
     * PUT /api/v1/employees/{id}
     *
     * Partial update.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('employees')->where('tenant_id', $tenantId)->ignore($employee->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'ON_LEAVE', 'TERMINATED'])],
            'hire_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'salary_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'color' => ['nullable', 'string', 'max:7'],
            'avatar' => ['nullable', 'string', 'max:500'],
        ]);

        $employee->update($validated);

        return response()->json([
            'data' => $this->transformModel($employee->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/employees/{id}
     *
     * Hard delete.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
        $employee->delete();

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/v1/employees/{id}/status
     *
     * Update employee status. Automatically sets exit_date when TERMINATED.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'ON_LEAVE', 'TERMINATED'])],
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'TERMINATED' && !$employee->exit_date) {
            $updateData['exit_date'] = Carbon::today()->toDateString();
        }

        $employee->update($updateData);

        return response()->json([
            'data' => $this->transformModel($employee->fresh()),
        ]);
    }
}
