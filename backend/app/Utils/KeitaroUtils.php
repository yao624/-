<?php

namespace App\Utils;

use App\Models\TrackerCampaign;
use App\Models\TrackerOfferClick;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class KeitaroUtils
{

    public static function getRequestObject($tracker): PendingRequest
    {
        $parsedUrl = parse_url($tracker['url']);
        $protocol = $parsedUrl['scheme']; // 获取协议
        $domain = $parsedUrl['host']; // 获取域名
        $endpoint = $protocol . '://' . $domain;

        $path = '/admin/?object=auth.login';
        $payload = [
            'login' => $tracker['username'],
            'password' => $tracker['password'],
        ];

        $redisKey = 'tracker_ck' . '_' . $tracker['id'];

        // 尝试从 Redis 获取所有 cookies
        $cookies = Redis::get($redisKey);

        if ($cookies) {
            Log::debug("cookie 存在");
//            Log::debug(json_decode($cookies, true));
            $domain = parse_url($endpoint, PHP_URL_HOST);
            return Http::withCookies(json_decode($cookies, true), $domain);
        } else {
            // 如果 cookie 不存在，调用登录接口
            Log::debug("cookie 不存在，手动登录");
            $response = Http::post($endpoint . $path, $payload);
            if ($response->successful()) {
                Log::debug('登录成功: ' . $response->status());
                // 假设登录成功并从响应中获取所有 cookies
                $respCookies = $response->cookies()->toArray();
                // 将 Cookies 对象转换为数组
                $cookies = [];
                foreach ($respCookies as $cookie) {
                    $cookies[$cookie['Name']] = $cookie['Value'];
                }
                // Log::debug($cookies);
                Redis::set($redisKey, json_encode($cookies), 'EX', 60 * 60 * 24 * 10); // 设置过期时间为10天

                $domain = parse_url($endpoint, PHP_URL_HOST);
                return Http::withCookies($cookies, $domain);
            } else {
                Log::debug($response->status());
                Log::debug($response->body());
                throw new Exception('failed to login keitaro');
            }
        }
    }

    public static function FetchCampaign($tracker) {

        $parsedUrl = parse_url($tracker['url']);
        $protocol = $parsedUrl['scheme']; // 获取协议
        $domain = $parsedUrl['host']; // 获取域名
        $endpoint = $protocol . '://' . $domain;

        $path = 'admin/?object=campaigns.withStats';
        $req_url = "{$endpoint}/{$path}";
        $request = self::getRequestObject($tracker);

        Log::debug($req_url);

        $payload = [
            "range" => [
                "interval" => "today",
                "timezone" => "America/New_York"
            ],
            "columns" => [
                "state",
                "id",
                "name",
                "alias"
            ],
            "metrics" => [
                "ts",
                "lp_clicks"
            ],
            "grouping" => [
            ],
            "filters" => [
            ],
            "sort" => [
                [
                    "name" => "id",
                    "order" => "desc"
                ]
            ],
            "summary" => true,
            "limit" => 250,
            "offset" => 0,
            "extended" => true
        ];

        $resp = $request->withHeaders([
            'Content-Type' => 'application/json'
        ])->post($req_url, $payload);

        //TODO: 分页
        if ($resp->successful()) {
            $rows = collect($resp->json('rows'));
            Log::info("get campaigns: {$rows->count()}");
            foreach ($rows as $row) {
                TrackerCampaign::query()->updateOrCreate(
                    [
                        'tracker_id' => $tracker['id'],
                        'campaign_source_id' => $row['id'],
                    ],
                    [
                        'campaign_name' => $row['name'],
                        'alias' => $row['alias'],
                    ]);
            }
        } else {
            $message = "获取 Keitaro Campaign 失败";
            Telegram::sendMessage($message);
        }

    }

    public static function FetchOfferClicks($tracker, $date_start, $date_stop) {

        $parsedUrl = parse_url($tracker['url']);
        $protocol = $parsedUrl['scheme']; // 获取协议
        $domain = $parsedUrl['host']; // 获取域名
        $endpoint = $protocol . '://' . $domain;

        $path = 'admin/?object=clicks.log';
        $req_url = "{$endpoint}/{$path}";
        $request = self::getRequestObject($tracker);

        Log::debug($req_url);

        $date_start = Carbon::createFromFormat('Y-m-d H:i', $date_start . ' 00:00');
        $date_stop = Carbon::createFromFormat('Y-m-d H:i', $date_stop . ' 23:59');

        $pageSize = 100;
        $payload = [
            "range" => [
                "interval" => "custom_time_range",
                "timezone" => "America/New_York",
                "from" => $date_start,
                "to" => $date_stop,
            ],
            "columns" => [
                "ad_campaign_id",
                "sub_id",
                "datetime",
                "ip",
                "campaign_id",
                "landing",
                "offer",
                "affiliate_network",
                "country_flag",
                "region",
                "sub_id_1",
                "sub_id_2",
                "sub_id_3",
                "sub_id_4",
                "sub_id_5",
            ],
            "metrics" => [],
            "grouping" => [],
            "filters" => [
                [
                    "name" => "landing_clicked",
                    "operator" => "EQUALS",
                    "expression" => true,
                ],
            ],
            "sort" => [["name" => "datetime", "order" => "desc"]],
            "summary" => true,
            "limit" => $pageSize,
            "offset" => 0,
            "extended" => true,
        ];

        $has_next = true;
        while ($has_next) {
            $resp = $request->retry(3, 1000)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Accept' => 'application/json'
            ])->timeout(60)->connectTimeout(60)->post($req_url, $payload);
//            Log::debug($resp->json());
            if ($resp->successful()) {
                $data = collect($resp->json('rows'));
                $identifiers = $data->map(function ($click) use ($tracker) {
                    return implode('-', [
                        $click['sub_id'],
                        $tracker->id,
                    ]);
                })->toArray();
                $existingIdentifiers = TrackerOfferClick::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

                $campaign_ids = $data->pluck('campaign_id');
//                Log::debug("campaign ids: " . $campaign_ids->toJson());
                $tracker_campaigns = TrackerCampaign::query()
                    ->where('tracker_id', $tracker['id'])
                    ->whereIn('campaign_source_id', $campaign_ids)
                    ->select('id', 'campaign_source_id')
                    ->get()
                    ->keyBy('campaign_source_id') // 这样可以直接使用 campaign_source_id 作为键
                    ->toArray(); // 转换为普通数组
//                Log::debug($tracker_campaigns);

                $new_data = [];
                foreach ($data as $click) {

                    $tracker_id = $tracker['id'];
                    $tracker_campaign_id = $tracker_campaigns[$click['campaign_id']]['id'];
                    $campaign_source_id = $click['campaign_id'];
                    $subid = $click['sub_id'];
                    $ip = $click['ip'];
                    $sub_1 = $click['sub_id_1'];
                    $sub_2 = $click['sub_id_2'];
                    $sub_3 = $click['ad_campaign_id'];
                    $sub_4 = $click['sub_id_4'];
                    $sub_5 = $click['sub_id_5'];

                    $timezone = 'America/New_York'; // EDT 时间
                    $click_date = Carbon::parse($click['datetime'], $timezone)->setTimezone('UTC');

                    $offer = $click['offer'];
                    $landing = $click['landing'];
                    $country_flag = $click['country_flag'];
                    $network_identifier = $click['affiliate_network'];

                    $identifier = implode('-', [
                        $click['sub_id'],
                        $tracker['id'],
                    ]);

                    if (!in_array($identifier, $existingIdentifiers)) {
                        $existingIdentifiers[] = $identifier;

                        $new_data[] = [
                            'id' => Str::ulid(),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'tracker_id' => $tracker_id,
                            'tracker_campaign_id' => $tracker_campaign_id,
                            'campaign_source_id' => $campaign_source_id,
                            'subid' => $subid,
                            'ip' => $ip,
                            'sub_1' => $sub_1,
                            'sub_2' => $sub_2,
                            'sub_3' => $sub_3,
                            'sub_4' => $sub_4,
                            'sub_5' => $sub_5,
                            'click_date' => $click_date,
                            'offer' => $offer,
                            'landing' => $landing,
                            'country_flag' => $country_flag,
                            'network_identifier' => $network_identifier,
                            'identifier' => $identifier,
                        ];
                    }
                }

                try {
                    $success = TrackerOfferClick::query()->insert($new_data);
                    Log::debug("insert success: {$success}, number: " . count($new_data));
                } catch (Exception $e) {
                    Log::error("Failed to insert tracker offer click: " . $e->getMessage());
                }

                $total_count = $resp->json('total');
                $current_offset = $payload['offset'];
                $current_count = $data->count();
                Log::debug("total: $total_count");
                if ($current_offset + $current_count < $total_count) {
                    $has_next = true;
                    $payload['offset'] = $payload['offset'] + $pageSize;
                    Log::debug(" --- next page: {$payload['offset']} ---");
                } else {
                    $has_next = false;
                    Log::debug(" --- no next page ---");
                }
            } else {
                $message = "failed to fetch keitaro offer clicks: {$date_start} to {$date_stop}";
                Telegram::sendMessage($message);
                throw new Exception("failed to pull keitaro data: {$resp->effectiveUri()}");
            }
        }

    }

}
