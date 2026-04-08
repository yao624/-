<?php

namespace App\Jobs\FBComponents;

use App\Utils\FbUtils;
use Carbon\Carbon;
use Closure;

class CreateAd
{
    public function handle($context, Closure $next)
    {

        $fbAdAccountSourceId = $context['fbAdAccountSourceId'];
        $fbAccount = $context['fbAccount'];
        $apiToken = $context['apiToken'];

        $creativeId = $context['creativeId'] ?? $context['postId'];
        $adsetId = $context['adsetId'];
        $adName = $context['adName'];
        $payload = [
            'name' => $this->processName($adName, $fbAdAccountSourceId, $context['random']),
            'adset_id' => $adsetId,
            'creative' => json_encode([
                "creative_id" => $creativeId
            ]),
            'status' => $context['adStatus'] ?? 'PAUSED',
        ];
        if (! empty($context['trackingSpecsEncoded'])) {
            $payload['tracking_specs'] = $context['trackingSpecsEncoded'];
        }

        $version = FbUtils::$API_Version;
//        $version = 'v22.0';
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccountSourceId}/ads";
        $resp = FbUtils::makeRequest($fbAccount, $endpoint, null, 'POST', $payload, 'create_ad', $apiToken);

        // 获取 ad id,返回
        if ($resp['success']) {
            $context['adSourceId'] = $resp['id'];
        } else {
            throw new \Exception('Failed to create ad');
        }

        return $next($context);
    }

    function processName($name, $adAccountSourceId, $randomStr) {
        // 替换 {{datetime}} 宏
//        $name = str_replace('{{date}}', date('Y-m-d'), $name);
        // 获取当前时间并转换为 UTC+8 时区
        $currentDateTime = Carbon::now('UTC')->addHours(8);
        // 格式化日期时间为指定的格式
        $formattedDateTime = $currentDateTime->format('m/d-H:i:s');
        $name = str_replace('{{date}}', "($formattedDateTime)", $name);

        // 替换 {{random}} 宏
        if (strpos($name, '{{random}}') !== false ) {
            if ($randomStr) {
                $name = str_replace('{{random}}', $randomStr, $name);
            } else {
                $randomString = substr(str_shuffle(md5(time())), 0, 6);
                $name = str_replace('{{random}}', $randomString, $name);
            }
        }

        $adAccountSourceIdSub = substr($adAccountSourceId, -4);
        $name = str_replace('{{acc.id}}', $adAccountSourceIdSub, $name);

        return $name;
    }
}
