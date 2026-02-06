<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/courses/{courseId}/lessons
     *
     * All lessons for a course, ordered by sort_order.
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Verify the course belongs to this tenant
        $course = Course::where('tenant_id', $tenantId)->findOrFail($courseId);

        $lessons = Lesson::where('course_id', $course->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $this->transformCollection($lessons),
        ]);
    }

    /**
     * POST /api/v1/courses/{courseId}/lessons
     *
     * Create a new lesson under a course.
     */
    public function store(Request $request, int $courseId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($courseId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['course_id'] = $course->id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $lesson = Lesson::create($validated);

        return response()->json([
            'data' => $this->transformModel($lesson),
        ], 201);
    }

    /**
     * GET /api/v1/courses/{courseId}/lessons/{id}
     *
     * Single lesson.
     */
    public function show(Request $request, int $courseId, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($courseId);

        $lesson = Lesson::where('course_id', $course->id)->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($lesson),
        ]);
    }

    /**
     * PUT /api/v1/courses/{courseId}/lessons/{id}
     *
     * Update a lesson.
     */
    public function update(Request $request, int $courseId, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($courseId);

        $lesson = Lesson::where('course_id', $course->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        $lesson->update($validated);

        return response()->json([
            'data' => $this->transformModel($lesson->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/courses/{courseId}/lessons/{id}
     *
     * Delete a lesson.
     */
    public function destroy(Request $request, int $courseId, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $course = Course::where('tenant_id', $tenantId)->findOrFail($courseId);

        $lesson = Lesson::where('course_id', $course->id)->findOrFail($id);
        $lesson->delete();

        return response()->json(null, 204);
    }
}
