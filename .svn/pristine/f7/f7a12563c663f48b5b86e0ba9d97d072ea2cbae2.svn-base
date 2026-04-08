<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $type
 * @property array $value
 * @property array $actions
 * @property boolean $active
 * @property array $excluded_ads
 */
class FraudConfig extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'notes',
        'type',
        'value',
        'actions',
        'active',
        'excluded_ads',
    ];

    protected $casts = [
        'value' => 'array',
        'actions' => 'array',
        'active' => 'boolean',
        'excluded_ads' => 'array'
    ];
}
