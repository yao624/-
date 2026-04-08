<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestLogResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->short_user_agent,
            'full_user_agent' => $this->user_agent,
            'request_method' => $this->request_method,
            'request_path' => $this->request_path,
            'query_parameters' => $this->query_parameters,
            'request_body' => $this->request_body,
            'response_status' => $this->response_status,
            'response_time' => $this->response_time,
            'response_time_formatted' => $this->response_time ? $this->response_time . 'ms' : null,
            'requested_at' => $this->requested_at?->toISOString(),
            'requested_at_formatted' => $this->formatted_requested_at,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
