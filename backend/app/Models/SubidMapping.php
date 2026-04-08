<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubidMapping extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'subid_1',
        'subid_2',
        'subid_3',
        'subid_4',
        'subid_5',
        'fb_campaign_id',
        'fb_adset_id',
        'fb_ad_id',
    ];

    public function networks()
    {
        return $this->hasMany(Network::class, 'subid_mapping_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
