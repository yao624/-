<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FingerBrowserGroup extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'group_id',
        'group_name',
        'notes'
    ];

    public function fingerBrowsers()
    {
        return $this->hasMany(FingerBrowser::class, 'group_id', 'group_id');
    }
}
