<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdLogPivotCampaign extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $table = 'adlog_campaign';

    protected $fillable = [
        'adlog_id',
        'campaign_source_id',
        'campaign_created',
        'campaign_failed_reason'
    ];
}
