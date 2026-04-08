<?php

namespace App\Http\Resources;

use App\Models\FbBm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbApiTokenResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'name' => $this->name,
            'bm_id' => $this->bm_id,
            'token' => $this->token,
            'active' => $this->active,
            'notes' => $this->notes,
            'pages' => FbPageResource::collection($this->fbPages),
            'ad_accounts' => FbAdAccountResource::collection($this->adAccounts),
            'bm' => new FbBmResource2($this->fbBm),
            'app' => $this->app,
            'token_type' => $this->token_type,
        ];
    }
}
