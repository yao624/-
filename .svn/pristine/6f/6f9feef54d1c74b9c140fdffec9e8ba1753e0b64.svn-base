<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdLogPivotAd extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $table = 'adlog_ad';

    protected $fillable = [
        'adlog_id',
        'ad_source_id',
        'ad_created',
        'ad_failed_reason'
    ];
}
