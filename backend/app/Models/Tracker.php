<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tracker extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'id',
        'notes',
        'name',
        'type',
        'username',
        'password',
        'url',
        'is_archived',
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'is_archived' => 'boolean'
    ];

    public function campaigns()
    {
        return $this->hasMany(TrackerCampaign::class);
    }
    public function offerClicks()
    {
        return $this->hasMany(TrackerOfferClick::class);
    }

}
