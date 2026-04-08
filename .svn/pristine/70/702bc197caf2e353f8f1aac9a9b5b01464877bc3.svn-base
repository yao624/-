<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardBin extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'card_provider_id',
        'card_bin',
        'card_type',
        'active',
        'notes'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    // 与CardProvider的关联
    public function cardProvider()
    {
        return $this->belongsTo(CardProvider::class);
    }

    // 搜索条件
    protected $searchAction = [
        'active' => 'in',
        'card_type' => 'in'
    ];
}
