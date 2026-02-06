<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimeEntryController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/time-entries
     *
     * Paginated list, filterable by employee_id and date range.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = TimeEntry::where('tenant_id', $tenantId);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $paginator = $query->orderByDesc('date')->orderByDesc('clock_in')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/time-entries
     *
     * Create a new time entry.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'date' => ['required', 'date'],
            'clock_in' => ['required', 'string', 'max:10'],
            'clock_out' => ['nullable', 'string', 'max:10'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['break_minutes'] = $validated['break_minutes'] ?? 0;

        $entry = TimeEntry::create($validated);

        return response()->json([
            'data' => $this->transformModel($entry),
        ], 201);
    }

    /**
     * GET /api/v1/time-entries/{id}
     *
     * Single time entry.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $entry = TimeEntry::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($entry),
        ]);
    }

    /**
     * PUT /api/v1/time-entries/{id}
     *
     * Update a time entry.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $entry = TimeEntry::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'employee_id' => ['sometimes', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'date' => ['sometimes', 'date'],
            'clock_in' => ['sometimes', 'string', 'max:10'],
            'clock_out' => ['nullable', 'string', 'max:10'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $entry->update($validated);

        return response()->json([
            'data' => $this->transformModel($entry->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/time-entries/{id}
     *
     * Delete a time entry.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $entry = TimeEntry::where('tenant_id', $tenantId)->findOrFail($id);
        $entry->delete();

        return response()->json(null, 204);
    }
}
