<?php

namespace App\Jobs;

use App\Models\Conversion;
use App\Models\Network;
use App\Utils\Telegram;
use Exception;
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

class NetworkFetchConversions implements ShouldQueue
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
        Log::info("--- Start Fetch Network: {$this->network_name}  Conversions from {$this->date_start} to {$this->date_stop} ---");

        $network = Network::query()->find($this->network_id);

        $network_type = $network['system_type'];
        if ($network_type == 'Cake') {
            $this->fetchCakeConversions($network, $this->date_start, $this->date_stop);
        } elseif ($network_type == 'Everflow') {
            $this->fetchEverflowConversions($network, $this->date_start, $this->date_stop);
        } elseif ($network_type == 'Jumb') {
            $this->fetchJumbConversions($network, $this->date_start, $this->date_stop);
        } elseif ($network_type === 'Keitaro') {
            $this->fetchKeitaroConversions($network, $this->date_start, $this->date_stop);
        }else {
            Log::warning("network {$network['name']} {$network_type} not supported");
        }

        Log::info("--- End Fetch Network: {$this->network_name} Conversions from {$this->date_start} to {$this->date_stop} ---");
    }

    public function tags(): array
    {
        return [
            'Fetch-Convs',
            $this->network_name,
            "{$this->date_start} - {$this->date_stop}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('NetworkFetchConversions Job failed: ' . $exception->getMessage());
        $msg = 'failed to fetch network conversion';
        Telegram::sendMessage($msg);
    }

    private function fetchCakeConversions($network, $date_start, $date_stop)
    {
        $endpoint = $network['endpoint'];
        $parsedUrl = parse_url($endpoint);
        $base_url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        $api_key = $network['apikey'];
        $aff_id = $network['aff_id'];

        $req_url = "$base_url/affiliates/api/Reports/Conversions";
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
            Log::debug(" --- pull convs for {$this->network_name}, start: {$this->date_start}, end: {$this->date_stop}, rows start from :{$params['start_at_row']} ---");
//            Log::debug($req_url);
//            Log::debug($params);
            $resp = Http::withHeaders([
                'Accept' => 'application/json'
            ])->timeout(3600)->connectTimeout(3600)->retry(3, 1000)->get($req_url, $params);
            if ($resp->status() > 204) {
                Log::debug("status code: {$resp->status()}, body: {$resp->body()}");
                throw new Exception("get convs failed ");
            }
            $all_data = collect($resp->json('data'));
            Log::debug("--- fetched {$all_data->count()} conversions");

            // 收集所有需要查询的tracking_id、network_name和offer_id, tracking_id 保存在数据里面为 transaction_id
            $identifiers = $all_data->map(function ($conversion) {
                return implode('-', [
                    $conversion['tracking_id'],
                    $this->network_id,
                    $conversion['offer_id'],
                    strval(intval($conversion['price']))
                ]);
            })->toArray();

            $existingConversions = Conversion::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

            $new_data = [];
            foreach ($all_data as $conversion){

                $transaction_id = $conversion['tracking_id'];
                $offer_source_id = $conversion['offer_id'];
                $offer_name = $conversion['offer_name'];
                $subid_1 = $conversion['subid_1'];
                $subid_2 = $conversion['subid_2'];
                $subid_3 = $conversion['subid_3'];
                $subid_4 = $conversion['subid_4'];
                $subid_5 = $conversion['subid_5'];
                $network_convert_date = $conversion['conversion_date'];
                $price = $conversion['price'];

                $timezone = 'America/New_York'; // EDT 时间
                $conversion_datetime = Carbon::parse($network_convert_date, $timezone)->setTimezone('UTC');

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
                    strval(intval($price))
                ]);

                // 如果在查询结果中找不到匹配的记录，就创建新的记录
                if (!in_array($identifier, $existingConversions)) {
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
                        'conversion_datetime' => $conversion_datetime,
                        'fb_campaign_source_id' => $fb_campaign_source_id,
                        'fb_adset_source_id' => $fb_adset_source_id,
                        'fb_ad_source_id' => $fb_ad_source_id,
                        'fb_pixel_number' => $subid_4,
                        'aff_id' => $aff_id,
                        'price' => floatval($price)
                    ];
                }
            };

            try {
                $success = Conversion::query()->insert($new_data);
                Log::debug("insert success: {$success}, number: " . count($new_data));
            } catch (Exception $e) {
                Log::error("Failed to insert Conversions: " . $e->getMessage());
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

    private function fetchEverflowConversions($network, $date_start, $date_stop)
    {
        // 有分页
        $endpoint = 'https://api.eflow.team/v1/affiliates/reporting/conversions';
        $api_key = $network['apikey'];
        $aff_id = $network['aff_id'];

        $page = 1;
        $page_size = 100;
        $has_next = true;
        $query = [
            'page' => $page,
            'page_size' => $page_size
        ];

        $payload = [
            "timezone_id" => 80,
            "from" => $this->date_start,
            "to" => $this->date_stop,
            "show_events" => true,
            "show_conversions" => true,
            "query" => [
                "filters" => [],
                "search_terms" => []
            ]
        ];

        $total_fetched = 0;
        while ($has_next) {

            if ($network->name === 'Mint-Rancher') {
                $endpoint = 'https://api-eu.eflow.team/v1/affiliates/reporting/conversions';
//                $resp = Http::withHeaders([
//                    'Accept' => 'application/json'
//                ])->withToken($api_key)->timeout(3600)->connectTimeout(3600)->retry(2, 1000)
//                    ->withQueryParameters($query)->post($endpoint, $payload);;

                $resp = Http::withHeaders([
                    'X-Eflow-API-Key' => $api_key,
                    'Accept' => 'application/json'
                ])->timeout(3600)->connectTimeout(3600)->retry(2, 1000)
                    ->withQueryParameters($query)->post($endpoint, $payload);
            } else {
                $resp = Http::withHeaders([
                    'X-Eflow-API-Key' => $api_key,
                    'Accept' => 'application/json'
                ])->timeout(3600)->connectTimeout(3600)->retry(2, 1000)
                    ->withQueryParameters($query)->post($endpoint, $payload);
            }

            if ($resp->successful()) {
                $data = collect($resp->json('conversions'));

                $total_fetched = $total_fetched + $data->count();
                $identifiers = $data->map(function ($conversion) {
                    return implode('-', [
                        $conversion['transaction_id'],
                        $this->network_id,
                        $conversion['relationship']['offer']['network_offer_id'],
                        floatval($conversion['revenue'])
                    ]);
                })->toArray();

                $existingConversions = Conversion::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

                $new_data = [];
                foreach ($data as $conversion){
                    $transaction_id = $conversion['transaction_id'];
                    $offer_source_id = $conversion['relationship']['offer']['network_offer_id'];
                    $offer_name = $conversion['relationship']['offer']['name'];
                    $subid_1 = $conversion['sub1'];
                    $subid_2 = $conversion['sub2'];
                    $subid_3 = $conversion['sub3'];
                    $subid_4 = $conversion['sub4'];
                    $subid_5 = $conversion['sub5'];
                    $network_conversion_date = $conversion['conversion_unix_timestamp'];
                    $ip_address = $conversion['conversion_user_ip'];
                    $price = $conversion['revenue'];

                    $timezone = 'America/New_York'; // EDT 时间
                    $conversion_date = Carbon::createFromTimestamp($network_conversion_date, $timezone)->setTimezone('UTC');

                    $fb_campaign_source_id = '';
                    $fb_adset_source_id = '';
                    $fb_ad_source_id = '';
                    $pixel_id = '';

//                    try {
//                        $s5 = explode('_', $subid_5);
//                        $fb_campaign_source_id = $s5[0];
//                        $fb_adset_source_id = $s5[1];
//                        $fb_ad_source_id = $s5[2];
//                    } catch (\Exception $exception) {
//                        Log::warning("cant extra fb campaign info from subid_5 {$subid_5}: {$exception->getMessage()}");
//                    }

                    // 尝试解析 sub3 字段
                    try {
                        if (preg_match('/^\((.*?)\)(\d+)-(\d+)$/', $subid_3, $matches)) {
                            $fb_campaign_source_id = $matches[2];
                            $fb_adset_source_id = $matches[3];
                        } else {
                            try {
                                Log::debug('解析s5');
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
                        $price
                    ]);

                    // 如果在查询结果中找不到匹配的记录，就创建新的记录
                    if (!in_array($identifier, $existingConversions)) {
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
                            'conversion_datetime' => $conversion_date,
                            'ip' => $ip_address,
                            'fb_campaign_source_id' => $fb_campaign_source_id,
                            'fb_adset_source_id' => $fb_adset_source_id,
                            'fb_ad_source_id' => $fb_ad_source_id,
                            'fb_pixel_number' => $pixel_id,
                            'aff_id' => $aff_id,
                            'price' => floatval($price)
                        ];
                    }
                };

                try {
                    $success = Conversion::query()->insert($new_data);
                    Log::debug("insert success: {$success}, number: " . count($new_data));
                } catch (Exception $e) {
                    Log::error("Failed to insert Conversions: " . $e->getMessage());
                }

                $total_count = $resp->json('total_count');
                if ($page * $page_size < $total_count) {
                    $has_next = true;
                    $page = $page + 1;
                    Log::info("--- fetch next page: {$page}");
                } else {
                    $has_next = false;
                    Log::info('--- no next page');
                }
            } else {
                Log::error("request failed: status code: {$resp->status()}, body: {$resp->body()}");
                $has_next = false;
            }
        }
        Log::info(" -- total fetched: {$total_fetched} ---");
    }

    private function fetchJumbConversions($network, $date_start, $date_stop)
    {
        $base_url = 'https://api.jumbleberry.com/v2/org';
        $aff_id = strval($network['aff_id']);
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
        $total_count = 0;
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

                // sales 为 1 的为转化
                $filtered = $data->filter(function ($item) {
                    return $item['sales'] == '1';
                });

                $identifiers = $filtered->map(function ($conversion) use ($aff_id) {
                    return implode('-', [
                        $conversion['c2'],
                        $this->network_id,
                        $aff_id,
                    ]);
                })->toArray();

                $existingIdentifiers = Conversion::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();


                $new_data = [];
                foreach ($filtered as $conversion){

                    $transaction_id = '';
                    $offer_source_id = $conversion['campaign_id'];
                    $offer_name = $conversion['campaign_name'];
                    $subid_1 = $conversion['c1'];
                    $subid_2 = $conversion['c2'];
                    $subid_3 = $conversion['c3'];
                    $price = $conversion['money'];

//                    $timezone = 'America/New_York'; // EDT 时间
//                    $click_date = Carbon::createFromTimestamp($network_click_date, $timezone)->setTimezone('UTC');

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
                            'fb_campaign_source_id' => $fb_campaign_source_id,
                            'fb_adset_source_id' => $fb_adset_source_id,
                            'fb_ad_source_id' => $fb_ad_source_id,
                            'fb_pixel_number' => $subid_2,
                            'aff_id' => $aff_id,
                            'price' => $price
                        ];
                        $total_count = $total_count + 1;
                    } else {
                        Log::debug("not write: {$identifier}");
                    }
                };

                try {
                    $success = Conversion::query()->insert($new_data);
                    Log::debug("insert success: {$success}, number: " . count($new_data));
                } catch (Exception $e) {
                    Log::error("Failed to insert Conversions: " . $e->getMessage());
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
//                $has_next = false;
            } else {
                throw new Exception("Jumberry failed to pull data: {$resp->effectiveUri()}");
            }
        }
        Log::info("-- get total {$total_count} data");
    }

    private function fetchKeitaroConversions(Network $network, $date_start, $date_stop)
    {

        $this->keitaroUpdateNetowrk($network);
        $networks = Network::query()->where('system_type', 'Keitaro')
            ->where('name', 'like', "{$network['name']}-%")
            ->get(['name', 'aff_id'])->mapWithKeys(function ($item) {
                return [$item['name'] => $item['aff_id']];
            });


        $endpoint = rtrim($network['endpoint'], '/');;
        $path = '/admin/?object=conversions.log';
        $url = $endpoint . $path;

        $request = $this->getRequestObject($network);

        $date_start = Carbon::parse($date_start)->startOfDay()->format('Y-m-d H:i');
        $date_stop = Carbon::parse($date_stop)->endOfDay()->format('Y-m-d H:i');
        Log::debug($date_start . $date_stop);
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
                "offer",
                "offer_id",
                "postback_datetime",
                "status",
                "revenue",
                "affiliate_network_id",
                "affiliate_network",
                "sub_id_1",
                "sub_id_2",
                "sub_id_3",
                "sub_id_4",
                "sub_id_5",
                "sub_id_6",
                "sub_id_7",
                "ad_campaign_id",
                "campaign_id",
                "country",
                "country_flag",
                "ip",
                "previous_status",
                "landing"
            ],
            "metrics" => [],
            "grouping" => [],
            "filters" => [],
            "sort" => [["name" => "postback_datetime", "order" => "desc"]],
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
            $on_debug = Cache::get('fb_debug');
            if ($on_debug) {
                Log::debug("conv req url: ". json_encode($url));
                Log::debug("conv req body: ". json_encode($payload));
                Log::debug("conv resp:" . $resp->body());
            }

            if ($resp->successful()) {
                $data = collect($resp->json('rows'));

                $identifiers = $data->map(function ($conversion) use ($network) {
                    return implode('-', [
                        $conversion['sub_id'],
                        $network->id,
                        $conversion['revenue']
                    ]);
                })->toArray();
                $existingIdentifiers = Conversion::whereIn('identifier', $identifiers)->pluck('identifier')->toArray();

                                                // 对于指定网络（Mint、Clickstack、BW）数据，一次性查询所有可能重复的 sub_id + price 组合（只检查 sub_2 字段）
                $targetNetworks = ['Mint', 'Clickstack', 'BW'];
                $targetSubIdPricePairs = $data->filter(function ($conversion) use ($targetNetworks) {
                    return in_array($conversion['affiliate_network'], $targetNetworks);
                })->map(function ($conversion) {
                    return [
                        'sub_id' => $conversion['sub_id'],
                        'price' => $conversion['revenue']
                    ];
                })->unique()->toArray();

                $existingTargetSubIdPrices = [];
                if (!empty($targetSubIdPricePairs)) {
                    // 提取所有的 sub_id 用于查询
                    $targetSubIds = collect($targetSubIdPricePairs)->pluck('sub_id')->unique()->toArray();

                    // 查询数据库中匹配的记录，获取 sub_2 + price 组合
                    $existingTargetSubIdPrices = Conversion::whereIn('sub_2', $targetSubIds)
                        ->get(['sub_2', 'price'])
                        ->map(function ($conversion) {
                            return $conversion->sub_2 . '|' . $conversion->price;
                        })
                        ->filter()
                        ->unique()
                        ->toArray();
                }

                $new_data = [];
                $subNetworks = Network::query()->where('apikey', 'like', "{$network['id']}%")->get();
                foreach ($data as $conversion) {
                    if (in_array($conversion['affiliate_network'], ['K2'])) {
                        continue;
                    }

                                        // 对于 affiliate_network 为 "LA" 的数据，进行时间过滤
                    if ($conversion['affiliate_network'] === 'LA') {
                        $timezone = 'America/New_York';
                        $conversion_datetime = Carbon::parse($conversion['postback_datetime'], $timezone)->setTimezone('UTC');
                        $cutoff_date = Carbon::create(2025, 6, 29, 0, 0, 0, 'UTC');

                        // 如果时间早于 2025年6月29日（UTC），跳过此条记录
                        if ($conversion_datetime->lt($cutoff_date)) {
                            continue;
                        }
                    }
                    $transaction_id = '';
                    $offer_source_id = $conversion['offer_id'];
                    $offer_name = $conversion['offer'] ?? $conversion['landing'] ?? '';
//                    $subid_1 = '';
//                    $subid_2 = $conversion['sub_id'] ?? '';
//                    $subid_3 = $conversion['campaign_id'];
//                    $subid_4 = $conversion['sub_id_1']; // pixel
//                    $subid_5 = '';
                    $price = $conversion['revenue'];

                    $timezone = 'America/New_York'; // EDT 时间
                    $conversion_date = Carbon::parse($conversion['postback_datetime'], $timezone)->setTimezone('UTC');

                    $fb_campaign_source_id = '';
                    $fb_adset_source_id = '';
                    $fb_ad_source_id = '';
                    $subid_1 = '';
                    $subid_2 = '';
                    $subid_3 = '';
                    $subid_4 = ''; // pixel
                    $subid_5 = '';


                    $identifier = implode('-', [
                        $conversion['sub_id'],
                        $network['id'],
                        $conversion['revenue'],
                    ]);

                                        // 对于指定网络（Mint、Clickstack、BW）的数据，检查 sub_id + price 组合是否已经存在
                    $shouldSkipTargetDuplicate = false;
                    if (in_array($conversion['affiliate_network'], $targetNetworks)) {
                        $currentSubIdPrice = $conversion['sub_id'] . '|' . $conversion['revenue'];
                        if (in_array($currentSubIdPrice, $existingTargetSubIdPrices)) {
                            $shouldSkipTargetDuplicate = true;
                            Log::debug("Skipping {$conversion['affiliate_network']} conversion with existing sub_id + price: {$conversion['sub_id']} (price: {$conversion['revenue']})");
                        }
                    }

                    if (!in_array($identifier, $existingIdentifiers) && !$shouldSkipTargetDuplicate) {
                        $existingIdentifiers[] = $identifier;
                        $original_aff_network_id = $conversion['affiliate_network_id'];
                        if ($original_aff_network_id != 0) {
                            $affiliate_network_id = $original_aff_network_id;
                        } else {
                            // 如果 affiliate network id 为 0，表示没有通过 offer 获取转化，而是直接在 prelander 上转化的
                            // 而 prelander 的名字后面有 @xxxx 是affiliate network 的 name, 因此先获取名字，再查询出对应的 network id
                            $trimmedStr = trim($conversion['landing']);
                            $lastAtPos = strrpos($trimmedStr, '@');
                            // 如果找到了 "@"，使用 substr 获取 "@" 后面的字符
                            if ($lastAtPos !== false) {
                                $network_name = substr($trimmedStr, $lastAtPos + 1); // +1 是为了跳过 "@" 本身
                                Log::debug("network name: {$network_name}");
                                // 这个 name 只是全称的 后面一段，全称需要加上 network['name']-
                                $affiliate_network_id = $networks->get("{$network['name']}-{$network_name}", '');
                            } else {
                                // 如果没有 "@" 符号，处理错误或者进行相应的操作
                                $affiliate_network_id = '';
                            }
                        }
                        $networkIdentifier = "{$network['id']}-$affiliate_network_id";
//                        Log::debug("network identifier: $networkIdentifier");
//                        Log::debug($subNetworks->pluck('apikey'));
//                        Log::debug($subNetworks);
//                        Log::debug($click['sub_id']);

                        // 有可能 identifier 的 aff network id 为 0
//                        Log::debug("subnetwork count:");
//                        Log::debug($subNetworks->count());
//                        Log::debug($subNetworks->toJson());
                        $subNetwork = $subNetworks->where('active', true)->firstWhere('apikey', $networkIdentifier);
                        if (!$subNetwork) {
                            Log::debug("subid is ignored: {$conversion['sub_id']}");
                            continue;
                        }
//                        Log::debug("subNetwork: {$subNetwork->name}");
                        $networkID = $subNetwork->id;
                        $mapping = $subNetwork->subidMapping;
                        if ($mapping) {
//                            Log::debug("mapping: {$mapping->name}");
                            $subid_1_key = $mapping['subid_1'];
                            $subid_2_key = $mapping['subid_2'];
                            $subid_3_key = $mapping['subid_3'];
                            $subid_4_key = $mapping['subid_4'];
                            $subid_5_key = $mapping['subid_5'];

                            $subid_1 = $conversion[$subid_1_key] ?? '';
                            $subid_2 = $conversion[$subid_2_key] ?? '';
                            $subid_3 = $conversion[$subid_3_key] ?? '';
                            $subid_4 = $conversion[$subid_4_key] ?? '';
                            $subid_5 = $conversion[$subid_5_key] ?? '';

                            if ($mapping['fb_campaign_id']) {
                                $fb_campaign_id_key = $mapping['fb_campaign_id'];
                                $fb_campaign_source_id = $conversion[$fb_campaign_id_key] ?? '';
                            }
                            if ($mapping['fb_adset_id']) {
                                $fb_adset_id_key = $mapping['fb_adset_id'];
                                $fb_adset_source_id = $conversion[$fb_adset_id_key] ?? '';
                            }
                            if ($mapping['fb_ad_id']) {
                                $fb_ad_id_key = $mapping['fb_ad_id'];
                                $fb_ad_source_id = $conversion[$fb_ad_id_key] ?? '';
                            }

                            // 如果 fb_campaign/adset/ad_id 为空，则默认从 ad_campaign_id 中获取
                            if (!$mapping['fb_campaign_id'] && !$mapping['fb_adset_id'] && $mapping['fb_ad_id']) {
                                $ad_campaign_id = $conversion['ad_campaign_id'];
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
                            $subid_2 = $conversion['sub_id'] ?? '';
                            $subid_3 = $conversion['campaign_id'];
                            $subid_4 = $conversion['sub_id_1']; // pixel
                            $subid_5 = '';
                            // 这里读取 mapping 的配置
                            $ad_campaign_id = $conversion['ad_campaign_id'];
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
                            'conversion_datetime' => $conversion_date,
                            'ip' => $conversion['ip'],
                            'fb_campaign_source_id' => $fb_campaign_source_id,
                            'fb_adset_source_id' => $fb_adset_source_id,
                            'fb_ad_source_id' => $fb_ad_source_id,
                            'fb_pixel_number' => $subid_4,
                            'aff_id' => $network['aff_id'],
                            'identifier' => $identifier,
                            'country_code' => $conversion['country_flag'],
                            'price' => $price
                        ];
                    }
                }

                try {
                    $success = Conversion::query()->insert($new_data);
                    Log::debug("insert success: {$success}, number: " . count($new_data));
                } catch (Exception $e) {
                    Log::error("Failed to insert Conversion: " . $e->getMessage());
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
                $message = "failed to fetch keitaro conversion: {$date_start} to {$date_stop}";
                Telegram::sendMessage($message);
                throw new Exception("failed to pull keitaro conversion: {$resp->effectiveUri()}");
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
            Log::debug(json_decode($cookies, true));
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
                    Log::debug("url: {$endpoint}{$path}");
                    Log::debug(json_encode($payload));
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
                Log::debug($cookies);
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

    private function keitaroUpdateNetowrk($network)
    {
        Log::debug("fetch keitaro network");
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
                        'aff_id' => $row['id'], // aff_id 来保存 keitaro 中的 network 的 id
                        'endpoint' => $network['endpoint'],
                        'click_placeholder' => 'sub2',
                        'notes' => 'auto generated, do not active me',
                        'is_subnetwork' => true,
//                        'active' => false
                    ]);
            }

        } else {
            $message = "获取 Keitaro Network 失败";
            Telegram::sendMessage($message);
        }

    }


}
