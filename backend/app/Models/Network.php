<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Network extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;
    public $fillable = [
        'name',
        'system_type',
        'aff_id',
        'endpoint',
        'apikey',
        'active',
        'click_placeholder',
        'notes',
        'user_id',
        'subid_mapping_id',
        'is_subnetwork',
    ];

    protected $casts = [
        'is_subnetwork' => 'boolean'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function clicks()
    {
        return $this->hasMany(Click::class, 'network_id');
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class, 'network_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subidMapping()
    {
        return $this->belongsTo(SubidMapping::class, 'subid_mapping_id');
    }
}
