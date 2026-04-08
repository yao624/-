<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class MaterialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $url = URL::temporarySignedRoute(
            'download', now()->addMinutes(20), ['id' => $this->id]
        );
        $user = $request->user();

        return [
            'id' => $this->id,
            'link' => $this->link,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'name' => $this->name,
            'filename' => $this->original_filename,
            'url' => $url,
            'notes' => $this->notes,
            'type' => $this->type,
            'is_owner' => $this->user_id === $user->id,
            'tags' => TagResource::collection($this->tags),

        ];
    }
}
