<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/rooms
     *
     * List rooms, filterable by location_id.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Room::where('tenant_id', $tenantId);

        if ($request->filled('location_id')) {
            $query->where('location_id', (int) $request->input('location_id'));
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/rooms
     *
     * Create a new room.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['capacity'] = $validated['capacity'] ?? 1;

        $room = Room::create($validated);

        return response()->json([
            'data' => $this->transformModel($room),
        ], 201);
    }

    /**
     * GET /api/v1/rooms/{id}
     *
     * Single room.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $room = Room::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($room),
        ]);
    }

    /**
     * PUT /api/v1/rooms/{id}
     *
     * Update a room.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $room = Room::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'location_id' => ['sometimes', 'integer', Rule::exists('locations', 'id')->where('tenant_id', $tenantId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
        ]);

        $room->update($validated);

        return response()->json([
            'data' => $this->transformModel($room->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/rooms/{id}
     *
     * Delete a room.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $room = Room::where('tenant_id', $tenantId)->findOrFail($id);
        $room->delete();

        return response()->json(null, 204);
    }
}
