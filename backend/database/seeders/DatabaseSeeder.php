<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseObjective;
use App\Models\CourseProject;
use App\Models\CourseTool;
use App\Models\CourseVideo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $teacher = User::factory()->teacher()->create([
            'name' => 'Demo Teacher',
            'email' => 'teacher@example.com',
            'role' => UserRole::Teacher,
            'password' => 'password',
        ]);

        User::factory()->student()->create([
            'name' => 'Demo Student',
            'email' => 'student@example.com',
            'role' => UserRole::Student,
            'password' => 'password',
        ]);

        $tags = collect(['AI', 'Backend', 'DevOps', 'Featured'])->map(function (string $name) {
            return Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'slug' => Str::slug($name)]
            );
        });

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'image_url' => 'https://example.com/course-image.png',
            'title' => 'Laravel Backend Fundamentals',
            'description' => 'Build a clean REST API with Laravel, Sanctum, and MySQL.',
            'demo_url' => 'https://example.com/demo',
            'curriculum_url' => 'https://example.com/curriculum.zip',
        ]);

        $course->tags()->sync($tags->take(2)->pluck('id'));

        CourseObjective::insert([
            ['course_id' => $course->id, 'position' => 1, 'objective' => 'Understand REST API design', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => $course->id, 'position' => 2, 'objective' => 'Implement auth with Sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => $course->id, 'position' => 3, 'objective' => 'Build enrollment workflow', 'created_at' => now(), 'updated_at' => now()],
        ]);

        CourseVideo::insert([
            ['course_id' => $course->id, 'serial_number' => 1, 'title' => 'Introduction', 'description' => 'Overview of the course', 'video_url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => $course->id, 'serial_number' => 2, 'title' => 'Sanctum Tokens', 'description' => null, 'video_url' => 'https://vimeo.com/123456', 'created_at' => now(), 'updated_at' => now()],
        ]);

        CourseProject::insert([
            ['course_id' => $course->id, 'title' => 'Sample API', 'description' => 'A demo REST API project', 'project_url' => 'https://github.com/example/repo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        CourseTool::insert([
            ['course_id' => $course->id, 'name' => 'Postman', 'url' => 'https://www.postman.com/', 'description' => 'API testing tool', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => $course->id, 'name' => 'MySQL', 'url' => 'https://www.mysql.com/', 'description' => 'Relational database', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
