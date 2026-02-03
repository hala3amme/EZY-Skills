<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'teacher_id',
        'image_url',
        'title',
        'description',
        'demo_url',
        'curriculum_url',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(CourseObjective::class)->orderBy('position');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CourseVideo::class)->orderBy('serial_number');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CourseProject::class);
    }

    public function tools(): HasMany
    {
        return $this->hasMany(CourseTool::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'course_tag');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
