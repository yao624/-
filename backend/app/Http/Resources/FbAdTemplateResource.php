<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAdTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            "id" => $this->id,
            "name" => $this->name,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "campaign_name" => $this->campaign_name,
            "adset_name" => $this->adset_name,
            "ad_name" => $this->ad_name,
            "bid_strategy" => $this->bid_strategy,
            "bid_amount" => $this->bid_amount,
            "budget_level" => $this->budget_level,
            "budget_type" => $this->budget_type,
            "budget" => $this->budget,
            "objective" => $this->objective,
            "accelerated" => $this->accelerated,
            "conversion_location" => $this->conversion_location,
            "optimization_goal" => $this->optimization_goal,
            "pixel_event" => $this->pixel_event,
            "advantage_plus_audience" => $this->advantage_plus_audience,
            "genders" => $this->genders,
            "age_min" => $this->age_min,
            "age_max" => $this->age_max,
//            "primary_text" => $this->primary_text,
//            "headline_text" => $this->headline_text,
//            "description_text" => $this->description_text,
            "countries_included" => $this->countries_included,
            "countries_excluded" => $this->countries_excluded,
            "regions_included" => $this->regions_included,
            "regions_excluded" => $this->regions_excluded,
            "cities_included" => $this->cities_included,
            "cities_excluded" => $this->cities_excluded,
            "locales" => $this->locales,
            "interests" => $this->interests,
            'publisher_platforms' => $this->publisher_platforms,
            'placement_mode' => $this->placement_mode,
            'facebook_positions' => $this->facebook_positions,
            'instagram_positions' => $this->instagram_positions,
            'messenger_positions' => $this->messenger_positions,
            'audience_network_positions' => $this->audience_network_positions,
            'device_platforms' => $this->device_platforms,
            'user_os' => $this->user_os,
            'wireless_carrier' => $this->wireless_carrier,
            'call_to_action' => $this->call_to_action,
            'url_params' => $this->url_params,

            "notes" => $this->notes,
            'is_owner' => $this->user_id === $user->id,
            "user" => optional($this->user)->only(['id', 'name', 'email']),
        ];
    }
}
