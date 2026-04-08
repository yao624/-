<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'request_method',
        'request_path',
        'query_parameters',
        'request_body',
        'response_status',
        'response_time',
        'requested_at',
    ];

    protected $casts = [
        'query_parameters' => 'array',
        'request_body' => 'array',
        'response_time' => 'integer',
        'requested_at' => 'datetime',
    ];

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取用户名称
     */
    public function getUserNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * 获取格式化的请求时间
     */
    public function getFormattedRequestedAtAttribute(): string
    {
        if (empty($this->requested_at)) {
            return '';
        }
        return $this->requested_at->format('Y-m-d H:i:s');
    }

    /**
     * 获取简化的用户代理
     */
    public function getShortUserAgentAttribute(): string
    {
        if (empty($this->user_agent)) {
            return '';
        }

        if (strlen($this->user_agent) > 100) {
            return substr($this->user_agent, 0, 100) . '...';
        }
        return $this->user_agent;
    }
}
