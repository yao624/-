<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FbApiToken
 *
 * @property string $name
 * @property string $notes
 * @property string $token
 * @property bool $active
 * @property int $bm_id
 * @property int $token_type
 * @property string|null $app
 *
 */
class FbApiToken extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'notes',
        'token',
        'active',
        'bm_id',
        'token_type',
        'app',
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    protected $searchAction = [
        'active' => '=',
        'name' => '=',
        'token' => '='
    ];

    public function adAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class, 'fb_api_token_fb_ad_account');
    }

    public function fbBm()
    {
        return $this->belongsTo(FbBm::class, 'bm_id', 'source_id');
    }

    public function fbPages()
    {
        return $this->belongsToMany(FbPage::class, 'fb_api_token_fb_page', 'fb_api_token_id', 'fb_page_id')
            ->using(CustomPivot::class)
            ->withPivot( 'tasks') // 包含中间表的字段
            ->withTimestamps();
    }

    /**
     * 获取关联的 FbApp
     * 通过 app 字段（存储 FbApp 的 source_id）进行关联
     */
    public function fbApp()
    {
        return $this->belongsTo(FbApp::class, 'app', 'source_id');
    }

}
