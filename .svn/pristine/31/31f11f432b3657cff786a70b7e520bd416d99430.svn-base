<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $fillable = ['name', 'user_id'];

    public function networks()
    {
        return $this->morphedByMany(Network::class, 'taggable');
    }

    public function ad_accounts()
    {
        return $this->morphedByMany(FbAdAccount::class, 'taggable');
    }

    public function links()
    {
        return $this->morphedByMany(Link::class, 'taggable');
    }

    public function materials()
    {
        return $this->morphedByMany(Link::class, 'taggable');
    }

    public function fbAccounts()
    {
        return $this->morphedByMany(FbAccount::class, 'taggable');
    }

    public function fbAdAccounts()
    {
        return $this->morphedByMany(FbAdAccount::class, 'taggable');
    }

    public function fbCampaigns()
    {
        return $this->morphedByMany(FbCampaign::class, 'taggable');
    }

    public function cronJobs()
    {
        return $this->morphedByMany(CronJob::class, 'taggable');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
