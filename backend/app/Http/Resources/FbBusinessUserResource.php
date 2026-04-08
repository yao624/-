<?php

namespace App\Http\Resources;

use App\Models\FbBusinessUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbBusinessUserResource extends JsonResource
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
            'source_id' => $this->source_id,
            'email' => $this->email,
            'finance_permission' => $this->finance_permission,
            'name' => $this->name,
            'role' => $this->role,
            'user_type' => $this->user_type,
            'is_operator' => $this->is_operator,
            'two_fac_status' => $this->two_fac_status,
            'notes' => $this->notes,
            'assigned_ad_accounts' => FbAdAccountResource3::collection($this->fbAdAccounts),
            'assigned_pages' => FbPageForBmUserResource::collection($this->fbPages),
            'assigned_catalogs' => FbCatalogForAssignedBusinessUserResource::collection($this->catalogs),
            'tags' => TagResource::collection($this->tags)
        ];
    }
}
