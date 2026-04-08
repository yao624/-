<?php

namespace App\Models;

use App\Jobs\ActionUpdateFbAdItemStauts;
use App\Utils\Telegram;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Rule extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'date_preset',
        'scope',
        'ad_account_ids',
        'relation',
        'conditions',
        'actions',
        'white_list',
        'is_active',
        'resource_ids',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'conditions' => 'array',
        'ad_account_ids' => 'array',
        'actions' => 'array',
        'white_list' => 'array',
        'is_active' => 'boolean',
        'relation' => 'boolean',
        'resource_ids' => 'array'
    ];

    public function fbAdAccounts()
    {
        return $this->morphedByMany(FbAdAccount::class, 'ruleable');
    }

    public function fbCampaigns()
    {
        return $this->morphedByMany(FbCampaign::class, 'ruleable');
    }

    public function fbAdsets()
    {
        return $this->morphedByMany(FbAdset::class, 'ruleable');
    }

    public function fbAds()
    {
        return $this->morphedByMany(FbAd::class, 'ruleable');
    }

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 检查条件是否满足
    /**
     * @param $conditionValue 实际的 Metric 值
     * @param $operator 比较符
     * @param $value 阈值
     * @return bool
     */
    protected function checkCondition($conditionValue, $operator, $value)
    {
        switch ($operator) {
            case '>':
                return $conditionValue > $value;
            case '<':
                return $conditionValue < $value;
            case '>=':
                return $conditionValue >= $value;
            case '<=':
                return $conditionValue <= $value;
            case '=':
                return $conditionValue == $value;
            default:
                return false;
        }
    }

    public function execute($metrics, $ad_account_name, $object_name, $object_id, $scope, $ad_account_id)
    {
        $ad_account_name = preg_replace('/[[:^print:]]/', '', $ad_account_name);
        $ad_account_name = Str::limit($ad_account_name);
        Log::debug("Execute Rule name: {$this->name},\r\n\t Object: {$ad_account_name}, {$object_name}, {$object_id}");
//        Log::debug("metrics:");
//        Log::debug($metrics);
        $passConditions = [];
        Log::debug("metrics:");
        Log::debug(json_encode($metrics));

        foreach ($this->conditions as $condition) {
            $metric = $condition['metric'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            $logic = $this->relation;
            // 获取 metric 的值
            $conditionValue = $metrics[$metric] ?? null;

            Log::debug("execute rule: {$metric}, {$operator}, {$value}, {$logic}");
            if ($this->checkCondition($conditionValue, $operator, $value)) {
                $passConditions[] = true;
                Log::debug("符合条件");
            } else {
                $passConditions[] = false;
                Log::debug("不符合条件");
            }
        }

        // 如果条件逻辑为 "或"，只要有一个条件满足就继续执行操作
        if (!$logic && in_array(true, $passConditions)) {
            Log::debug("发送告警");
            $this->performActions($ad_account_name, $object_name, $object_id, $scope, $ad_account_id);
            return true;
        }
        // 如果条件逻辑为 "与"，所有条件都满足才执行操作
        if ($logic && !in_array(false, $passConditions)) {
            Log::debug("发送告警");
            $this->performActions($ad_account_name, $object_name, $object_id, $scope, $ad_account_id);
            return true;
        }

        return false;
    }

    protected function performActions($ad_account_name, $object_name, $object_id, $scope, $ad_account_id)
    {
        $ad_account_name = preg_replace('/[[:^print:]]/', '', $ad_account_name);
        $ad_account_name = Str::limit($ad_account_name);
        Log::info("Perform Actions: {$ad_account_name}, {$object_name}, {$object_id}");
        $adAccount = FbAdAccount::where('source_id', $ad_account_id)->where('is_archived', false)->first();
        if (!$adAccount) {
            Log::warning("not found actived ad account: {$ad_account_name}");
            return;
        }
        if ($adAccount && $adAccount->disable_reason_code !== 0) {
            Log::info("Ad Account is disabled, skipping actions.");
            return; // Skip all actions if the ad account is disabled
        }

        Log::debug('total actions: ' . count($this->actions));
        Log::debug($this->actions);
        $names = [];
        foreach ($this->actions as $key => $action) {
            if (isset($action['name'])) {
                $names[] = $action['name'];
            }
        }
        foreach ($this->actions as $index => $action) {
            Log::debug("action: {$index}");
            Log::debug($action);
            $name = $action['name'];

            if (in_array($scope, ['adset', 'adset_tag']) && in_array($name, ['turn_off_adsets', 'tg_alert'])) {
                // 处理特殊情况，1，如果是关闭操作，则检查是否已经关闭了。2，tg_alert和其它一起，则检查其它是否要触发
                $adset = FbAdset::query()->firstWhere('source_id', $object_id);
                if ($adset) {
                    if ($name === 'turn_off_adsets') {
                        if ($adset->status === 'PAUSED') {
                            Log::debug("Action skipped as adset is already paused");
                            continue;
                        }
                    } elseif ($name === 'tg_alert') {
                        if (in_array('turn_off_adsets', $names)) {
                            if ($adset->status === 'PAUSED') {
                                Log::debug("Action skipped as adset is already paused");
                                continue;
                            }
                        }
                    }
                } else {
                    Log::info("adset not paused, continue: {$object_id} {$adset['status']}");
                }
            }

            if (in_array($scope, ['ad', 'ad_tag']) && in_array($name, [ 'turn_off_adss', 'tg_alert'])) {
                $ad = FbAd::query()->firstWhere('source_id', $object_id);
                if ($ad) {
                    if ($name === 'turn_off_adss') {
                        if ($ad->status === 'PAUSED') {
                            Log::debug("Action skipped as ad is already paused");
                            continue;
                        }
                    } elseif ($name === 'tg_alert') {
                        if (in_array('turn_off_adss', $names)) {
                            if ($ad->status === 'PAUSED') {
                                Log::debug("Action skipped as adset is already paused");
                                continue;
                            }
                        }
                    }
                }
            }

            if (in_array($scope, ['campaign', 'camp_tag']) && in_array($name, ['turn_off_campaigns', 'tg_alert'])) {
                $campaign = FbCampaign::query()->firstWhere('source_id', $object_id);
                if ($campaign) {
                    if ($name === 'turn_off_campaigns') {
                        if ($campaign->status === 'PAUSED') {
                            Log::debug("Action skipped as campaign is already paused");
                            continue;
                        }
                    } elseif ($name === 'tg_alert') {
                        if (in_array('turn_off_campaigns', $names)) {
                            if ($campaign->status === 'PAUSED') {
                                Log::debug("Action skipped as campaign is already paused");
                                continue;
                            }
                        }
                    }
                }
            }

            if ($scope == 'ad_account' && $name === 'turn_off_campaigns') {
                $campaigns = FbCampaign::where('ad_account_id', $object_id)->get();
                foreach ($campaigns as $campaign) {
                    if ($campaign->status !== 'PAUSED') {
                        ActionUpdateFbAdItemStauts::dispatch($campaign->id, 'PAUSED')->onQueue('facebook');
                    }
                }
                continue; // Skip further actions to avoid duplicate notifications
            }

            try {
                switch ($name) {
                    case 'tg_alert':
                        if ($scope !== 'ad_account') { // Avoid sending alerts if the scope is ad_account
                            $message = "{$this->name} \r\n 广告账号: {$ad_account_name} \r\n Scope: {$this->scope} \r\n object: {$object_name} \r\n object id: {$object_id}";
                            Telegram::sendMessage($message);
                        }
                        break;
                    case 'turn_off_adsets':
                        if (in_array($scope, ['adset', 'adset_tag'])) {
                            Log::debug("turn off adset 23333");
                            ActionUpdateFbAdItemStauts::dispatch($object_id, 'PAUSED', 'adset')->onQueue('facebook');
                        }
                        break;
                    case 'turn_off_adss':
                        if (in_array($scope, ['ad', 'ad_tag'])) {
                            // 如果是广告层级的
                            ActionUpdateFbAdItemStauts::dispatch($object_id, 'PAUSED', 'ad')->onQueue('facebook');
                        } else if ($scope === 'ad_account') {
                            Log::info("turn off all ads under this ad account");
                        }
                        break;
                    case 'turn_off_campaigns':
                        if (in_array($scope, ['campaign', 'camp_tag'])) { // This case is handled above for ad_account scope
                            ActionUpdateFbAdItemStauts::dispatch($object_id, 'PAUSED', 'campaign')->onQueue('facebook');
                        }
                        break;
                    default:
                        Log::info("No action performed for: {$name}");
                        break;
                }
            } catch (\Exception $e) {
                Log::debug("failed to execute action: {$index}");
            }

        }
    }
}
