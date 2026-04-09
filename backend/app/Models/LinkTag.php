<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkTag extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'link_tags';

    protected $fillable = [
        'link_id',
        'user_id',
        'name',
    ];

    public function link()
    {
        return $this->belongsTo(Link::class, 'link_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
