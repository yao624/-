<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FbPageForBmResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = DB::table('fb_account_page')
            ->where('fb_page_id', $this->id)
            ->get();

        return [
            'id' => $this->id,
            'source_id' => $this->source_id,
            'name' => $this->name,
            'fan_count' => $this->fan_count,
            'promotion_eligible' => boolval($this->promotion_eligible),
            'verification_status' => $this->verification_status,
            'picture' => $this->picture,
            'users' => $this->whenLoaded('fbAccounts', function () {
                return $this->fbAccounts->map(function ($user) {
                    return [
                        'source_id' => $user->source_id,
                        'fb_account_id' => $user->id,
                        'name' => $user->name,
                        'role_human' => $user->role_human,
                        'tasks' => json_decode($user->tasks, true),
                    ];
                });
            }),
            'bms' => FbBmResource2::collection($this->fbBms),
            'bm_system_users' => FbApiTokenResource2::collection($this->fbApiTokens),
            'users_count' => $this->fbAccounts->count(),
            'notes' => $this->notes,
            'tags' => TagResource::collection($this->tags),
            'is_owner' => boolval($this->pivot->is_owner),
            'role' => $this->pivot->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
