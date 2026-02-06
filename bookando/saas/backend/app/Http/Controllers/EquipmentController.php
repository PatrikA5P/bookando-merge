<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/equipment
     *
     * List equipment, filterable by location_id, condition, and available.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Equipment::where('tenant_id', $tenantId);

        if ($request->filled('location_id')) {
            $query->where('location_id', (int) $request->input('location_id'));
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->input('condition'));
        }

        if ($request->has('available')) {
            $query->where('available', filter_var($request->input('available'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/equipment
     *
     * Create a new equipment item.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'condition' => ['nullable', 'string', 'max:100'],
            'available' => ['nullable', 'boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['condition'] = $validated['condition'] ?? 'good';
        $validated['available'] = $validated['available'] ?? true;

        $equipment = Equipment::create($validated);

        return response()->json([
            'data' => $this->transformModel($equipment),
        ], 201);
    }

    /**
     * GET /api/v1/equipment/{id}
     *
     * Single equipment item.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $equipment = Equipment::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($equipment),
        ]);
    }

    /**
     * PUT /api/v1/equipment/{id}
     *
     * Update an equipment item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $equipment = Equipment::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('tenant_id', $tenantId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'condition' => ['nullable', 'string', 'max:100'],
            'available' => ['nullable', 'boolean'],
        ]);

        $equipment->update($validated);

        return response()->json([
            'data' => $this->transformModel($equipment->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/equipment/{id}
     *
     * Delete an equipment item.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $equipment = Equipment::where('tenant_id', $tenantId)->findOrFail($id);
        $equipment->delete();

        return response()->json(null, 204);
    }
}
