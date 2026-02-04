<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StudentDashboardController;
use App\Http\Controllers\Api\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('courses/{course}/content', [CourseController::class, 'content']);
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'requestEnrollment']);

    Route::get('me/enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('me/courses', [StudentDashboardController::class, 'myCourses']);

    Route::get('teacher/dashboard', [TeacherDashboardController::class, 'dashboard']);
    Route::get('teacher/enrollments', [EnrollmentController::class, 'teacherEnrollments']);
    Route::post('teacher/enrollments/{enrollment}/approve', [EnrollmentController::class, 'approve']);
    Route::post('teacher/enrollments/{enrollment}/decline', [EnrollmentController::class, 'decline']);

    Route::get('me/notifications', [NotificationController::class, 'index']);
    Route::post('me/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});
