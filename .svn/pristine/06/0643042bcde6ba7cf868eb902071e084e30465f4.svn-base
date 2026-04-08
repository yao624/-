<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardTransaction extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'card_id',
        'status',
        'transaction_amount',
        'currency',
        'transaction_date',
        'transaction_type',
        'merchant_name',
        'custom_1',
        'source_id',
        'posted_date',
        'failure_reason',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'posted_date' => 'datetime',
        'transaction_amount' => 'decimal:2',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    protected $searchAction = [
        'status' => 'in',
        'transaction_type' => 'in'
    ];
}
