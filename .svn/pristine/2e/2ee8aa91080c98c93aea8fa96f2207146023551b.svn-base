<?php

namespace App\Services\CardProviders;

use App\Models\Card;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * 功能有限的Provider示例
 * 只支持基本的开卡和查询功能
 * 不支持冻结、取消等高级功能
 */
class AdposProvider extends BaseCardProvider
{
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->baseUrl = $config['base_url'] ?? 'https://api.adpos.io';
    }

    /**
     * 定义支持的功能（只支持基本功能）
     */
    protected function defineSupportedCapabilities(): array
    {
        return [
            // self::CAPABILITY_CREATE_CARD,        // 支持开卡
            self::CAPABILITY_SYNC_CARD,          // 支持同步卡片信息
            // self::CAPABILITY_GET_CARD_DETAILS,   // 支持获取卡片详情
            self::CAPABILITY_SYNC_TRANSACTIONS,  // 支持同步交易
            self::CAPABILITY_GET_ALL_CARDS,       // 获取所有卡片列表
            self::CAPABILITY_FREEZE_CARD,         // 支持冻结卡片
            self::CAPABILITY_UNFREEZE_CARD,       // 支持解冻卡片
            self::CAPABILITY_CANCEL_CARD,         // 支持取消卡片
            self::CAPABILITY_SET_BALANCE,         // 支持设置余额
            self::CAPABILITY_SET_PER_TRANSACTION_LIMIT, // 支持设置单笔限额
            // self::CAPABILITY_SET_TOTAL_LIMIT,     // 支持设置总限额
            // 不支持: freeze, unfreeze, cancel, limits, etc.
        ];
    }

    /**
     * 认证获取Token
     */
    public function getToken(): string
    {
        // 检查必需的配置项
        if (empty($this->config['username'])) {
            throw new Exception("Adpos username not configured. Please set config['username'] in CardProvider.");
        }

        if (empty($this->config['password'])) {
            throw new Exception("Adpos password not configured. Please set config['password'] in CardProvider.");
        }

        $token = Cache::remember('ADPOS_TOKEN', 1500, function () {
            $response = Http::post($this->baseUrl . '/auth/access-token', [
                'email' => $this->config['username'],
                'password' => $this->config['password']
            ]);

            if ($response->successful()) {
                return $response->json('data.access_token');
            }

            // 记录认证失败的详细信息
            Log::error("Adpos authentication failed", [
                'status' => $response->status(),
                'response' => $response->body(),
                'username' => $this->config['username']
            ]);

            return null;
        });

        if (!$token) {
            throw new Exception("Failed to authenticate with Adpos API. Please check your credentials and network connection.");
        }

        return $token;
    }

    /**
     * 创建卡片
     */
    public function createCard(string $cardName, float $balance, array $options = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/card/create', [
            'card_name' => $cardName,
            'initial_balance' => $balance
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to create card: " . $response->body());
        }

        $data = $response->json();

        return [
            'card_id' => $data['card_id'],
            'status' => 'ACTIVE',
            'balance' => $data['balance'],
            'number' => $data['card_number'],
            'cvv' => $data['cvv'],
            'expiration' => $data['expiry']
        ];
    }

    /**
     * 同步卡片信息
     */
    protected function doSyncCard(string $sourceId, bool $syncCvv = false): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . "/cards", [
            'card_id' => $sourceId
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to sync card: " . $response->body());
        }

        $data = $response->json();

        // 如果返回的是数组格式，取第一个元素
        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
            $cardData = $data['data'][0];
        } else {
            throw new Exception("Card not found with ID: {$sourceId}");
        }

        $result = [
            'name' => $cardData['name'] ?? 'Unknown',
            'status' => $this->mapAdposStatus($cardData['status'] ?? 'unknown'),
            'balance' => $cardData['available_balance'] ?? 0,
            'single_transaction_limit' => isset($cardData['single_transaction_limit']) ? floatval($cardData['single_transaction_limit']) : null,
            'created_at' => isset($cardData['applied_at']) ? Carbon::parse($cardData['applied_at'], 'Asia/Shanghai')->utc() : now(),
            'currency' => $cardData['currency'] ?? 'USD'
        ];

        $existingCard = Card::query()->where('source_id', $cardData['card_id'])->first();

        // 如果需要同步CVV信息
        if ($syncCvv && !empty($cardData['card_number'])) {
            $result['number'] = $cardData['card_number'];

            // 检查卡片状态，如果是CLOSED或INACTIVE状态，直接设置默认CVC
            if (in_array($result['status'], ['CLOSED', 'INACTIVE'])) {
                $result['cvv'] = '000';
                $result['expiration'] = '99/99';
            } else {
                // 如果 cvv 不存在，或者 cvv 存在，但是为000的话，重新拉取 cvv
                if (!$existingCard['cvv'] || ($existingCard['cvv'] && $existingCard['cvv'] == '000')) {
                    try {
                        $cvcData = $this->fetchCardCvcInternal($cardData['card_number']);
                        $result['cvv'] = $cvcData['cvv'] ?? null;
                        $result['expiration'] = $cvcData['expiration'] ?? null;
                    } catch (\Exception $e) {
                        Log::warning("获取卡片CVC失败", [
                            'card_id' => $sourceId,
                            'error' => $e->getMessage()
                        ]);
                        // CVC获取失败时不设置这些字段
                    }
                }

            }
        }

        return $result;
    }

    /**
     * 获取卡片详情
     */
    protected function doGetCardDetails(string $sourceId): array
    {
        Log::debug("get card detail, source id: {$sourceId}");
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . "/cards", [
            'card_id' => $sourceId
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to get card details: " . $response->body());
        }

        $data = $response->json();

        // 如果返回的是数组格式，取第一个元素
        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
            $cardData = $data['data'][0];
        } else {
            throw new Exception("Card not found with ID: {$sourceId}");
        }

        return [
            'card_id' => $cardData['card_id'],
            'card_number' => $cardData['card_number'] ?? '',
            'name' => $cardData['name'] ?? '',
            'currency' => $cardData['currency'] ?? 'USD',
            'last_four_digits' => $cardData['last_four_digits'] ?? '',
            'available_balance' => $cardData['available_balance'] ?? 0,
            'single_transaction_limit' => isset($cardData['single_transaction_limit']) ? floatval($cardData['single_transaction_limit']) : null,
            'status' => $this->mapAdposStatus($cardData['status'] ?? 'unknown'),
            'applied_at' => isset($cardData['applied_at']) ? $cardData['applied_at'] : null,
            'username' => $cardData['username'] ?? '',
            'suggested_card_user' => $cardData['suggested_card_user'] ?? '',
            'suggested_card_address' => $cardData['suggested_card_address'] ?? '',
            'suggested_card_zipcode' => $cardData['suggested_card_zipcode'] ?? '',
        ];
    }

    /**
     * 同步单张卡片交易
     */
    protected function doSyncTransactions(string $sourceId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . "/card/{$sourceId}/transactions");

        if (!$response->successful()) {
            throw new Exception("Failed to sync transactions");
        }

        $transactions = $response->json('transactions', []);

        return array_map(function($transaction) {
            return [
                'transaction_id' => $transaction['id'],
                'amount' => $transaction['amount'],
                'date' => Carbon::parse($transaction['date']),
                'type' => $transaction['type'],
                'status' => $transaction['status'],
                'merchant' => $transaction['merchant'] ?? 'Unknown'
            ];
        }, $transactions);
    }

    /**
     * 参数化同步交易记录
     *
     * @param array $params 包含以下可选参数：
     *  - start_time: 开始时间 (timestamp)
     *  - stop_time: 结束时间 (timestamp)
     *  - after: 分页参数
     *  - status: 交易状态 (approved, declined)
     *  - card_source_id: 特定卡片的 source_id
     */
    protected function doSyncTransactionsWithParams(array $params = []): array
    {
        // 检查时间范围是否超过30天限制
        if (isset($params['start_time']) && isset($params['stop_time'])) {
            $startTime = is_numeric($params['start_time'])
                ? (int) $params['start_time']
                : strtotime($params['start_time']);
            $stopTime = is_numeric($params['stop_time'])
                ? (int) $params['stop_time']
                : strtotime($params['stop_time']);

            $daysDiff = ($stopTime - $startTime) / 86400; // 86400 seconds = 1 day

            // 如果超过30天，需要拆分请求
            if ($daysDiff > 30) {
                return $this->syncTransactionsWithTimeRangeSplit($params, $startTime, $stopTime);
            }
        }

        // 如果没有超过30天限制，使用原有逻辑
        return $this->syncTransactionsSingleRequest($params);
    }

    /**
     * 拆分时间范围并合并结果
     */
    private function syncTransactionsWithTimeRangeSplit(array $params, int $startTime, int $stopTime): array
    {
        $allTransactions = [];
        $currentStart = $startTime;
        $daysPerChunk = 30; // 每次查询30天
        $secondsPerChunk = $daysPerChunk * 24 * 60 * 60;

        Log::info("Time range exceeds 30 days, splitting into chunks", [
            'original_start' => date('Y-m-d H:i:s', $startTime),
            'original_stop' => date('Y-m-d H:i:s', $stopTime),
            'total_days' => ($stopTime - $startTime) / 86400,
            'chunks_needed' => ceil(($stopTime - $startTime) / $secondsPerChunk)
        ]);

        $chunkIndex = 1;

        while ($currentStart < $stopTime) {
            $currentStop = min($currentStart + $secondsPerChunk - 1, $stopTime);

            Log::info("Processing time chunk {$chunkIndex}", [
                'chunk_start' => date('Y-m-d H:i:s', $currentStart),
                'chunk_stop' => date('Y-m-d H:i:s', $currentStop),
                'chunk_days' => ($currentStop - $currentStart) / 86400
            ]);

            // 创建当前时间段的参数
            $chunkParams = $params;
            $chunkParams['start_time'] = $currentStart;
            $chunkParams['stop_time'] = $currentStop;

            // 处理当前时间段的所有分页数据
            $chunkTransactions = $this->syncTimeChunkAllPages($chunkParams);
            $allTransactions = array_merge($allTransactions, $chunkTransactions);

            Log::info("Chunk {$chunkIndex} completed", [
                'transactions_fetched' => count($chunkTransactions),
                'total_so_far' => count($allTransactions)
            ]);

            // 移动到下一个时间段
            $currentStart = $currentStop + 1;
            $chunkIndex++;

            // 在时间段之间添加短暂延迟，避免API频率限制
            if ($currentStart < $stopTime) {
                Log::debug("Pausing 2 seconds before next chunk...");
                sleep(2);
            }
        }

        Log::info("Time range split completed", [
            'total_chunks_processed' => $chunkIndex - 1,
            'total_transactions' => count($allTransactions)
        ]);

        // 拆分请求的情况下，不返回分页信息，因为所有数据都已获取
        return [
            'transactions' => $allTransactions,
            'has_more' => false,
            'next_page' => null
        ];
    }

    /**
     * 获取单个时间段的所有分页数据
     */
    private function syncTimeChunkAllPages(array $params): array
    {
        $allTransactions = [];
        $currentPage = 1;
        $hasMore = true;

        while ($hasMore) {
            $pageParams = $params;
            if ($currentPage > 1) {
                $pageParams['after'] = $currentPage;
            }

            $result = $this->syncTransactionsSingleRequest($pageParams);
            $allTransactions = array_merge($allTransactions, $result['transactions']);

            $hasMore = $result['has_more'];
            if ($hasMore) {
                $currentPage = $result['next_page'];

                // 分页之间短暂延迟
                sleep(1);
            }
        }

        return $allTransactions;
    }

    /**
     * 单次API请求（原有逻辑）
     */
    private function syncTransactionsSingleRequest(array $params): array
    {
        $requestParams = [];
        $requestParams['per_page'] = 200;

        // 处理时间参数 - Adpos API 需要 start_timestamp 和 end_timestamp (integer)
        if (isset($params['start_time'])) {
            $requestParams['start_timestamp'] = is_numeric($params['start_time'])
                ? (int) $params['start_time']
                : strtotime($params['start_time']);
        }
        if (isset($params['stop_time'])) {
            $requestParams['end_timestamp'] = is_numeric($params['stop_time'])
                ? (int) $params['stop_time']
                : strtotime($params['stop_time']);
        }

        // 处理分页参数
        if (isset($params['after'])) {
            $requestParams['page'] = $params['after'];
        }

        // 处理状态过滤
        if (isset($params['status'])) {
            $requestParams['status'] = $params['status'];
        }

        // 处理特定卡片过滤 - Adpos API 使用 card_number 参数
        if (isset($params['card_source_id'])) {
            // 通过 source_id 查找对应的卡号
            $card = Card::where('source_id', $params['card_source_id'])->first();
            if ($card && $card->number) {
                $requestParams['card_number'] = $card->number;
            }
        }

        Log::debug("Single API request to Adpos", [
            'request_params' => $requestParams,
            'original_params' => $params
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . '/v2/cards/transactions', $requestParams);

        if (!$response->successful()) {
            Log::error("Failed to sync transactions", [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_params' => $requestParams
            ]);
            throw new Exception("Failed to sync transactions: " . $response->body());
        }

        $responseData = $response->json();
        $transactions = $responseData['data'] ?? [];
        $pagination = $responseData['meta']['pagination'] ?? null;
        Log::debug("Page meta", [
            'meta' => $responseData['meta']
        ]);

        // 映射交易数据到标准格式
        $mappedTransactions = array_map(function($transaction) {
            return $this->mapAdposTransaction($transaction);
        }, $transactions);

        // 处理分页信息
        $hasMore = false;
        $nextPage = null;

        if ($pagination) {
            $currentPage = $pagination['current_page'] ?? 1;
            $totalPages = $pagination['total_pages'] ?? 1;
            $hasMore = $currentPage < $totalPages;
            $nextPage = $hasMore ? ($currentPage + 1) : null;
        }

        return [
            'transactions' => $mappedTransactions,
            'has_more' => $hasMore,
            'next_page' => $nextPage
        ];
    }

    /**
     * 映射Adpos交易数据到标准格式
     */
    private function mapAdposTransaction(array $transaction): array
    {
        return [
            'source_id' => $transaction['id'],
            'card_number' => $transaction['card_number'] ?? null, // 使用card_number而不是card_id
            'status' => $this->mapAdposTransactionStatus($transaction['status'] ?? 'unknown'),
            'transaction_amount' => isset($transaction['transaction_amount']) ?
                round(floatval($transaction['transaction_amount']), 2) : 0.00,
            'currency' => $transaction['billing_currency'] ?? null,
            'transaction_date' => isset($transaction['transaction_unix_timestamp']) ?
                \Carbon\Carbon::createFromTimestamp($transaction['transaction_unix_timestamp']) : null,
            'transaction_type' => $transaction['transaction_type'] ?? 'unknown',
            'merchant_name' => $transaction['merchant_name'] ?? 'Unknown Merchant',
            'custom_1' => $transaction['billing_status'] ?? null, // billing_status 映射到 custom_1
            'posted_date' => $this->calculatePostedDate($transaction),
            'failure_reason' => $transaction['fail_reason'] ?? null,
            'notes' => $transaction['notes'] ?? null,
        ];
    }

    /**
     * 映射Adpos交易状态
     */
    private function mapAdposTransactionStatus(string $status): string
    {
        $statusMap = [
            'approved' => 'approved',
            'declined' => 'declined',
            'pending' => 'pending',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[strtolower($status)] ?? $status;
    }

    /**
     * 计算交易完成时间（posted_date）
     */
    private function calculatePostedDate(array $transaction): ?\Carbon\Carbon
    {
        $billingStatus = $transaction['billing_status'] ?? null;
        $transactionDate = isset($transaction['transaction_unix_timestamp']) ?
            \Carbon\Carbon::createFromTimestamp($transaction['transaction_unix_timestamp']) : null;

        // 如果 billing_status 为 declined，posted_date 设置为 transaction_date
        if ($billingStatus === 'declined' && $transactionDate) {
            return $transactionDate;
        }

        // 如果 billing_status 从 pending 变为其他状态，表示交易完成
        // 这里我们暂时返回null，因为API没有提供确切的完成时间
        if ($billingStatus && $billingStatus !== 'pending') {
            // 可能需要根据实际API响应调整
            return $transactionDate;
        }

        return null;
    }

        /**
     * 内部方法：获取单张卡片的CVC信息
     */
    private function fetchCardCvcInternal(string $cardNumber): array
    {
        $requestData = [
            'card_number' => $cardNumber,
            'trade_password' => config('services.adpos.trade_password', '')
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->timeout(30)->post($this->baseUrl . '/cards/cvc', $requestData);

        if (!$response->successful()) {
            throw new Exception("Failed to get card CVC: " . $response->body());
        }

        $data = $response->json();

        if (!isset($data['data']['cvc'])) {
            throw new Exception("CVC information not found in response");
        }

        return [
            'cvv' => $data['data']['cvc'],
            'expiration' => $data['data']['expiry'] ?? null
        ];
    }

    /**
     * 获取所有卡片列表（支持分页）
     */
    protected function doGetAllCards(array $options = []): array
    {
        $syncCvc = $options['sync_cvc'] ?? false;
        $allCards = [];
        $currentPage = 1;
        $perPage = 100;
        $totalPages = null;

        Log::info("开始获取所有卡片数据（分页模式）", [
            'provider' => 'Adpos',
            'sync_cvc' => $syncCvc
        ]);

        do {
            Log::info("获取卡片数据", [
                'current_page' => $currentPage,
                'total_pages' => $totalPages ?? '未知'
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getToken()
            ])->get($this->baseUrl . "/cards", [
                'page' => $currentPage,
                'per_page' => $perPage
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to get cards page {$currentPage}: " . $response->body());
            }

            $responseData = $response->json();
            $pageCards = $responseData['data'] ?? [];
            $pagination = $responseData['meta']['pagination'] ?? null;

            if ($pagination) {
                $totalPages = $pagination['total_pages'];
                $totalCards = $pagination['total'];
                $currentCount = $pagination['count'];

                Log::info("分页信息", [
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages,
                    'current_count' => $currentCount,
                    'total_cards' => $totalCards,
                    'per_page' => $pagination['per_page'] ?? 'unknown'
                ]);
            } else {
                Log::warning("未找到分页信息，可能是最后一页或API结构变更");
            }

            // 将当前页的卡片添加到总列表中
            $allCards = array_merge($allCards, $pageCards);

            Log::info("已获取卡片数据", [
                'current_page_cards' => count($pageCards),
                'total_cards_so_far' => count($allCards)
            ]);

            $currentPage++;

            // 如果不是最后一页，休眠20秒
            if ($pagination && $currentPage <= $totalPages) {
                Log::info("等待5秒后获取下一页...", [
                    'next_page' => $currentPage,
                    'remaining_pages' => $totalPages - $currentPage + 1
                ]);
                sleep(5);
            }

        } while ($pagination && $currentPage <= $totalPages);

        Log::info("完成所有卡片数据获取", [
            'total_pages_fetched' => $currentPage - 1,
            'total_cards_retrieved' => count($allCards)
        ]);

        $data = $allCards;

        return array_map(function($card) use ($syncCvc) {
            $cardData = [
                'card_id' => $card['card_id'],
                'card_number' => $card['card_number'] ?? '',
                'name' => $card['name'] ?? '',  // alias
                'currency' => $card['currency'] ?? 'USD',
                'last_four_digits' => $card['last_four_digits'] ?? '',
                'available_balance' => $card['available_balance'] ?? 0,
                'single_transaction_limit' => isset($card['single_transaction_limit']) ? floatval($card['single_transaction_limit']) : null,
                'status' => $this->mapAdposStatus($card['status'] ?? 'unknown'),
                'applied_at' => isset($card['applied_at']) ? $card['applied_at'] : null,
                'username' => $card['username'] ?? '',
                'suggested_card_user' => $card['suggested_card_user'] ?? '',
                'suggested_card_address' => $card['suggested_card_address'] ?? '',
                'suggested_card_zipcode' => $card['suggested_card_zipcode'] ?? '',
            ];

            // 如果需要同步CVC且有卡号，则获取CVC信息
            if ($syncCvc && !empty($cardData['card_number'])) {
                // 首先检查数据库中是否已经存在该卡片且有CVC数据
                // 从options中获取provider_id，如果没有则跳过数据库检查
                $providerId = $options['provider_id'] ?? null;

                if ($providerId) {
                    $existingCard = Card::where('source_id', $cardData['card_id'])
                        ->where('card_provider_id', $providerId)
                        ->first();
                } else {
                    $existingCard = Card::where('source_id', $cardData['card_id'])
                        ->first();
                }


                // 如果卡片不存在，要同步cvc
                // 如果卡片存在，如果当前状态是active, 状态 cvc 为 000，同步cvc。如果状态不是 active,不同步
                if ($existingCard) {
                    if ($cardData['status'] === 'ACTIVE') {
                        if ($existingCard['cvv'] === '000') {
                            try {
                                Log::info("Fetch cvc for card with 000 cvc", [
                                    'card_id' => $cardData['card_id'],
                                ]);
                                $cvcData = $this->fetchCardCvcInternal($cardData['card_number']);
                                $cardData['cvv'] = $cvcData['cvv'] ?? null;
                                $cardData['expiration'] = $cvcData['expiration'] ?? null;

                                Log::info("成功获取卡片CVC数据", [
                                    'card_id' => $cardData['card_id'],
                                    'card_number' => substr($cardData['card_number'], 0, 4) . '****',
                                    'status' => $cardData['status']
                                ]);
                            } catch (\Exception $e) {
                                // CVC获取失败，记录警告但不影响卡片数据
                                Log::warning("获取卡片CVC失败", [
                                    'card_id' => $cardData['card_id'],
                                    'card_number' => substr($cardData['card_number'], 0, 4) . '****',
                                    'error' => $e->getMessage()
                                ]);
                                // 不设置CVC字段，让CardSyncAll知道没有CVC数据
                            }
                        }
                    }

//                    Log::info("卡片已存在CVC数据，跳过API调用", [
//                        'card_id' => $cardData['card_id'],
//                        'card_number' => substr($cardData['card_number'], 0, 4) . '****',
//                        'existing_cvv' => '***',
//                        'existing_expiration' => $existingCard->expiration
//                    ]);
                    // 不设置CVC字段，让CardSyncAll使用现有数据
                } else {
                    // 检查卡片状态，如果是CLOSED或INACTIVE状态，直接设置默认CVC
                    if (in_array($cardData['status'], ['CLOSED', 'INACTIVE'])) {
                        $cardData['cvv'] = '000';
                        $cardData['expiration'] = '99/99';

                        Log::info("卡片状态为{$cardData['status']}，设置默认CVC", [
                            'card_id' => $cardData['card_id'],
                            'card_number' => substr($cardData['card_number'], 0, 4) . '****',
                            'status' => $cardData['status'],
                            'cvv' => $cardData['cvv'],
                            'expiration' => $cardData['expiration']
                        ]);
                    } else {
                        try {
                            Log::info("Fetch cvc", [
                                'card_id' => $cardData['card_id'],
                            ]);
                            $cvcData = $this->fetchCardCvcInternal($cardData['card_number']);
                            $cardData['cvv'] = $cvcData['cvv'] ?? null;
                            $cardData['expiration'] = $cvcData['expiration'] ?? null;

                            Log::info("成功获取卡片CVC数据", [
                                'card_id' => $cardData['card_id'],
                                'card_number' => substr($cardData['card_number'], 0, 4) . '****',
                                'status' => $cardData['status']
                            ]);
                        } catch (\Exception $e) {
                            // CVC获取失败，记录警告但不影响卡片数据
                            Log::warning("获取卡片CVC失败", [
                                'card_id' => $cardData['card_id'],
                                'card_number' => substr($cardData['card_number'], 0, 4) . '****',
                                'error' => $e->getMessage()
                            ]);
                            // 不设置CVC字段，让CardSyncAll知道没有CVC数据
                        }
                    }
                }
            }

            return $cardData;
        }, $data);
    }

    /**
     * 获取卡片CVC信息
     */
    public function getCardCvc(string $cardNumber, string $tradePassword = '12345'): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . "/cards/cvc", [
            'card_number' => $cardNumber,
            'trade_password' => $tradePassword
        ]);

        if (!$response->successful()) {
            // 检查是否是因为卡片状态为CLOSED导致的错误
            $errorMessage = $response->body();

            // 如果API返回卡片状态相关的错误，返回默认CVC
            if (str_contains(strtolower($errorMessage), 'closed') ||
                str_contains(strtolower($errorMessage), 'inactive') ||
                str_contains(strtolower($errorMessage), 'status')) {

                Log::info("卡片可能为CLOSED或INACTIVE状态，返回默认CVC", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'error' => $errorMessage
                ]);

                return [
                    'cvv' => '000',
                    'expiration' => '99/99',
                ];
            }

            throw new Exception("Failed to get card CVC: " . $errorMessage);
        }

        $data = $response->json();

        return [
            'cvv' => $data['data']['cvc'] ?? '',
            'expiration' => $data['data']['expiry'] ?? '',
        ];
    }

    /**
     * 解冻卡片（解锁卡片）
     */
    protected function doUnfreezeCard(string $sourceId): bool
    {
        // 首先获取卡片详情以获得 card_number 和当前状态
        try {
            $cardDetails = $this->doGetCardDetails($sourceId);
            $cardNumber = $cardDetails['card_number'] ?? null;
            $currentStatus = $cardDetails['status'] ?? null;

            if (empty($cardNumber)) {
                Log::error("Card number not found for card {$sourceId}");
                return false;
            }

            // 检查卡片当前状态，如果已经是 ACTIVE（解锁），则无需调用API
            if ($currentStatus === 'ACTIVE') {
                // 检查数据库中的状态是否一致
                $card = Card::where('source_id', $sourceId)->first();
                if ($card && $card->status !== 'ACTIVE') {
                    // 数据库状态不一致，需要更新
                    $oldStatus = $card->status;
                    $card->status = 'ACTIVE';
                    $card->save();

                    Log::info("Card {$sourceId} status synchronized: database updated from {$oldStatus} to ACTIVE", [
                        'card_number' => substr($cardNumber, 0, 4) . '****',
                        'api_status' => $currentStatus,
                        'old_db_status' => $oldStatus,
                        'new_db_status' => 'ACTIVE'
                    ]);
                } else {
                    Log::info("Card {$sourceId} is already active, both API and database status are consistent", [
                        'card_number' => substr($cardNumber, 0, 4) . '****',
                        'current_status' => $currentStatus
                    ]);
                }

                return true; // 已经是激活状态，返回成功
            }

            Log::info("Card {$sourceId} current status: {$currentStatus}, proceeding with unfreeze", [
                'card_number' => substr($cardNumber, 0, 4) . '****'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get card details for unfreeze operation", [
                'card_id' => $sourceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        // 调用 Adpos Unlock Card API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/cards/unlock', [
            'card_number' => $cardNumber
        ]);

        if (!$response->successful()) {
            Log::error("Failed to unfreeze card {$sourceId}", [
                'status' => $response->status(),
                'response' => $response->body(),
                'card_id' => $sourceId,
                'card_number' => substr($cardNumber, 0, 4) . '****'
            ]);
            return false;
        }

        // 根据API规范，成功时返回 200 或 201 状态码
        if (in_array($response->status(), [200, 201])) {
            // API 调用成功，更新数据库状态
            $card = Card::where('source_id', $sourceId)->first();
            if ($card) {
                $oldStatus = $card->status;
                $card->status = 'ACTIVE';
                $card->save();

                Log::info("Card {$sourceId} unfrozen successfully via API and database updated", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'previous_status' => $currentStatus,
                    'old_db_status' => $oldStatus,
                    'new_db_status' => 'ACTIVE',
                    'status_code' => $response->status()
                ]);
            } else {
                Log::warning("Card {$sourceId} unfrozen via API but not found in database", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'status_code' => $response->status()
                ]);
            }

            return true;
        }

        // 如果状态码不是200或201，记录详细信息
        Log::warning("Card unfreeze response unexpected", [
            'card_id' => $sourceId,
            'card_number' => substr($cardNumber, 0, 4) . '****',
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return false;
    }

    /**
     * 取消卡片（关闭卡片）
     */
    protected function doCancelCard(string $sourceId): bool
    {
        // 首先获取卡片详情以获得 card_number 和当前状态
        try {
            $cardDetails = $this->doGetCardDetails($sourceId);
            $cardNumber = $cardDetails['card_number'] ?? null;
            $currentStatus = $cardDetails['status'] ?? null;

            if (empty($cardNumber)) {
                Log::error("Card number not found for card {$sourceId}");
                return false;
            }

            // 检查卡片当前状态，如果已经是 CLOSED（取消），则无需调用API
            if ($currentStatus === 'CLOSED') {
                // 检查数据库中的状态是否一致
                $card = Card::where('source_id', $sourceId)->first();
                if ($card && $card->status !== 'CLOSED') {
                    // 数据库状态不一致，需要更新
                    $oldStatus = $card->status;
                    $card->status = 'CLOSED';
                    $card->save();

                    Log::info("Card {$sourceId} status synchronized: database updated from {$oldStatus} to CLOSED", [
                        'card_number' => substr($cardNumber, 0, 4) . '****',
                        'api_status' => $currentStatus,
                        'old_db_status' => $oldStatus,
                        'new_db_status' => 'CLOSED'
                    ]);
                } else {
                    Log::info("Card {$sourceId} is already cancelled, both API and database status are consistent", [
                        'card_number' => substr($cardNumber, 0, 4) . '****',
                        'current_status' => $currentStatus
                    ]);
                }

                return true; // 已经是取消状态，返回成功
            }

            Log::info("Card {$sourceId} current status: {$currentStatus}, proceeding with cancel", [
                'card_number' => substr($cardNumber, 0, 4) . '****'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get card details for cancel operation", [
                'card_id' => $sourceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        // 生成唯一的 request_id
        $requestId = $this->generateRequestId('cancel-card');

        // 调用 Adpos Cancel Card API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/cards/cancel', [
            'request_id' => $requestId,
            'items' => [
                [
                    'card_number' => $cardNumber
                ]
            ]
        ]);

        if (!$response->successful()) {
            Log::error("Failed to cancel card {$sourceId}", [
                'status' => $response->status(),
                'response' => $response->body(),
                'card_id' => $sourceId,
                'card_number' => substr($cardNumber, 0, 4) . '****',
                'request_id' => $requestId
            ]);
            return false;
        }

        // 根据API规范，成功时返回 200 状态码
        if ($response->status() === 200) {
            // API 调用成功，更新数据库状态
            $card = Card::where('source_id', $sourceId)->first();
            if ($card) {
                $oldStatus = $card->status;
                $card->status = 'CLOSED';
                $card->save();

                Log::info("Card {$sourceId} cancelled successfully via API and database updated", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'previous_status' => $currentStatus,
                    'old_db_status' => $oldStatus,
                    'new_db_status' => 'CLOSED',
                    'status_code' => $response->status(),
                    'request_id' => $requestId
                ]);
            } else {
                Log::warning("Card {$sourceId} cancelled via API but not found in database", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'status_code' => $response->status(),
                    'request_id' => $requestId
                ]);
            }

            return true;
        }

        // 如果状态码不是200，记录详细信息
        Log::warning("Card cancel response unexpected", [
            'card_id' => $sourceId,
            'card_number' => substr($cardNumber, 0, 4) . '****',
            'status' => $response->status(),
            'response' => $response->body(),
            'request_id' => $requestId
        ]);

        return false;
    }

    /**
     * 映射Adpos卡片状态到系统标准状态
     */
    private function mapAdposStatus(string $adposStatus): string
    {
        $statusMap = [
            'activated' => 'ACTIVE',
            'locked' => 'INACTIVE',
            'suspended' => 'CLOSED',
        ];

        return $statusMap[strtolower($adposStatus)] ?? 'INACTIVE';
    }

    /**
     * 冻结卡片（锁卡）
     */
    protected function doFreezeCard(string $sourceId): bool
    {
        // 首先获取卡片详情以获得 card_number 和当前状态
        try {
            $cardDetails = $this->doGetCardDetails($sourceId);
            $cardNumber = $cardDetails['card_number'] ?? null;
            $currentStatus = $cardDetails['status'] ?? null;

            if (empty($cardNumber)) {
                Log::error("Card number not found for card {$sourceId}");
                return false;
            }

            // 检查卡片当前状态，如果已经是 INACTIVE（锁定），则无需调用API
            if ($currentStatus === 'INACTIVE') {
                // 检查数据库中的状态是否一致
                $card = Card::where('source_id', $sourceId)->first();
                if ($card && $card->status !== 'INACTIVE') {
                    // 数据库状态不一致，需要更新
                    $oldStatus = $card->status;
                    $card->status = 'INACTIVE';
                    $card->save();

                    Log::info("Card {$sourceId} status synchronized: database updated from {$oldStatus} to INACTIVE", [
                        'card_number' => substr($cardNumber, 0, 4) . '****',
                        'api_status' => $currentStatus,
                        'old_db_status' => $oldStatus,
                        'new_db_status' => 'INACTIVE'
                    ]);
                } else {
                    Log::info("Card {$sourceId} is already frozen, both API and database status are consistent", [
                        'card_number' => substr($cardNumber, 0, 4) . '****',
                        'current_status' => $currentStatus
                    ]);
                }

                return true; // 已经是锁定状态，返回成功
            }

            Log::info("Card {$sourceId} current status: {$currentStatus}, proceeding with freeze", [
                'card_number' => substr($cardNumber, 0, 4) . '****'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get card details for freeze operation", [
                'card_id' => $sourceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        // 调用 Adpos Lock Card API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/cards/lock', [
            'card_number' => $cardNumber
        ]);

        if (!$response->successful()) {
            Log::error("Failed to freeze card {$sourceId}", [
                'status' => $response->status(),
                'response' => $response->body(),
                'card_id' => $sourceId,
                'card_number' => substr($cardNumber, 0, 4) . '****'
            ]);
            return false;
        }

        // 根据API规范，成功时返回 200 或 201 状态码
        if (in_array($response->status(), [200, 201])) {
            // API 调用成功，更新数据库状态
            $card = Card::where('source_id', $sourceId)->first();
            if ($card) {
                $oldStatus = $card->status;
                $card->status = 'INACTIVE';
                $card->save();

                Log::info("Card {$sourceId} frozen successfully via API and database updated", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'previous_status' => $currentStatus,
                    'old_db_status' => $oldStatus,
                    'new_db_status' => 'INACTIVE',
                    'status_code' => $response->status()
                ]);
            } else {
                Log::warning("Card {$sourceId} frozen via API but not found in database", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'status_code' => $response->status()
                ]);
            }

            return true;
        }

        // 如果状态码不是200或201，记录详细信息
        Log::warning("Card freeze response unexpected", [
            'card_id' => $sourceId,
            'card_number' => substr($cardNumber, 0, 4) . '****',
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return false;
    }

    /**
     * 设置卡片余额
     */
    protected function doSetBalance(string $sourceId, float $balance): bool
    {
        // 首先获取卡片详情以获得 card_number
        try {
            $cardDetails = $this->doGetCardDetails($sourceId);
            $cardNumber = $cardDetails['card_number'] ?? null;
            $currentStatus = $cardDetails['status'] ?? null;

            if (empty($cardNumber)) {
                Log::error("Card number not found for card {$sourceId}");
                return false;
            }

            // 检查卡片状态，如果是 CLOSED 状态，无法设置余额
            if ($currentStatus === 'CLOSED') {
                Log::error("Cannot set balance for closed card {$sourceId}", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'status' => $currentStatus
                ]);
                return false;
            }

            Log::info("Setting balance for card {$sourceId}", [
                'card_number' => substr($cardNumber, 0, 4) . '****',
                'new_balance' => $balance,
                'current_status' => $currentStatus
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get card details for set balance operation", [
                'card_id' => $sourceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        // 调用 Adpos Update Card API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/cards/update', [
            'card_number' => $cardNumber,
            'shared_card_balance' => $balance
        ]);

        if (!$response->successful()) {
            Log::error("Failed to set balance for card {$sourceId}", [
                'status' => $response->status(),
                'response' => $response->body(),
                'card_id' => $sourceId,
                'card_number' => substr($cardNumber, 0, 4) . '****',
                'new_balance' => $balance
            ]);
            return false;
        }

        // 根据API规范，成功时返回 200 或 201 状态码
        if (in_array($response->status(), [200, 201])) {
            // API 调用成功，更新数据库中的余额
            $card = Card::where('source_id', $sourceId)->first();
            if ($card) {
                $oldBalance = $card->balance;
                $card->balance = $balance;
                $card->save();

                Log::info("Card {$sourceId} balance updated successfully via API and database updated", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'old_balance' => $oldBalance,
                    'new_balance' => $balance,
                    'status_code' => $response->status()
                ]);
            } else {
                Log::warning("Card {$sourceId} balance updated via API but not found in database", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'new_balance' => $balance,
                    'status_code' => $response->status()
                ]);
            }

            return true;
        }

        // 如果状态码不是200或201，记录详细信息
        Log::warning("Card balance update response unexpected", [
            'card_id' => $sourceId,
            'card_number' => substr($cardNumber, 0, 4) . '****',
            'new_balance' => $balance,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return false;
    }

    /**
     * 设置卡片单笔交易限额
     */
    protected function doSetPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        // 首先获取卡片详情以获得 card_number
        try {
            $cardDetails = $this->doGetCardDetails($sourceId);
            $cardNumber = $cardDetails['card_number'] ?? null;
            $currentStatus = $cardDetails['status'] ?? null;

            if (empty($cardNumber)) {
                Log::error("Card number not found for card {$sourceId}");
                return false;
            }

            // 检查卡片状态，如果是 CLOSED 状态，无法设置限额
            if ($currentStatus === 'CLOSED') {
                Log::error("Cannot set per transaction limit for closed card {$sourceId}", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'status' => $currentStatus
                ]);
                return false;
            }

            Log::info("Setting per transaction limit for card {$sourceId}", [
                'card_number' => substr($cardNumber, 0, 4) . '****',
                'new_limit' => $perTransLimit,
                'current_status' => $currentStatus
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get card details for set per transaction limit operation", [
                'card_id' => $sourceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        // 调用 Adpos Update Card API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/cards/update', [
            'card_number' => $cardNumber,
            'single_transaction_limit' => $perTransLimit
        ]);

        if (!$response->successful()) {
            Log::error("Failed to set per transaction limit for card {$sourceId}", [
                'status' => $response->status(),
                'response' => $response->body(),
                'card_id' => $sourceId,
                'card_number' => substr($cardNumber, 0, 4) . '****',
                'new_limit' => $perTransLimit
            ]);
            return false;
        }

        // 根据API规范，成功时返回 200 或 201 状态码
        if (in_array($response->status(), [200, 201])) {
            // API 调用成功，更新数据库中的限额
            $card = Card::where('source_id', $sourceId)->first();
            if ($card) {
                $oldLimit = $card->single_transaction_limit;
                $card->single_transaction_limit = $perTransLimit;
                $card->save();

                Log::info("Card {$sourceId} per transaction limit updated successfully via API and database updated", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'old_limit' => $oldLimit,
                    'new_limit' => $perTransLimit,
                    'status_code' => $response->status()
                ]);
            } else {
                Log::warning("Card {$sourceId} per transaction limit updated via API but not found in database", [
                    'card_number' => substr($cardNumber, 0, 4) . '****',
                    'new_limit' => $perTransLimit,
                    'status_code' => $response->status()
                ]);
            }

            return true;
        }

        // 如果状态码不是200或201，记录详细信息
        Log::warning("Card per transaction limit update response unexpected", [
            'card_id' => $sourceId,
            'card_number' => substr($cardNumber, 0, 4) . '****',
            'new_limit' => $perTransLimit,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return false;
    }

    // 注意：这个Provider不实现以下方法，因为不支持这些功能：
    // - doSetTotalLimit
    // - doSyncAllTransactions

    // 当调用这些方法时，BaseCardProvider会自动返回false或记录日志

    /**
     * 获取账户余额信息
     */
    public function getAccountBalance(): float
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getToken(),
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/account');

            if (!$response->successful()) {
                Log::error("Failed to get account balance from Adpos", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new Exception("Failed to get account balance: " . $response->body());
            }

            $data = $response->json();

            // 根据API文档，余额字段是shared_account_balance
            $balance = $data['shared_account_balance'] ?? $data['data']['shared_account_balance'] ?? 0;

            Log::info("Adpos account balance retrieved", [
                'balance' => $balance
            ]);

            return (float) $balance;

        } catch (Exception $e) {
            Log::error("Exception getting Adpos account balance", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
