<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/courses
     *
     * Paginated list, filterable by type/difficulty/visibility, searchable by title.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Course::where('tenant_id', $tenantId);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->input('difficulty'));
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->input('visibility'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy('title')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/courses
     *
     * Create a new course.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'string', 'max:100'],
            'duration_hours' => ['nullable', 'integer', 'min:0'],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'image' => ['nullable', 'string', 'max:500'],
            'certificate' => ['nullable', 'boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['type'] = $validated['type'] ?? 'online';
        $validated['difficulty'] = $validated['difficulty'] ?? 'beginner';
        $validated['visibility'] = $validated['visibility'] ?? 'public';
        $validated['price_minor'] = $validated['price_minor'] ?? 0;
        $validated['currency'] = $validated['currency'] ?? 'CHF';
        $validated['certificate'] = $validated['certificate'] ?? false;

        $course = Course::create($validated);

        return response()->json([
            'data' => $this->transformModel($course),
        ], 201);
    }

    /**
     * GET /api/v1/courses/{id}
     *
     * Single course.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($course),
        ]);
    }

    /**
     * PUT /api/v1/courses/{id}
     *
     * Update a course.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['sometimes', 'string', 'max:100'],
            'difficulty' => ['sometimes', 'string', 'max:100'],
            'visibility' => ['sometimes', 'string', 'max:100'],
            'duration_hours' => ['nullable', 'integer', 'min:0'],
            'price_minor' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'image' => ['nullable', 'string', 'max:500'],
            'certificate' => ['nullable', 'boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
        ]);

        $course->update($validated);

        return response()->json([
            'data' => $this->transformModel($course->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/courses/{id}
     *
     * Delete a course.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($id);
        $course->delete();

        return response()->json(null, 204);
    }
}
