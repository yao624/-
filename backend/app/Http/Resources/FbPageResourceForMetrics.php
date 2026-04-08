<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FbPageResourceForMetrics extends JsonResource
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
            'picture' => $this->picture,
        ];
    }
}
