<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseVideoResource extends JsonResource
{
    public function __construct($resource, private readonly bool $includeLink = false)
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
            'serial_number' => $this->serial_number,
            'title' => $this->title,
            'description' => $this->description,
            'is_locked' => !$this->includeLink,
            'video_url' => $this->includeLink ? $this->video_url : null,
        ];
    }
}
