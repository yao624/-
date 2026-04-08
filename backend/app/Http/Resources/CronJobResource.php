<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CronJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'object_type' => $this->object_type,
            'object_value' => $this->object_value,
            'timezone' => $this->timezone,
            'start_time' => $this->start_time ? $this->start_time->format('H:i') : '',
            'stop_time' => $this->stop_time ? $this->stop_time->format('H:i') : '',
            'user_id' => $this->user_id,
            'user_name' => $this->user->name ?? '',
            'active' => $this->active,
            'notes' => $this->notes,
            'tags' => TagResource::collection($this->tags),
        ];
    }
}
