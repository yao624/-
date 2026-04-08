<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cloudflare extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'email',
        'account_id',
        'api_token',
        'kv_namespace_id',
        'notes',
        'kv_pairs',
    ];

    protected $hidden = [
        'api_token',
        'kv_pairs'
    ];

    protected $casts = [
        'kv_pairs' => 'array'
    ];
}
