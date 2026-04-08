<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAutoRuleExecutionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'execution_time',
        'status',
        'conditions_matched',
        'actions_executed',
        'result',
    ];

    protected $casts = [
        'execution_time' => 'datetime',
        'conditions_matched' => 'array',
        'actions_executed' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MetaAutoRule::class, 'rule_id');
    }
}
