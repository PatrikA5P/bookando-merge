<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/services
     *
     * Paginated list with optional filters and search.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Service::where('tenant_id', $tenantId);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by title
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy('title')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/services
     *
     * Create a new service.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'sale_price_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['active'] = $validated['active'] ?? true;

        $service = Service::create($validated);

        return response()->json([
            'data' => $this->transformModel($service),
        ], 201);
    }

    /**
     * GET /api/v1/services/{id}
     *
     * Single service.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $service = Service::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($service),
        ]);
    }

    /**
     * PUT /api/v1/services/{id}
     *
     * Partial update.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $service = Service::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'sale_price_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);

        $service->update($validated);

        return response()->json([
            'data' => $this->transformModel($service->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/services/{id}
     *
     * Hard delete.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $service = Service::where('tenant_id', $tenantId)->findOrFail($id);
        $service->delete();

        return response()->json(null, 204);
    }
}
