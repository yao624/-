<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string id
 * @property string source_id
 */
class FbAccount extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'source_id',
        'name',
        'first_name',
        'last_name',
        'username',
        'password',
        'gender',
        'picture',
        'twofa_key',
        'cookies',
        'token',
        'token_valid',
        'authorization_status',
        'authorized_by',
        'authorized_at',
        'authorization_fail_reason',
        'useragent',
        'fingerbrowser_id',
        'proxy_id',
        'is_archived',
        'notes',
        'auto_bind'
    ];

    protected $casts = [
        'token_valid' => 'boolean',
        'is_archived' => 'boolean',
        'auto_bind' => 'boolean',
        'authorized_at' => 'datetime',
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function proxy() {
        return $this->belongsTo(Proxy::class);
    }

    public function fingerbrowser() {
        return $this->belongsTo(FingerBrowser::class, 'fingerbrowser_id');
    }

    public function fbPages()
    {
        return $this->belongsToMany(FbPage::class, 'fb_account_page')
            ->using(CustomPivot::class)
            ->withPivot('tasks', 'role_human', 'is_active', 'source_id', 'source_name')
            ->withTimestamps();
    }

    public function fbBusinessUsers()
    {
        return $this->hasMany(FbBusinessUser::class);
    }

    public function fbBms()
    {
        return $this->hasManyThrough(
            FbBm::class,
            FbBusinessUser::class,
            'fb_account_id',
            'id',
            'id',
            'fb_bm_id'
        );
    }

    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class, 'fb_account_fb_ad_account')
            ->using(CustomPivot::class)
            ->withPivot('source_id', 'relation')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'fb_account_user', 'fb_account_id', 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function posts()
    {
        return $this->hasMany(FbPagePost::class, 'ad_account_source_id', 'source_id');
    }

    /**
     * ��ȡ�Ѱ��˻�����
     */
    public function scopeWithBindingCount($query)
    {
        return $query->withCount('fbAdAccounts as binding_count');
    }

    /**
     * ��Ȩ״̬������
     */
    public function scopeWithAuthorizationStatus($query, ?string $status = null)
    {
        if ($status) {
            return $query->where('authorization_status', $status);
        }
        return $query;
    }
}
