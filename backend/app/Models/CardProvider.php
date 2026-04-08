<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardProvider extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'nick_name',
        'config',
        'active',
        'notes'
    ];

    protected $casts = [
        'config' => 'array',
        'active' => 'boolean'
    ];

    // 与CardBin的关联
    public function cardBins()
    {
        return $this->hasMany(CardBin::class);
    }

    // 与Card的关联
    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    // 获取活跃的CardBin
    public function activeCardBins()
    {
        return $this->cardBins()->where('active', true);
    }

    // 搜索条件
    protected $searchAction = [
        'active' => 'in'
    ];
}
