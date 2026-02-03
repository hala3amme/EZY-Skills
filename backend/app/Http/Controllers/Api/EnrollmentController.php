<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollments\RequestEnrollmentRequest;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Notifications\EnrollmentRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function requestEnrollment(Course $course, RequestEnrollmentRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role !== UserRole::Student) {
            return response()->json(['message' => 'Only students can enroll.'], 403);
        }

        $existing = CourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Enrollment request already exists.',
                'enrollment' => [
                    'id' => $existing->id,
                    'status' => $existing->status->value,
                ],
            ]);
        }

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $user->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $course->loadMissing('teacher');
        $course->teacher->notify(new EnrollmentRequested($enrollment));

        return response()->json([
            'message' => 'Enrollment request submitted.',
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
            ],
        ], 201);
    }

    public function myEnrollments(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = CourseEnrollment::query()
            ->where('student_id', $user->id)
            ->with(['course.teacher:id,name,email,phone_number,role', 'course.tags:id,name,slug'])
            ->latest()
            ->get();

        return response()->json([
            'enrollments' => $enrollments->map(fn (CourseEnrollment $e) => [
                'id' => $e->id,
                'status' => $e->status->value,
                'reviewed_at' => $e->reviewed_at,
                'course' => [
                    'id' => $e->course->id,
                    'title' => $e->course->title,
                    'image_url' => $e->course->image_url,
                    'teacher' => [
                        'id' => $e->course->teacher->id,
                        'name' => $e->course->teacher->name,
                    ],
                    'tags' => $e->course->tags->pluck('name')->values(),
                ],
            ])->values(),
        ]);
    }

    public function teacherEnrollments(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Teacher) {
            return response()->json(['message' => 'Only teachers can access this endpoint.'], 403);
        }

        $status = $request->query('status');

        $query = CourseEnrollment::query()
            ->whereHas('course', fn ($q) => $q->where('teacher_id', $user->id))
            ->with(['course:id,title,teacher_id', 'student:id,name,email,phone_number,role'])
            ->latest();

        if (is_string($status) && in_array($status, ['pending', 'approved', 'declined'], true)) {
            $query->where('status', $status);
        }

        $enrollments = $query->get();

        return response()->json([
            'enrollments' => $enrollments->map(fn (CourseEnrollment $e) => [
                'id' => $e->id,
                'status' => $e->status->value,
                'created_at' => $e->created_at,
                'reviewed_at' => $e->reviewed_at,
                'course' => [
                    'id' => $e->course->id,
                    'title' => $e->course->title,
                ],
                'student' => [
                    'id' => $e->student->id,
                    'name' => $e->student->name,
                    'email' => $e->student->email,
                    'phone_number' => $e->student->phone_number,
                ],
            ])->values(),
        ]);
    }

    public function approve(CourseEnrollment $enrollment, Request $request): JsonResponse
    {
        return $this->review($enrollment, $request, EnrollmentStatus::Approved);
    }

    public function decline(CourseEnrollment $enrollment, Request $request): JsonResponse
    {
        return $this->review($enrollment, $request, EnrollmentStatus::Declined);
    }

    private function review(CourseEnrollment $enrollment, Request $request, EnrollmentStatus $status): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Teacher) {
            return response()->json(['message' => 'Only teachers can access this endpoint.'], 403);
        }

        $enrollment->load('course');

        if ($enrollment->course->teacher_id !== $user->id) {
            return response()->json(['message' => 'You do not own this course.'], 403);
        }

        $enrollment->update([
            'status' => $status,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Enrollment updated.',
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
                'reviewed_at' => $enrollment->reviewed_at,
            ],
        ]);
    }
}
