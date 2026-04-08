<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string id
 * @property string source_id
 * @property string name
 */
class FbBusinessUser extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'email',
        'finance_permission',
        'name',
        'first_name',
        'last_name',
        'role',
        'two_fac_status',
        'expiry_time',
        'fb_bm_id',
        'user_type',
        'is_operator',
    ];

    protected $casts = [
      'is_operator' => 'boolean'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }
    public function fbAccount()
    {
        return $this->belongsTo(FbAccount::class);
    }

    public function fbBm()
    {
        return $this->belongsTo(FbBm::class);
    }

    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class)
            ->using(CustomPivot::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function fbPages()
    {
        return $this->belongsToMany(FbPage::class)
            ->using(CustomPivot::class)
            ->withPivot('role', 'tasks')
            ->withTimestamps();
    }

    public function catalogs()
    {
        return $this->belongsToMany(FbCatalog::class, 'fb_business_user_fb_catalog', 'fb_business_user_id', 'fb_catalog_id')
            ->using(CustomPivot::class)
            ->withPivot( ['role', 'tasks']) // 包含中间表的字段
            ->withTimestamps();
    }
}
