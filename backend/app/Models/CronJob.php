<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CronJob extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'object_type',
        'object_value',
        'timezone',
        'start_time',
        'stop_time',
        'user_id',
        'active',
        'notes',
    ];

    protected $casts = [
        'object_value' => 'array',
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'active' => 'boolean'
    ];

    protected $searchAction = [
        'active' => '=',
        'object_type' => 'in'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
