<?php

namespace App\Jobs;

use App\Models\Cloudflare;
use App\Models\Network;
use App\Utils\Geos;
use App\Utils\Telegram;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SyncKeitaroLanderToKv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Log::debug("sync keitaro lander to kv");
        $network = Network::query()->firstWhere('name', 'Crypto 01');
        $endpoint = rtrim($network['endpoint'], '/');

        $result = collect();

        $request = $this->getRequestObject($network);

        // 获取 lander list, 根据 Group Name 查询国家的 代码
        $path = '/admin/?object=landings.withStats';
        $url = $endpoint . $path;
        Log::debug($url);
        $payload = [
            "range" => ["interval" => "today", "timezone" => "America/New_York"],
            "columns" => ["checkbox", "id", "name", "group"],
            "metrics" => [
                "profitability",
                "clicks",
                "conversions",
            ],
            "grouping" => [],
            "filters" => [],
            "sort" => [["name" => "conversions", "order" => "desc"]],
            "summary" => true,
            "limit" => 250,
            "offset" => 0,
            "extended" => true,
        ];
        $resp = $request->withHeaders([
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        if ($resp->successful()) {
            $rows = collect($resp->json('rows'));
            $geos = Geos::$GeoCodeList;
            foreach ($rows as $row) {
                $group = $row['group'];

                // 如果 Group 是 Geo 并且在 list 中
                if (in_array($group, $geos)) {
                    $landing_id = $row['id'];
                    Log::info("landing: {$row['id']}, {$row['name']}");
                    $landing_request = $endpoint . "/admin/?object=landings.show&id={$landing_id}";
                    Log::debug($landing_request);
                    $resp = $request->withHeaders([
                        'Content-Type' => 'application/json'
                    ])->connectTimeout(300)->timeout(300)->get($landing_request);
                    if ($resp->successful()) {
                        $folder = $resp->json('action_options.folder');
                        if (!is_null($folder)) {
                            $kv_key = "lander/{$folder}";
                            $key_value = $group;
                            $result->add([
                                'kv_key' => $kv_key,
                                'kv_value' => $key_value,
                                'kv_meta' => [
                                    'is_offer' => false,
                                    'is_lander' => true,
                                ]
                            ]);
                        }
                    } else {
                        Log::warning("获取 landing detail 失败: {$row['id']}");
                        Log::debug($resp->body());
                    }
                } else {
                    Log::debug("landing {$row['group']} 不在 Geo list 里面");
                }
            }
        } else {
            Log::warning("获取 landing list 失败");
        }

        // 获取 offer 的 list, 根据 offer 的 geo 获取国家的代码
        $payload = [
            "range" => ["interval" => "today", "timezone" => "America/New_York"],
            "columns" => ["checkbox", "id", "name", "group"],
            "metrics" => [
                "notes",
                "clicks",
            ],
            "grouping" => [],
            "filters" => [],
            "sort" => [["name" => "id", "order" => "desc"]],
            "summary" => true,
            "limit" => 250,
            "offset" => 0,
            "extended" => true,
        ];
        $path = '/admin/?object=offers.withStats';
        $url = $endpoint . $path;
        Log::debug($url);
        $resp = $request->withHeaders([
            'Content-Type' => 'application/json'
        ])->post($url, $payload);
        if ($resp->successful()) {
            $rows = collect($resp->json('rows'));
            foreach ($rows as $row) {
                $group = $row['group'];
                $geos = Geos::$GeoCodeList;

                // 如果 Group 是 Geo 并且在 list 中
                if (in_array($group, $geos)) {
                    $offer_id = $row['id'];
                    Log::info("offer: {$row['id']}, {$row['name']}");
                    $offer_request = $endpoint . "/admin/?object=offers.show&id={$offer_id}";
                    Log::debug($offer_request);
                    $resp = $request->withHeaders([
                        'Content-Type' => 'application/json'
                    ])->connectTimeout(300)->timeout(300)->get($offer_request);
                    if ($resp->successful()) {
                        $folder = $resp->json('action_options.folder');
                        if (!is_null($folder)) {
                            $kv_key = "lander/{$folder}";
                            $key_value = $group;
                            $result->add([
                                'kv_key' => $kv_key,
                                'kv_value' => $key_value,
                                'kv_meta' => [
                                    'is_offer' => true,
                                    'is_lander' => false,
                                ]
                            ]);
                        }
                    } else {
                        Log::warning("获取 offer detail 失败: {$row['name']}");
                        Log::debug($resp->body());
                    }
                } else {
                    Log::debug("offer {$row['group']} 不在 Geo list 里面");
                }
            }
        }

        // 往 Cloudflare 里面写 kv
        $kv_to_push = $result->toArray(); // 上一步获取到的 kv paris
        Log::debug("获取到数据:");
        Log::debug(count($kv_to_push));
        $cf_list = Cloudflare::query()->whereNotNull('api_token')->get();
        $push_datetime = Carbon::now()->toDateTimeLocalString();

        foreach ($cf_list as $cf) {
            // 获取已经存在的 kv
            Log::info("准备写入kv 到 {$cf->email}");
            $existed_kv = $cf->kv_pairs ?? []; // 防止为null

            // 过滤掉已经存在的 kv
            $filtered_kv_to_push = array_filter($kv_to_push, function ($kv) use ($existed_kv) {
                foreach ($existed_kv as $existing_kv) {
                    if ($kv['kv_key'] == $existing_kv['kv_key'] && $kv['kv_value'] == $existing_kv['kv_value']) {
                        return false;
                    }
                }
                return true;
            });

            $kv_package = [];
            foreach ($filtered_kv_to_push as $kv) {
                $kv_package[] = [
                    'base64' => false,
                    'key' => $kv['kv_key'],
                    'value' => $kv['kv_value'],
                    'metadata' => [
                        'datetime' => $push_datetime,
                        'is_offer' => $kv['kv_meta']['is_offer'],
                        'is_lander' => $kv['kv_meta']['is_lander'],
                    ]
                ];
            }

            if (count($kv_package) == 0) {
                // 如果没有待写入的 kv，继续下一个循环
                Log::debug("没有待写入到 kv 到 {$cf->email}");
                continue;
            }

            $account_id = $cf['account_id'];
            $email = $cf['email'];
            $kv_namespace_id = $cf['kv_namespace_id'];
            $api_token = $cf['api_token'];

            $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/storage/kv/namespaces/{$kv_namespace_id}/bulk";
            $resp = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Auth-Email' => $email,
                'Authorization' => "Bearer {$api_token}"
            ])->timeout(300)->connectTimeout(300)->put($url, $kv_package);

            // 如果成功，就把已经写入的 kv 与已经存在的 kv 合并保存在 cf 的 kv_pairs中
            if ($resp->successful()) {
                $write_cunt = count($kv_package);
                $msg = "成功写入 kv 到 {$account_id}: {$write_cunt} 条记录";
                Log::info($msg);
                Telegram::sendMessage($msg);
                Log::debug($resp->body());

                // 合并新写入的 kv 到已经存在的 kv
                $new_kv_pairs = array_merge($existed_kv, $filtered_kv_to_push);
                $cf->kv_pairs = $new_kv_pairs;
                $cf->save();

            } else {
                $message = "Failed to write kv pairs to cf: {$account_id}";
                Telegram::sendMessage($message);
                Log::warning($message);
                Log::warning($resp->body());
            }
        }

    }

    protected function getRequestObject($network): PendingRequest
    {
        $endpoint = rtrim($network['endpoint'], '/');
        $path = '/admin/?object=auth.login';
        $payload = [
            'login' => $network['aff_id'],
            'password' => $network['apikey'],
        ];

        $network_name_no_spaces = preg_replace('/\s+/', '', $network['name']);
        $redisKey = strtoupper($network_name_no_spaces) . '__' . $network['id'];

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

    public function failed(Throwable $exception)
    {
        Log::debug('同步 Keitaro 到 CF kv 失败: ' . $exception->getMessage());

        $msg = '同步 Keitaro 到 CF kv 失败';
        Telegram::sendMessage($msg);
    }

}
