<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdLogPivotAdset extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $table = 'adlog_adset';

    protected $fillable = [
        'adlog_id',
        'adset_source_id',
        'adset_created',
        'adset_failed_reason'
    ];
}
