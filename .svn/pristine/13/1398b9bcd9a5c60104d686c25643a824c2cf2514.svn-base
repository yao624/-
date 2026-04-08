<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory, HasUlids;

    /**
     * 固定走 mysql_main：tenants 表始终在「主库」（DB_MAIN_DATABASE，未设则同 DB_DATABASE）。
     * 避免 .env 里 DB_DATABASE 指向业务库（如 laravel）时误查 laravel.tenants。
     */
    protected $connection = 'mysql_main';

    /**
     * 表名
     */
    protected $table = 'tenants';

    /**
     * 主键类型
     */
    public $incrementing = false;
    public $keyType = 'string';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'uuid',
        'email',
        'name',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'status',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'database_port' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 隐藏的属性（序列化时）
     */
    protected $hidden = [
        'database_password',
    ];

    /**
     * 根据邮箱查找租户
     *
     * @param string $email
     * @return Tenant|null
     */
    public static function findByEmail(string $email): ?self
    {
        return static::on('mysql_main')->where('email', $email)
            ->where('status', 'active')
            ->first();
    }

    /**
     * 根据 UUID 查找租户
     *
     * @param string $uuid
     * @return Tenant|null
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::on('mysql_main')->where('uuid', $uuid)
            ->where('status', 'active')
            ->first();
    }

    /**
     * 获取数据库连接配置数组
     *
     * @return array
     */
    public function getDatabaseConfig(): array
    {
        return [
            'driver' => 'mysql',
            'host' => $this->database_host,
            'port' => $this->database_port,
            'database' => $this->database_name,
            'username' => $this->database_username,
            'password' => $this->database_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ];
    }
}

