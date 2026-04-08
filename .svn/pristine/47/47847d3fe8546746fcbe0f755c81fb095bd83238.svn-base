<?php

namespace App\Http\Resources;

use App\Models\FbBm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 给 FbAdAccount Resource 用的
 */
class FbApiTokenResource2 extends JsonResource
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
            'active' => $this->active,
            'bm' => new FbBmForAdAccountListResource($this->fbBm),
            'token_type' => $this->token_type,
            'app' => $this->app,
        ];
    }
}
