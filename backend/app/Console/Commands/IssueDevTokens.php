<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Models\User;
use Illuminate\Console\Command;

class IssueDevTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ezy:dev-tokens
        {--reset : Delete existing tokens for the demo users before issuing new ones}
        {--json : Output JSON only (useful for scripts)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue Sanctum tokens for the seeded demo teacher and student for quick Postman testing.';

    public function handle(): int
    {
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            ['name' => 'Demo Teacher', 'role' => UserRole::Teacher, 'password' => 'password']
        );
        $teacher->forceFill(['role' => UserRole::Teacher])->save();

        $student = User::firstOrCreate(
            ['email' => 'student@example.com'],
            ['name' => 'Demo Student', 'role' => UserRole::Student, 'password' => 'password']
        );
        $student->forceFill(['role' => UserRole::Student])->save();

        if ($this->option('reset')) {
            $teacher->tokens()->delete();
            $student->tokens()->delete();
        }

        $teacherToken = $teacher->createToken('postman-teacher')->plainTextToken;
        $studentToken = $student->createToken('postman-student')->plainTextToken;

        $course = Course::query()
            ->where('teacher_id', $teacher->id)
            ->latest('id')
            ->first();

        if (!$course) {
            $course = Course::create([
                'teacher_id' => $teacher->id,
                'image_url' => null,
                'title' => 'Demo Course',
                'description' => 'Demo course used for API testing in Postman.',
                'demo_url' => null,
                'curriculum_url' => null,
            ]);

            CourseVideo::create([
                'course_id' => $course->id,
                'serial_number' => 1,
                'title' => 'Demo Video',
                'description' => 'Locked until enrollment approved.',
                'video_url' => 'https://example.com/video',
            ]);
        }

        $payload = [
            'baseUrl' => 'http://127.0.0.1:8000',
            'teacher' => [
                'id' => $teacher->id,
                'email' => $teacher->email,
                'token' => $teacherToken,
            ],
            'student' => [
                'id' => $student->id,
                'email' => $student->email,
                'token' => $studentToken,
            ],
            'courseId' => (string) $course->id,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->info('EZY Skills dev tokens issued (copy into Postman variables)');
        $this->line('');
        $this->line("teacherToken = {$teacherToken}");
        $this->line("token        = {$studentToken}");
        $this->line("courseId     = {$course->id}");
        $this->line('');
        $this->comment('Demo credentials: teacher@example.com / password | student@example.com / password');
        $this->comment('Tip: run with --reset to revoke old tokens first.');

        return self::SUCCESS;
    }
}
