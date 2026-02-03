<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function myCourses(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Student) {
            return response()->json(['message' => 'Only students can access this endpoint.'], 403);
        }

        $courseIds = $user->enrollments()
            ->where('status', EnrollmentStatus::Approved->value)
            ->pluck('course_id');

        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->with(['teacher:id,name,email,phone_number,role', 'tags:id,name,slug'])
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
                'teacher' => [
                    'id' => $course->teacher->id,
                    'name' => $course->teacher->name,
                ],
                'tags' => $course->tags->pluck('name')->values(),
            ])->values(),
        ]);
    }
}
