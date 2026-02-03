<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = (string) $request->query('search', '');
        $tag = (string) $request->query('tag', '');

        $query = Course::query()->with([
            'teacher:id,name,email,phone_number,role',
            'tags:id,name,slug',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($tag !== '') {
            $query->whereHas('tags', function ($q) use ($tag): void {
                $q->where('slug', $tag)->orWhere('name', $tag);
            });
        }

        return CourseResource::collection($query->latest()->paginate(10));
    }

    public function show(Course $course, Request $request): CourseDetailResource
    {
        $course->load([
            'teacher:id,name,email,phone_number,role',
            'tags:id,name,slug',
            'objectives',
            'videos',
            'projects',
            'tools',
        ]);

        // Public course details always return locked content links; unlockable content is served via /courses/{course}/content.
        return new CourseDetailResource($course, false);
    }

    public function content(Course $course, Request $request): CourseDetailResource
    {
        $course->load([
            'teacher:id,name,email,phone_number,role',
            'tags:id,name,slug',
            'objectives',
            'videos',
            'projects',
            'tools',
        ]);

        $user = $request->user();
        $includeLinks = false;

        if ($user) {
            $includeLinks = ($course->teacher_id === $user->id)
                || $course->enrollments()
                    ->where('student_id', $user->id)
                    ->where('status', EnrollmentStatus::Approved->value)
                    ->exists();
        }

        return new CourseDetailResource($course, $includeLinks);
    }
}
