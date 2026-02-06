<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Bundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/bundles
     *
     * All bundles for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $bundles = Bundle::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $this->transformCollection($bundles),
        ]);
    }

    /**
     * POST /api/v1/bundles
     *
     * Create a new bundle.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_minor' => ['required', 'integer', 'min:0'],
            'savings_minor' => ['nullable', 'integer', 'min:0'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['savings_minor'] = $validated['savings_minor'] ?? 0;

        $bundle = Bundle::create($validated);

        return response()->json([
            'data' => $this->transformModel($bundle),
        ], 201);
    }

    /**
     * GET /api/v1/bundles/{id}
     *
     * Single bundle.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $bundle = Bundle::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($bundle),
        ]);
    }

    /**
     * PUT /api/v1/bundles/{id}
     *
     * Update a bundle.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $bundle = Bundle::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_minor' => ['sometimes', 'integer', 'min:0'],
            'savings_minor' => ['nullable', 'integer', 'min:0'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
        ]);

        $bundle->update($validated);

        return response()->json([
            'data' => $this->transformModel($bundle->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/bundles/{id}
     *
     * Delete a bundle.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $bundle = Bundle::where('tenant_id', $tenantId)->findOrFail($id);
        $bundle->delete();

        return response()->json(null, 204);
    }
}
