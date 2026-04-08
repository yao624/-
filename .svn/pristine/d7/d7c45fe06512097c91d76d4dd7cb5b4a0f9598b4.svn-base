<?php

namespace App\Http\Resources;

use App\Models\FbBusinessUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbBusinessUserSimpleResource extends JsonResource
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
            'source_id' => $this->source_id,
            'name' => $this->name,
            'role' => $this->role,
        ];
    }
}
