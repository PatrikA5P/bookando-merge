<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/vouchers
     *
     * Paginated list, filterable by active status.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Voucher::where('tenant_id', $tenantId);

        if ($request->has('active')) {
            $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/vouchers
     *
     * Create a new voucher.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vouchers')->where('tenant_id', $tenantId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_minor' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['active'] = $validated['active'] ?? true;
        $validated['usage_count'] = 0;

        $voucher = Voucher::create($validated);

        return response()->json([
            'data' => $this->transformModel($voucher),
        ], 201);
    }

    /**
     * GET /api/v1/vouchers/{id}
     *
     * Single voucher.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $voucher = Voucher::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($voucher),
        ]);
    }

    /**
     * PUT /api/v1/vouchers/{id}
     *
     * Update a voucher.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $voucher = Voucher::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('vouchers')->where('tenant_id', $tenantId)->ignore($voucher->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_minor' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
        ]);

        $voucher->update($validated);

        return response()->json([
            'data' => $this->transformModel($voucher->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/vouchers/{id}
     *
     * Delete a voucher.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $voucher = Voucher::where('tenant_id', $tenantId)->findOrFail($id);
        $voucher->delete();

        return response()->json(null, 204);
    }
}
