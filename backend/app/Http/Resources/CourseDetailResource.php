<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
{
    public function __construct($resource, private readonly bool $includeVideoLinks = false)
    {
        parent::__construct($resource);
    }

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
            'objectives' => $this->whenLoaded('objectives', fn () => $this->objectives->map(fn ($o) => [
                'id' => $o->id,
                'position' => $o->position,
                'objective' => $o->objective,
            ])->values()),
            'videos' => $this->whenLoaded('videos', fn () => $this->videos->map(
                fn ($v) => (new CourseVideoResource($v, $this->includeVideoLinks))->toArray($request)
            )->values()),
            'projects' => $this->whenLoaded('projects', fn () => $this->projects->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'project_url' => $p->project_url,
            ])->values()),
            'tools' => $this->whenLoaded('tools', fn () => $this->tools->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'url' => $t->url,
                'description' => $t->description,
            ])->values()),
        ];
    }
}
