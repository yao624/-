<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasFactory;

    public $keyType = 'string';
    public $incrementing = false;

    /**
     * 使用主数据库连接
     * Token 表存储在主数据库中，这样 Sanctum 可以在认证时读取 Token
     * 然后中间件从 Token 中获取 tenant_uuid 并设置租户连接
     */
    protected $connection = 'mysql'; // 使用主数据库连接

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'tenant_uuid', // 租户 UUID
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::ulid();
        });
    }

    /**
     * 获取租户 UUID
     *
     * @return string|null
     */
    public function getTenantUuidAttribute(): ?string
    {
        return $this->attributes['tenant_uuid'] ?? null;
    }
}
