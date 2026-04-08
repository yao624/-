<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FingerBrowser extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'created_time',
        'user_id',
        'serial_number',
        'group_id',
        'provider',
        'notes'
    ];

    public function fingerBroserGroup()
    {
        return $this->belongsTo(FingerBrowserGroup::class, 'group_id', 'group_id');
    }

    public function fbAccount()
    {
        return $this->hasOne(FbAccount::class, 'fingerbrowser_id');
    }
}
