<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proxy extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'protocol',
        'host',
        'port',
        'username',
        'password',
        'notes',
        'user_id'
    ];

    protected $searchAction = [
        'protocol' => '='
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fbAccounts()
    {
        return $this->hasMany(FbAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
