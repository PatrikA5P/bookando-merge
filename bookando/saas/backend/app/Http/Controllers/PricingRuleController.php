<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\PricingRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingRuleController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/pricing-rules
     *
     * All pricing rules for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $rules = PricingRule::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $this->transformCollection($rules),
        ]);
    }

    /**
     * POST /api/v1/pricing-rules
     *
     * Create a new pricing rule.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['earlyBird', 'lastMinute', 'seasonal', 'demand'])],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'conditions' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['active'] = $validated['active'] ?? true;

        $rule = PricingRule::create($validated);

        return response()->json([
            'data' => $this->transformModel($rule),
        ], 201);
    }

    /**
     * GET /api/v1/pricing-rules/{id}
     *
     * Single pricing rule.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $rule = PricingRule::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($rule),
        ]);
    }

    /**
     * PUT /api/v1/pricing-rules/{id}
     *
     * Update a pricing rule.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $rule = PricingRule::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(['earlyBird', 'lastMinute', 'seasonal', 'demand'])],
            'discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'conditions' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
        ]);

        $rule->update($validated);

        return response()->json([
            'data' => $this->transformModel($rule->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/pricing-rules/{id}
     *
     * Delete a pricing rule.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $rule = PricingRule::where('tenant_id', $tenantId)->findOrFail($id);
        $rule->delete();

        return response()->json(null, 204);
    }
}
