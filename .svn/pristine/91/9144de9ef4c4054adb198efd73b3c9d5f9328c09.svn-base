<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbPagePostResourceSimple extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'primary_text' => $this->primary_text,
            'headline' => $this->headline,
            'description' => $this->description,
            'post_type' => $this->post_type,
            'url' => $this->url,
            'url_tags' => $this->url_tags,
        ];
    }
}
