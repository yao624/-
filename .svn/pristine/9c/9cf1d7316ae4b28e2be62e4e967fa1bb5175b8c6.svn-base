<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbAdInsight extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'account_currency',
        'account_id',
        'account_name',
        'actions',
        'action_values',
        'clicks',
        'cost_per_action_type',
        'cost_per_inline_link_click',
        'cpc',
        'cpm',
        'ctr',
        'date_start',
        'date_stop',
        'frequency',
        'impressions',
        'inline_link_click_ctr',
        'inline_link_clicks',
        'objective',
        'purchase_roas',
        'purchase_roas_value',
        'quality_ranking',
        'reach',
        'spend',
        'campaign_id',
        'campaign_name',
        'adset_id',
        'adset_name',
        'ad_id',
        'ad_name',
        'notes',
        'purchase',
        'purchase_value',
        'cost_per_purchase',
        'lead',
        'cost_per_lead',
        'add_to_cart',
        'cost_to_add_to_cart',
        'original_cost_per_purchase',
        'original_cost_per_lead',
        'original_cost_to_add_to_cart',
        'original_spend',
    ];

    protected $casts = [
        'actions' => 'array',
        'action_values' => 'array',
        'cost_per_action_type' => 'array',
        'purchase_roas' => 'array',
    ];
}
