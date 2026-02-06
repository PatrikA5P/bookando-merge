<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/categories
     *
     * Returns all categories for the tenant, ordered by sort_order.
     * No pagination -- returns full list.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $this->transformCollection($categories),
        ]);
    }

    /**
     * POST /api/v1/categories
     *
     * Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['active'] = $validated['active'] ?? true;

        $category = Category::create($validated);

        return response()->json([
            'data' => $this->transformModel($category),
        ], 201);
    }

    /**
     * GET /api/v1/categories/{id}
     *
     * Single category.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $category = Category::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($category),
        ]);
    }

    /**
     * PUT /api/v1/categories/{id}
     *
     * Update a category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $category = Category::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json([
            'data' => $this->transformModel($category->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/categories/{id}
     *
     * Hard delete.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $category = Category::where('tenant_id', $tenantId)->findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }
}
