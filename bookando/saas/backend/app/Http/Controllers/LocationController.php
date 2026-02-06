<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/locations
     *
     * All locations for the tenant with room count.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $locations = Location::where('tenant_id', $tenantId)
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $locations->map(fn ($loc) => $this->transformModel($loc, [
                'roomCount' => $loc->rooms_count,
            ]))->values()->toArray(),
        ]);
    }

    /**
     * POST /api/v1/locations
     *
     * Create a new location.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $validated['tenant_id'] = $tenantId;

        $location = Location::create($validated);

        return response()->json([
            'data' => $this->transformModel($location),
        ], 201);
    }

    /**
     * GET /api/v1/locations/{id}
     *
     * Single location with room count.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $location = Location::where('tenant_id', $tenantId)
            ->withCount('rooms')
            ->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($location, [
                'roomCount' => $location->rooms_count,
            ]),
        ]);
    }

    /**
     * PUT /api/v1/locations/{id}
     *
     * Update a location.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $location = Location::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $location->update($validated);

        return response()->json([
            'data' => $this->transformModel($location->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/locations/{id}
     *
     * Delete a location.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $location = Location::where('tenant_id', $tenantId)->findOrFail($id);
        $location->delete();

        return response()->json(null, 204);
    }
}
