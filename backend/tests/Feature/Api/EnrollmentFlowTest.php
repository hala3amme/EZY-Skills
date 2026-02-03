<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Models\User;

test('student requests enrollment then teacher approves and content unlocks', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'image_url' => null,
        'title' => 'Test Course',
        'description' => 'Test description',
        'demo_url' => null,
        'curriculum_url' => null,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'serial_number' => 1,
        'title' => 'Video 1',
        'description' => null,
        'video_url' => 'https://example.com/video',
    ]);

    // Initially locked for student (not enrolled)
    $this->actingAs($student)
        ->getJson("/api/courses/{$course->id}/content")
        ->assertOk()
        ->assertJsonPath('videos.0.video_url', null)
        ->assertJsonPath('videos.0.is_locked', true);

    // Student requests enrollment
    $this->actingAs($student)
        ->postJson("/api/courses/{$course->id}/enroll")
        ->assertCreated()
        ->assertJsonPath('enrollment.status', EnrollmentStatus::Pending->value);

    expect($teacher->notifications()->count())->toBeGreaterThan(0);

    $enrollmentId = $course->enrollments()->where('student_id', $student->id)->value('id');
    expect($enrollmentId)->not->toBeNull();

    // Teacher approves
    $this->actingAs($teacher)
        ->postJson("/api/teacher/enrollments/{$enrollmentId}/approve")
        ->assertOk()
        ->assertJsonPath('enrollment.status', EnrollmentStatus::Approved->value);

    // Content unlocked after approval
    $this->actingAs($student)
        ->getJson("/api/courses/{$course->id}/content")
        ->assertOk()
        ->assertJsonPath('videos.0.is_locked', false)
        ->assertJsonPath('videos.0.video_url', 'https://example.com/video');
});
