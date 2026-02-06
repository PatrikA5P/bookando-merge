<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/shifts
     *
     * List shifts, filterable by employee_id and date range.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Shift::where('tenant_id', $tenantId);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $paginator = $query->orderByDesc('date')->orderBy('start_time')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/shifts
     *
     * Create a new shift.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'date' => ['required', 'date'],
            'type' => ['required', 'string', Rule::in(['early', 'late', 'night', 'off'])],
            'start_time' => ['required', 'string', 'max:10'],
            'end_time' => ['required', 'string', 'max:10'],
        ]);

        $validated['tenant_id'] = $tenantId;

        $shift = Shift::create($validated);

        return response()->json([
            'data' => $this->transformModel($shift),
        ], 201);
    }

    /**
     * GET /api/v1/shifts/{id}
     *
     * Single shift.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $shift = Shift::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($shift),
        ]);
    }

    /**
     * PUT /api/v1/shifts/{id}
     *
     * Update a shift.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $shift = Shift::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'employee_id' => ['sometimes', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'date' => ['sometimes', 'date'],
            'type' => ['sometimes', 'string', Rule::in(['early', 'late', 'night', 'off'])],
            'start_time' => ['sometimes', 'string', 'max:10'],
            'end_time' => ['sometimes', 'string', 'max:10'],
        ]);

        $shift->update($validated);

        return response()->json([
            'data' => $this->transformModel($shift->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/shifts/{id}
     *
     * Delete a shift.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $shift = Shift::where('tenant_id', $tenantId)->findOrFail($id);
        $shift->delete();

        return response()->json(null, 204);
    }
}
