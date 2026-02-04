<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image_url,
            'title' => $this->title,
            'description' => $this->description,
            'demo_url' => $this->demo_url,
            'curriculum_url' => $this->curriculum_url,
            'teacher' => $this->whenLoaded('teacher', fn () => new UserResource($this->teacher)),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->values()),
        ];
    }
}
