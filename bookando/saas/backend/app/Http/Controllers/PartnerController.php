<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/partners
     *
     * Paginated list, filterable by status and type.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Partner::where('tenant_id', $tenantId);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/partners
     *
     * Create a new partner.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'revenue_share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'PENDING'])],
            'data_processing_agreement' => ['nullable', 'boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status'] = $validated['status'] ?? 'ACTIVE';
        $validated['revenue_share_percent'] = $validated['revenue_share_percent'] ?? 0;
        $validated['data_processing_agreement'] = $validated['data_processing_agreement'] ?? false;

        $partner = Partner::create($validated);

        return response()->json([
            'data' => $this->transformModel($partner),
        ], 201);
    }

    /**
     * GET /api/v1/partners/{id}
     *
     * Single partner.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $partner = Partner::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($partner),
        ]);
    }

    /**
     * PUT /api/v1/partners/{id}
     *
     * Update a partner.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $partner = Partner::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'revenue_share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'PENDING'])],
            'data_processing_agreement' => ['nullable', 'boolean'],
        ]);

        $partner->update($validated);

        return response()->json([
            'data' => $this->transformModel($partner->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/partners/{id}
     *
     * Delete a partner.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $partner = Partner::where('tenant_id', $tenantId)->findOrFail($id);
        $partner->delete();

        return response()->json(null, 204);
    }
}
