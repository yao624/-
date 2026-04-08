<?php

namespace App\Http\Resources;

use App\Models\FbBm;
use App\Models\FbBusinessUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAccountResource extends JsonResource
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
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'gender' => $this->gender,
            'picture' => $this->picture,
            'twofa_key' => $this->twofa_key,
            'token_valid' => (boolean)$this->token_valid,
            'useragent' => $this->useragent,
            'notes' => $this->notes,
            // 授权相关字段
            'authorization_status' => $this->authorization_status,
            'authorized_by' => $this->authorized_by,
            'authorized_by_name' => $this->whenLoaded('authorizedBy', fn() => $this->authorizedBy?->name),
            'authorized_at' => $this->authorized_at?->format('Y-m-d H:i:s'),
            'authorization_fail_reason' => $this->authorization_fail_reason,
            // 绑定账户数量
            'binding_count' => $this->whenCounted('fbAdAccounts as binding_count') ?? ($this->fbAdAccounts_count ?? null),
            // 指纹浏览器
            'fingerbrowser_id' => $this->fingerbrowser_id,
            'proxy' => $this->whenLoaded('proxy', function () {
                return new ProxyResource($this->proxy);
            }),
            'ad_accounts' => FbAdAccountResource::collection($this->whenLoaded('fbAdAccounts')),
            'business_users'=> FbBusinessUserResource::collection($this->whenLoaded('fbBusinessUsers')),
            'bms' => FbBmResource::collection($this->whenLoaded('fbBms')),
            'pages' => FbPageResource::collection($this->whenLoaded('fbPages')),
            'tags' => TagResource::collection($this->tags),
//            'cookies' => $this->cookies
            'user_id' => $this->user_id ?? '',
            'system_user_name' => $this->whenLoaded('user', fn() => $this->user?->name),
        ];
    }
}
