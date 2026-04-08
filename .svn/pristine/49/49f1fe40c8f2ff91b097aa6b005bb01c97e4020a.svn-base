<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'source_id',
        'status',
        'balance',
        'number',
        'cvv',
        'expiration',
        'currency',
        'notes',
        'card_provider_id',
        'single_transaction_limit',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'balance' => 'decimal:2',
        'single_transaction_limit' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(CardTransaction::class);
    }

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // 与CardProvider的关联
    public function cardProvider()
    {
        return $this->belongsTo(CardProvider::class);
    }

    // 与FbAdAccount的多对多关联
    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class, 'card_fb_ad_account')
                    ->withPivot('is_default')
                    ->withTimestamps();
    }

    // 获取设置为默认的FbAdAccount
    public function defaultFbAdAccounts()
    {
        return $this->fbAdAccounts()->wherePivot('is_default', true);
    }

    protected $searchAction = [
        'status' => 'in',
        'number' => 'like',
    ];

    /**
     * 掩码显示卡号
     */
    public function getMaskedNumberAttribute()
    {
        if (!$this->number) {
            return null;
        }
        $showFirst = 4;
        $showLast = 4;
        $maskLength = strlen($this->number) - ($showFirst + $showLast);
        $mask = str_repeat('*', $maskLength);
        $firstPart = substr($this->number, 0, $showFirst);
        $lastPart = substr($this->number, -1 * $showLast);
        return $firstPart . $mask . $lastPart;
    }
}
