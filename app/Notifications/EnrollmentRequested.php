<?php

namespace App\Notifications;

use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class EnrollmentRequested extends Notification
{
    use Queueable;

    public function __construct(private readonly CourseEnrollment $enrollment)
    {
        $this->enrollment->loadMissing(['course:id,title,teacher_id', 'student:id,name,email,phone_number,role']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'enrollment_requested';
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enrollment_requested',
            'enrollment_id' => $this->enrollment->id,
            'course' => [
                'id' => $this->enrollment->course->id,
                'title' => $this->enrollment->course->title,
            ],
            'student' => [
                'id' => $this->enrollment->student->id,
                'name' => $this->enrollment->student->name,
                'email' => $this->enrollment->student->email,
                'phone_number' => $this->enrollment->student->phone_number,
            ],
            'action' => [
                'route' => '/teacher/enrollments',
                'params' => [
                    'status' => 'pending',
                ],
            ],
        ];
    }
}
