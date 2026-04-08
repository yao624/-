<?php

namespace App\Jobs;

use App\Models\Click;
use App\Models\Network;
use App\Utils\Telegram;
use Exception;
use http\Env\Request;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class NetworkFetchClicks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $network_id;
    private $date_start;
    private $date_stop;
    private mixed $network_name;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct($network_id, $date_start, $date_stop)
    {
        $this->network_id = $network_id;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        $this->network_name = Network::query()->find($network_id)->name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("--- Start Fetch Network: {$this->network_name}  Clicks from {$this->date_start} to {$this->date_stop} ---");

        $network = Network::query()->find($this->network_id);

        $network_type = $network['system_type'];
        if ($network_type == 'Cake') {
            $this->fetchCakeClicks($network, $this->date_start, $this->date_stop);
        } elseif ($network_type == 'Everflow') {
            $this->fetchEverflowClicks($network, $this->date_start, $this->date_stop);
        } elseif ($network_type == 'Jumb') {
            $this->fetchJumbClicks($network, $this->date_start, $this->date_stop);
        } elseif ($network_type === 'Keitaro') {
            $this->fetchKeitaroClicks($network, $this->date_start, $this->date_stop);
        }else {
            Log::warning("network {$network['name']} {$network_type} not supported");
        }

        Log::info("--- End Fetch Network: {$this->network_name} Clicks from {$this->date_start} to {$this->date_stop} ---");

    }

    public function tags(): array
    {
        return [
            'Fetch-Clicks',
            $this->network_name,
            "{$this->date_start} - {$this->date_stop}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('NetworkFetchClicks Job failed: ' . $exception->getMessage());
        $msg = "Failed to fetch network clicks";
        Telegram::sendMessage($msg);
    }

    private function fetchCakeClicks($network, $date_start, $date_stop)
    {
        // 拼接  url
        // 获取，分页

        $endpoint = $network['endpoint'];
        $parsed_url = parse_url($endpoint);
        $base_url = "{$parsed_url['scheme']}://{$parsed_url['host']}";

        $api_key = $network['apikey'];
        $aff_id = $network['aff_id'];

        $req_url = "{$base_url}/affiliates/api/Reports/Clicks";
        $start_at_row = 1;
        $row_limit = 1500;
        $has_next = true;
        $params = [
            "start_date" => $this->date_start,
            "end_date" => $this->date_stop,
            "start_at_row" => $start_at_row,
            "row_limit" => $row_limit,
            "api_key" => $api_key,
            "affiliate_id" => $aff_id,
        ];

        while ($has_next) {
            Log::debug(" --- pull clicks for {$this->network_name}, start: {$this->date_start}, end: {$this->date_stop}, rows start from :{$params['start_at_row']} ---");
//            Log::debug($req_url);
//            Log::debug($params);
            $resp = Http::withHeaders([
                'Accept' => 'application/json'
            ])->timeout(3600)->connectTimeout(3600)->retry(3,1000)->get($req_url, $params);
            if ($resp->status() > 204) {
                Log::debug("status code: {$resp->status()}, body: {$resp->body()}");
                throw new Exception("get clicks failed ");
            }
            $all_data = collect($resp->json('data'));
            Log::debug("--- fetched {$all_data->count()} clicks");

            // 收集所有需要查询的tracking_id、network_name和offer_id, tracking_id 保存在数据里面为 transaction_id
            $identifiers = $all_data->map(function ($click) {
                return implode('-', [
                    $click['tracking_id'],
                    $this->network_id,
                    $click['offer']['offer_id'],
                ]);
            })->toArray();

            $existingIdentifiers = Click::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

            $new_data = [];
            foreach ($all_data as $click) {

                $transaction_id = $click['tracking_id'];
                $offer_source_id = $click['offer']['offer_id'];
                $offer_name = $click['offer']['offer_name'];
                $subid_1 = $click['subid_1'];
                $subid_2 = $click['subid_2'];
                $subid_3 = $click['subid_3'];
                $subid_4 = $click['subid_4'];
                $subid_5 = $click['subid_5'];
                $network_click_date = $click['click_date'];
                $ip_address = $click['ip_address'];

                $timezone = 'America/New_York'; // EDT 时间
                $click_date = Carbon::parse($network_click_date, $timezone)->setTimezone('UTC');

                $fb_campaign_source_id = '';
                $fb_adset_source_id = '';
                $fb_ad_source_id = '';

                try {
                    $s5 = explode('_', $subid_5);
                    $fb_campaign_source_id = $s5[0];
                    $fb_adset_source_id = $s5[1];
                    $fb_ad_source_id = $s5[2];
                } catch (\Exception $exception) {
                    Log::warning("cant extra fb campaign info from subid_5 {$subid_5}: {$exception->getMessage()}");
                }

                $identifier = implode('-', [
                    $transaction_id,
                    $this->network_id,
                    $offer_source_id,
                ]);

                // 如果在查询结果中找不到匹配的记录，就创建新的记录
                if (!in_array($identifier, $existingIdentifiers)) {
                    $new_data[] = [
                        'id' => Str::ulid(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'identifier' => $identifier,
                        'transaction_id' => $transaction_id,
                        'network_id' => $this->network_id,
                        'offer_source_id' => $offer_source_id,
                        'offer_source_name' => $offer_name,
                        'sub_1' => $subid_1,
                        'sub_2' => $subid_2,
                        'sub_3' => $subid_3,
                        'sub_4' => $subid_4,
                        'sub_5' => $subid_5,
                        'click_datetime' => $click_date,
                        'ip' => $ip_address,
                        'fb_campaign_source_id' => $fb_campaign_source_id,
                        'fb_adset_source_id' => $fb_adset_source_id,
                        'fb_ad_source_id' => $fb_ad_source_id,
                        'fb_pixel_number' => $subid_4,
                        'aff_id' => $aff_id,
                    ];
                }
            };
            try {
                $success = Click::query()->insert($new_data);
                Log::debug("insert success: {$success}, number: " . count($new_data));
            } catch (Exception $e) {
                Log::error("Failed to insert Click: " . $e->getMessage());
            }

            $counts = $all_data->count();
            $total_counts = $resp->json('row_count');
            $current_index = $start_at_row - 1 + $counts;
            Log::debug("--- total: {$total_counts}, current: $current_index");
            if ($current_index < $total_counts) {
                $has_next = true;
                $start_at_row = $current_index + 1;
                $params['start_at_row'] = $start_at_row;
                Log::debug("--- next page, current index: {$current_index}, next page start from {$params['start_at_row']}");
            } else {
                Log::debug("--- no next page");
                $has_next = false;
            }
        }
    }

    private function fetchEverflowClicks($network, $date_start, $date_stop)
    {
        // 它没有分页，且一次最多返回5000条，所以如果 Everflow 的点击每两天获取一次

        Log::debug("fetch everflow network: {$network['name']} clicks: {$date_start}, {$date_stop}");
        $endpoint = 'https://api.eflow.team/v1/affiliates/reporting/clicks';
        $api_key = $network['apikey'];
        $aff_id = $network['aff_id'];

        $payload = [
            "timezone_id" => 80,
                "from" => Carbon::createFromFormat('Y-m-d', $this->date_start)->startOfDay()->toDateTimeString(),
                "to" => Carbon::createFromFormat('Y-m-d', $this->date_stop)->endOfDay()->toDateTimeString(),
                "query" => [
                    "filters" => [],
                    "user_metrics" => [],
                    "exclusions" => [],
                    "metric_filters" => [],
                    "settings" => [
                        "campaign_data_only" => false,
                        "ignore_fail_traffic" => false,
                        "only_include_fail_traffic" => false
                    ]
              ]
        ];
//        Log::debug($payload);
        if ($network->name === 'Mint-Rancher') {
            $endpoint = 'https://api-eu.eflow.team/v1/affiliates/reporting/clicks';
//            $resp = Http::withHeaders([
//                'Accept' => 'application/json'
//            ])->withToken($api_key)->timeout(3600)->connectTimeout(3600)->post($endpoint, $payload);

            $resp = Http::withHeaders([
                'X-Eflow-API-Key' => $api_key,
                'Accept' => 'application/json'
            ])->timeout(3600)->connectTimeout(3600)->post($endpoint, $payload);
        } else {
            $resp = Http::withHeaders([
                'X-Eflow-API-Key' => $api_key,
                'Accept' => 'application/json'
            ])->timeout(3600)->connectTimeout(3600)->post($endpoint, $payload);
        }
        if ($resp->successful()) {
            $data = collect($resp->json('clicks'));

            $identifiers = $data->map(function ($click) {
                return implode('-', [
                    $click['transaction_id'],
                    $this->network_id,
                    $click['relationship']['offer']['network_offer_id'],
                ]);
            })->toArray();

            $existingIdentifiers = Click::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

            $new_data = [];
            foreach ($data as $click) {
                $transaction_id = $click['transaction_id'];
                $offer_source_id = $click['relationship']['offer']['network_offer_id'];
                $offer_name = $click['relationship']['offer']['name'];
                $subid_1 = $click['sub1'];
                $subid_2 = $click['sub2'];
                $subid_3 = $click['sub3'];
                $subid_4 = $click['sub4'];
                $subid_5 = $click['sub5'];
                $network_click_date = $click['unix_timestamp'];
                $ip_address = $click['user_ip'];

                $timezone = 'America/New_York'; // EDT 时间
                $click_date = Carbon::createFromTimestamp($network_click_date, $timezone)->setTimezone('UTC');

                $fb_campaign_source_id = '';
                $fb_adset_source_id = '';
                $fb_ad_source_id = '';
                $pixel_id = '';


//                try {
//                    $s5 = explode('_', $subid_5);
//                    $fb_campaign_source_id = $s5[0];
//                    $fb_adset_source_id = $s5[1];
//                    $fb_ad_source_id = $s5[2];
//                } catch (\Exception $exception) {
//                    Log::warning("cant extra fb campaign info from subid_5 {$subid_5}: {$exception->getMessage()}");
//                }

                // 尝试解析 sub3 字段
                try {
                    if (preg_match('/^\((.*?)\)(\d+)-(\d+)$/', $subid_3, $matches)) {
                        $fb_campaign_source_id = $matches[2];
                        $fb_adset_source_id = $matches[3];
                    } else {
                        try {
                            $s5 = explode('_', $subid_5);
                            $fb_campaign_source_id = $s5[0];
                            $fb_adset_source_id = $s5[1];
                            $fb_ad_source_id = $s5[2];
                        } catch (\Exception $exception) {
                            Log::warning("cant extra fb campaign info from subid_5 {$subid_5}: {$exception->getMessage()}");
                        }
                    }
                } catch (\Exception $exception) {
                    // 因为 sub3 解析失败，尝试解析 sub5
                    Log::warning("cant extra fb campaign info from subid_3 {$subid_3}: {$exception->getMessage()}");
                }

                // 检查 subid3 字段是否为长度至少为15的数字字符串
                if (preg_match('/^\d{15,}$/', $subid_3)) {
                    $pixel_id = $subid_3;
                }

                $identifier = implode('-', [
                    $transaction_id,
                    $this->network_id,
                    $offer_source_id,
                ]);

                // 如果在查询结果中找不到匹配的记录，就创建新的记录
                if (!in_array($identifier, $existingIdentifiers)) {
                    $new_data[] = [
                        'id' => Str::ulid(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'identifier' => $identifier,
                        'transaction_id' => $transaction_id,
                        'network_id' => $this->network_id,
                        'offer_source_id' => $offer_source_id,
                        'offer_source_name' => $offer_name,
                        'sub_1' => $subid_1,
                        'sub_2' => $subid_2,
                        'sub_3' => $subid_3,
                        'sub_4' => $subid_4,
                        'sub_5' => $subid_5,
                        'click_datetime' => $click_date,
                        'ip' => $ip_address,
                        'fb_campaign_source_id' => $fb_campaign_source_id,
                        'fb_adset_source_id' => $fb_adset_source_id,
                        'fb_ad_source_id' => $fb_ad_source_id,
                        'fb_pixel_number' => $pixel_id,
                        'aff_id' => $aff_id,
                    ];
                }
            };
            try {
                $success = Click::query()->insert($new_data);
                Log::debug("insert success: {$success}, number: " . count($new_data));
            } catch (Exception $e) {
                Log::error("Failed to insert Click: " . $e->getMessage());
            }
        } else {
            Log::error("request failed: status code: {$resp->status()}, body: {$resp->body()}");
        }

    }

    private function fetchJumbClicks($network, $date_start, $date_stop)
    {
        $base_url = 'https://api.jumbleberry.com/v2/org';
        $aff_id = $network['aff_id'];
        $api_key = $network['apikey'];

        $endpoint = "{$base_url}/{$aff_id}/reporting_summary";

        $params = [
            'dateRange' => 'CUSTOM',
            'startDate' => $date_start,
            'endDate' => $date_stop,
            'groupBy' => 'campaign_id,c1,c2,c3',
            'sortBy' => 'money',
            'order' => 'desc',
            'reportType' => 'subid',
            'page' => 1,
            'perPage' => 500,
            'type' => 'subid',
        ];

        $has_next = true;
        $total_write_count = 0;
        $total_read_count = 0;

//        Log::debug($endpoint);
//        Log::debug($params);
        while ($has_next) {
            $resp = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Api-Token' => $api_key,
                'X-Client' => 'jb-platform 7.2.0',
                'Accept' => 'application/json'
            ])->timeout(3600)->connectTimeout(3600)->get($endpoint, $params);
//            Log::debug($resp->json());
            if ($resp->successful()) {
                $data = collect($resp->json('data'));
                $total_read_count = $total_read_count + $data->count();

                $identifiers = $data->map(function ($click) use ($aff_id) {
                    return implode('-', [
                        $click['c2'],
                        $this->network_id,
                        $aff_id,
                    ]);
                })->toArray();
                $existingIdentifiers = Click::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

                $new_data = [];
                foreach ($data as $click) {

                    $transaction_id = '';
                    $offer_source_id = $click['campaign_id'];
                    $offer_name = $click['campaign_name'];
                    $subid_1 = $click['c1'] ?? '';
                    $subid_2 = $click['c2'] ?? '';
                    $subid_3 = $click['c3'] ?? '';

                    $fb_campaign_source_id = '';
                    $fb_adset_source_id = '';
                    $fb_ad_source_id = '';

                    try {
                        $s5 = explode('_', $subid_3);
                        $fb_campaign_source_id = $s5[0];
                        $fb_adset_source_id = $s5[1];
                        $fb_ad_source_id = $s5[2];
                    } catch (\Exception $exception) {
                        Log::warning("cant extra fb campaign info from subid_5 {$subid_3}: {$exception->getMessage()}");
                    }

                    $identifier = implode('-', [
                        $subid_2,
                        $this->network_id,
                        $aff_id,
                    ]);

                    if (!in_array($identifier, $existingIdentifiers)) {
                        $existingIdentifiers[] = $identifier;
                        $new_data[] = [
                            'id' => Str::ulid(),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'transaction_id' => $transaction_id,
                            'network_id' => $this->network_id,
                            'offer_source_id' => $offer_source_id,
                            'offer_source_name' => $offer_name,
                            'sub_1' => $subid_1,
                            'sub_2' => $subid_2,
                            'sub_3' => $subid_3,
                            'fb_campaign_source_id' => $fb_campaign_source_id,
                            'fb_adset_source_id' => $fb_adset_source_id,
                            'fb_ad_source_id' => $fb_ad_source_id,
                            'fb_pixel_number' => $subid_2,
                            'aff_id' => $aff_id,
                            'identifier' => $identifier
                        ];
                        $total_write_count = $total_write_count + 1;
                    }
                }

                try {
                    $success = Click::query()->insert($new_data);
                    Log::debug("insert success: {$success}, number: " . count($new_data));
                } catch (Exception $e) {
                    Log::error("Failed to insert Click: " . $e->getMessage());
                }

                $current_page = $resp->json('_meta.page');
                $total_page = $resp->json('_meta.page_count');
                if ($current_page < $total_page) {
                    $has_next = true;
                    $params['page'] = $params['page']+1;
                    Log::debug(" --- next page: {$params['page']} ---");
                } else {
                    $has_next = false;
                    Log::debug(" --- no next page ---");
                }
            } else {
                throw new Exception("Jumberry failed to pull data: {$resp->effectiveUri()}");
            }
        }
        Log::info("-- total read: {$total_read_count} total write:{$total_write_count}");

    }

    private function fetchKeitaroClicks($network, $date_start, $date_stop)
    {
        $this->keitaroUpdateNetowrk($network);
        $endpoint = rtrim($network['endpoint'], '/');;
        $path = '/admin/?object=clicks.log';
        $url = $endpoint . $path;

        $request = $this->getRequestObject($network);

        $date_start = Carbon::parse($date_start)->startOfDay()->format('Y-m-d H:i');
        $date_stop = Carbon::parse($date_stop)->endOfDay()->format('Y-m-d H:i');

        $pageSize = 100;
        $payload = [
            "range" => [
                "interval" => "custom_date_range",
                "timezone" => "America/New_York",
                "from" => $date_start,
                "to" => $date_stop,
            ],
            "columns" => [
                "sub_id",
                "datetime",
                "ip",
                "landing",
                "offer",
                "affiliate_network",
                "is_bot",
                "offer_id",
                "landing_clicked",
                "ad_campaign_id",
                "affiliate_network_id",
                "affiliate_network",
                "sub_id_1",
                "sub_id_2",
                "sub_id_3",
                "sub_id_4",
                "sub_id_5",
                "sub_id_6",
                "sub_id_7",
                "country",
                "country_flag",
                "campaign_id",
            ],
            "metrics" => [],
            "grouping" => [],
            "filters" => [
                ["name" => "is_bot", "operator" => "IS_FALSE", "expression" => null],
                ["name" => "landing_clicked", "operator" => "IS_TRUE", "expression" => null],
            ],
            "sort" => [["name" => "datetime", "order" => "desc"]],
            "summary" => true,
            "limit" => $pageSize,
            "offset" => 0,
        ];

        $has_next = true;
        while ($has_next) {
            $resp = $request->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Accept' => 'application/json'
            ])->timeout(3600)->connectTimeout(3600)->post($url,$payload);
//            Log::debug("click req body: " . json_encode($payload));
//            Log::debug("click resp: " . json_encode($resp->json()));
            $on_debug = Cache::get('fb_debug');
            if ($on_debug) {
                Log::debug("conv req url: ". json_encode($url));
                Log::debug("conv req body: ". json_encode($payload));
                Log::debug("conv resp:" . $resp->body());
            }
            if ($resp->successful()) {
                $data = collect($resp->json('rows'));

                $identifiers = $data->map(function ($click) use ($network) {
                    return implode('-', [
                        $click['sub_id'],
                        $network->id,
                    ]);
                })->toArray();
                $existingIdentifiers = Click::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

                                                // 对于指定网络（Mint、Clickstack、BW）数据，一次性查询所有可能重复的 sub_id（只检查 sub_2 字段）
                $targetNetworks = ['Mint', 'Clickstack', 'BW'];
                $targetSubIds = $data->filter(function ($click) use ($targetNetworks) {
                    return in_array($click['affiliate_network'], $targetNetworks);
                })->pluck('sub_id')->unique()->toArray();

                $existingTargetSubIds = [];
                if (!empty($targetSubIds)) {
                    $existingTargetSubIds = Click::whereIn('sub_2', $targetSubIds)
                        ->pluck('sub_2')
                        ->filter()
                        ->unique()
                        ->toArray();
                }

                $new_data = [];
                $subNetworks = Network::query()->where('apikey', 'like', "{$network['id']}%")->get();
                foreach ($data as $click) {
                    if (in_array($click['affiliate_network'], ['K2'])) {
                        continue;
                    }
                    if (str_contains($click['landing'], '@')) {
                        continue;
                    }

                                        // 对于 affiliate_network 为 "LA" 的数据，进行时间过滤
                    if ($click['affiliate_network'] === 'LA') {
                        $timezone = 'America/New_York';
                        $click_datetime = Carbon::parse($click['datetime'], $timezone)->setTimezone('UTC');
                        $cutoff_date = Carbon::create(2025, 6, 29, 0, 0, 0, 'UTC');

                        // 如果时间早于 2025年6月29日（UTC），跳过此条记录
                        if ($click_datetime->lt($cutoff_date)) {
                            continue;
                        }
                    }
                    $transaction_id = '';
                    $offer_source_id = $click['offer_id'];
                    $offer_name = $click['offer'];
//                    $subid_1 = '';
//                    $subid_2 = $click['sub_id'] ?? '';
//                    $subid_3 = $click['campaign_id'];
//                    $subid_4 = $click['sub_id_1']; // pixel
//                    $subid_5 = '';

                    $timezone = 'America/New_York'; // EDT 时间
                    $click_date = Carbon::parse($click['datetime'], $timezone)->setTimezone('UTC');

                    $subid_1 = '';
                    $subid_2 = '';
                    $subid_3 = '';
                    $subid_4 = '';
                    $subid_5 = '';
                    $fb_campaign_source_id = '';
                    $fb_adset_source_id = '';
                    $fb_ad_source_id = '';

                    $identifier = implode('-', [
                        $click['sub_id'],
                        $network['id'],
                    ]);

                                        // 对于指定网络（Mint、Clickstack、BW）的数据，检查 sub_id 是否已经存在
                    $shouldSkipTargetDuplicate = false;
                    if (in_array($click['affiliate_network'], $targetNetworks)) {
                        if (in_array($click['sub_id'], $existingTargetSubIds)) {
                            $shouldSkipTargetDuplicate = true;
                            Log::debug("Skipping {$click['affiliate_network']} click with existing sub_id: {$click['sub_id']}");
                        }
                    }

                    if (!in_array($identifier, $existingIdentifiers) && !$shouldSkipTargetDuplicate) {
                        $existingIdentifiers[] = $identifier;
                        $networkIdentifier = "{$network['id']}-{$click['affiliate_network_id']}";
//                        Log::debug("network identifier: $networkIdentifier");
//                        Log::debug($subNetworks->pluck('apikey'));
//                        Log::debug($subNetworks);
//                        Log::debug($click['sub_id']);

                        // 有可能 identifier 的 aff network id 为 0
                        $subNetwork = $subNetworks->where('active', true)->firstWhere('apikey', $networkIdentifier);
                        if (!$subNetwork) {
                            Log::debug("subid is ignored: {$click['sub_id']}");
                            continue;
                        }

                        $networkID = $subNetwork->id;
                        $mapping = $subNetwork->subidMapping;
                        if ($mapping) {
//                            Log::debug("mapping: {$mapping->name}");
                            $subid_1_key = $mapping['subid_1'];
                            $subid_2_key = $mapping['subid_2'];
                            $subid_3_key = $mapping['subid_3'];
                            $subid_4_key = $mapping['subid_4'];
                            $subid_5_key = $mapping['subid_5'];

                            $subid_1 = $click[$subid_1_key] ?? '';
                            $subid_2 = $click[$subid_2_key] ?? '';
                            $subid_3 = $click[$subid_3_key] ?? '';
                            $subid_4 = $click[$subid_4_key] ?? '';
                            $subid_5 = $click[$subid_5_key] ?? '';

                            if ($mapping['fb_campaign_id']) {
                                $fb_campaign_id_key = $mapping['fb_campaign_id'];
                                $fb_campaign_source_id = $click[$fb_campaign_id_key] ?? '';
                            }
                            if ($mapping['fb_adset_id']) {
                                $fb_adset_id_key = $mapping['fb_adset_id'];
                                $fb_adset_source_id = $click[$fb_adset_id_key] ?? '';
                            }
                            if ($mapping['fb_ad_id']) {
                                $fb_ad_id_key = $mapping['fb_ad_id'];
                                $fb_ad_source_id = $click[$fb_ad_id_key] ?? '';
                            }

                            // 如果 fb_campaign/adset/ad_id 为空，则默认从 ad_campaign_id 中获取
                            if (!$mapping['fb_campaign_id'] && !$mapping['fb_adset_id'] && $mapping['fb_ad_id']) {
                                $ad_campaign_id = $click['ad_campaign_id'];
                                try {
                                    $ad_campaign = explode('_', $ad_campaign_id);
                                    $fb_campaign_source_id = $ad_campaign[0];
                                    $fb_adset_source_id = $ad_campaign[1];
                                    $fb_ad_source_id = $ad_campaign[2];
                                } catch (\Exception $exception) {
                                    Log::warning("cant extra fb campaign info from ad_campaign {$ad_campaign_id}: {$exception->getMessage()}");
                                }
                            }
                        } else {
                            $subid_2 = $click['sub_id'] ?? '';
                            $subid_3 = $click['campaign_id'];
                            $subid_4 = $click['sub_id_1']; // pixel
                            $ad_campaign_id = $click['ad_campaign_id'];

                            try {
                                $ad_campaign = explode('_', $ad_campaign_id);
                                $fb_campaign_source_id = $ad_campaign[0];
                                $fb_adset_source_id = $ad_campaign[1];
                                $fb_ad_source_id = $ad_campaign[2];
                            } catch (\Exception $exception) {
                                Log::warning("cant extra fb campaign info from ad_campaign {$ad_campaign_id}: {$exception->getMessage()}");
                            }
                        }

                        $new_data[] = [
                            'id' => Str::ulid(),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'transaction_id' => $transaction_id,
                            'network_id' => $networkID,
                            'offer_source_id' => $offer_source_id,
                            'offer_source_name' => $offer_name,
                            'sub_1' => $subid_1,
                            'sub_2' => $subid_2,
                            'sub_3' => $subid_3,
                            'sub_4' => $subid_4,
                            'sub_5' => $subid_5,
                            'fb_campaign_source_id' => $fb_campaign_source_id,
                            'fb_adset_source_id' => $fb_adset_source_id,
                            'fb_ad_source_id' => $fb_ad_source_id,
                            'fb_pixel_number' => $subid_4,
                            'aff_id' => $network['aff_id'],
                            'identifier' => $identifier,
                            'country_code' => $click['country_flag'],
                            'click_datetime' => $click_date,
                        ];
                    }
                }

                try {
                    $success = Click::query()->insert($new_data);
                    Log::debug("insert success: {$success}, number: " . count($new_data));
                } catch (Exception $e) {
                    Log::error("Failed to insert Click: " . $e->getMessage());
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
                $message = "failed to fetch keitaro clicks: {$date_start} to {$date_stop}";
                Telegram::sendMessage($message);
                throw new Exception("failed to pull keitaro data: {$resp->effectiveUri()}");
            }
        }

//        $this->keitaroUpdateNetowrk($network);

    }

    private function keitaroUpdateNetowrk($network)
    {
        Log::debug("fet keitaro network");
        $endpoint = rtrim($network['endpoint'], '/');
        $path = '/admin/?object=affiliateNetworks.withStats';
        $url = $endpoint . $path;
        Log::debug($url);

        $request = $this->getRequestObject($network);

        $payload = [
            "range" => [
                "interval" => "today",
                "timezone" => "America/New_York"
            ],
            "columns" => [
                "checkbox",
                "id",
                "name"
            ],
            "metrics" => [
                "offers"
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
            "offset" => 0
        ];
        $resp = $request->withHeaders([
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        if ($resp->successful()) {
            $rows = collect($resp->json('rows'));
            foreach ($rows as $row) {
                if (in_array($row['name'], ['K2'])) {
                    continue;
                }
                Network::query()->updateOrCreate(
                    [
                        'apikey' => $network['id'] . '-' . $row['id']
                    ],
                    [
                    'user_id' => $network->user->id,
                    'name' => $network['name'] . '-' . $row['name'],
                    'system_type' => $network['system_type'],
                    'aff_id' => $network['aff_id'],
                    'endpoint' => $network['endpoint'],
                    'click_placeholder' => 'sub2',
                    'notes' => 'auto generated, do not active me',
                    'is_subnetwork' => true,
//                    'active' => false
                ]);
            }

        } else {
            $message = "获取 Keitaro Network 失败";
            Telegram::sendMessage($message);
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
                $success = $response->json('success', false);
                if (!$success) {
                    Log::debug($response->status());
                    Log::debug($response->body());
                    throw new Exception('failed to login keitaro');
                }
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
}
