<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Teacher) {
            return response()->json(['message' => 'Only teachers can access this endpoint.'], 403);
        }

        $courses = Course::query()
            ->where('teacher_id', $user->id)
            ->with(['tags:id,name,slug'])
            ->withCount([
                'enrollments as pending_enrollment_requests_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->latest()
            ->get();

        return response()->json([
            'courses' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'image_url' => $course->image_url,
                'title' => $course->title,
                'description' => $course->description,
                'demo_url' => $course->demo_url,
                'curriculum_url' => $course->curriculum_url,
                'tags' => $course->tags->pluck('name')->values(),
                'pending_enrollment_requests_count' => $course->pending_enrollment_requests_count,
            ])->values(),
        ]);
    }
}
