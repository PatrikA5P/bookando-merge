<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Absence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsenceController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/absences
     *
     * List absences, filterable by employee_id, type, and status.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Absence::where('tenant_id', $tenantId);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginator = $query->orderByDesc('start_date')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/absences
     *
     * Create a new absence request.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', 'string', Rule::in(['vacation', 'sick', 'personal', 'training', 'maternity', 'military'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status'] = 'PENDING';

        $absence = Absence::create($validated);

        return response()->json([
            'data' => $this->transformModel($absence),
        ], 201);
    }

    /**
     * GET /api/v1/absences/{id}
     *
     * Single absence.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $absence = Absence::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($absence),
        ]);
    }

    /**
     * PUT /api/v1/absences/{id}
     *
     * Update an absence. Can change status (PENDING/APPROVED/REJECTED).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $absence = Absence::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'employee_id' => ['sometimes', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'type' => ['sometimes', 'string', Rule::in(['vacation', 'sick', 'personal', 'training', 'maternity', 'military'])],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $absence->update($validated);

        return response()->json([
            'data' => $this->transformModel($absence->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/absences/{id}
     *
     * Delete an absence.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $absence = Absence::where('tenant_id', $tenantId)->findOrFail($id);
        $absence->delete();

        return response()->json(null, 204);
    }
}
