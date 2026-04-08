<?php

namespace App\Services\CardProviders;

use App\Contracts\CardProviderInterface;
use App\Models\Card;
use App\Models\CardTransaction;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirwallexProvider implements CardProviderInterface
{
    private array $config;
    private string $baseUrl = 'https://api.airwallex.com';

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getToken(): string
    {
        $token = Cache::remember('AIRWALLEX_TOKEN', 1500, function () {
            return $this->authenticate();
        });

        if (!$token) {
            throw new Exception("failed to auth airwallex");
        }

        return $token;
    }

    private function authenticate(): string
    {
        $clientId = $this->config['client_id'] ?? 'xxx';
        $apiKey = $this->config['api_key'] ?? 'xxxxx';

        $url = "{$this->baseUrl}/api/v1/authentication/login";

        $response = Http::withHeaders([
            'x-client-id' => $clientId,
            'x-api-key' => $apiKey
        ])->post($url);

        if ($response->successful()) {
            return $response->json('token');
        }

        Log::warning("failed to auth for airwallex");
        Log::warning($response->body());
        return '';
    }

    public function createCard(string $cardName, float $balance, array $options = []): array
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/create";
        $requestId = $this->generateRequestId('opencard');

        $payload = [
            "authorization_controls" => [
                "allowed_transaction_count" => "MULTIPLE",
                "transaction_limits" => [
                    "currency" => "USD",
                    "limits" => [
                        [
                            "amount" => intval($balance),
                            "interval" => "ALL_TIME"
                        ],
                        [
                            'interval' => 'PER_TRANSACTION',
                            'amount' => 1200
                        ],
                    ]
                ]
            ],
            "nick_name" => $cardName,
            "created_by" => "Fuxi Digital BE",
            "form_factor" => "VIRTUAL",
            "issue_to" => "ORGANISATION",
            "request_id" => $requestId,
            "purpose" => "MARKETING_EXPENSES"
        ];

        $response = Http::withToken($token)->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("failed to create card: {$response->body()}, status: {$response->status()}");
            throw new Exception("failed to create card");
        }

        $data = $response->json();
        $cardId = $data['card_id'];

        // 获取卡片详情
        $cardDetails = $this->getCardDetails($cardId);

        $cardLimits = $data['authorization_controls']['transaction_limits']['limits'];
        $totalBalance = -1;
        foreach ($cardLimits as $limit) {
            if ($limit['interval'] == 'ALL_TIME') {
                $totalBalance = floatval($limit['amount']);
                break;
            }
        }

        $expiryMonth = $cardDetails['expiry_month'];
        $expiryYear = $cardDetails['expiry_year'];
        $formattedExpiry = sprintf('%02d/%02d', $expiryMonth, $expiryYear % 100);

        return [
            'card_id' => $cardId,
            'status' => $data['card_status'],
            'balance' => $totalBalance,
            'number' => $cardDetails['card_number'],
            'cvv' => $cardDetails['cvv'],
            'expiration' => $formattedExpiry
        ];
    }

    public function freezeCard(string $sourceId): bool
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}/update";

        $payload = ['card_status' => 'INACTIVE'];

        $response = Http::withToken($token)->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("failed to freeze card, status: {$response->status()}, body: {$response->body()}");
            return false;
        }

        $status = $response->json('card_status');
        return $status === 'INACTIVE';
    }

    public function unfreezeCard(string $sourceId): bool
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}/update";

        $payload = ['card_status' => 'ACTIVE'];

        $response = Http::withToken($token)->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("failed to unfreeze card, status: {$response->status()}, body: {$response->body()}");
            return false;
        }

        $status = $response->json('card_status');
        return $status === 'ACTIVE';
    }

    public function cancelCard(string $sourceId): bool
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}/update";

        $payload = ['card_status' => 'CLOSED'];

        $response = Http::withToken($token)->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("failed to cancel card, status: {$response->status()}, body: {$response->body()}");
            return false;
        }

        $status = $response->json('card_status');
        return $status === 'CLOSED';
    }

    public function syncCard(string $sourceId, bool $syncCvv = false): array
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}";

        $response = Http::withToken($token)->get($endpoint);

        if (!$response->successful()) {
            Log::error("failed to get card detail, status: {$response->status()}, body: {$response->body()}");
            throw new Exception('failed to get card detail');
        }

        $data = $response->json();
        $status = $data['card_status'];
        $name = $data['nick_name'];
        $createdAt = Carbon::parse($data['created_at']);
        $currency = $data['authorization_controls']['transaction_limits']['currency'];
        $limits = $data['authorization_controls']['transaction_limits']['limits'];

        $totalLimit = -2;
        foreach ($limits as $limit) {
            if ($limit['interval'] == 'ALL_TIME') {
                $totalLimit = floatval($limit['amount']);
                break;
            } elseif ($limit['interval'] == 'DAILY') {
                $totalLimit = floatval($limit['amount']);
                break;
            } else {
                $totalLimit = -1;
            }
        }

        $result = [
            'name' => $name,
            'status' => $status,
            'limits' => $totalLimit,
            'balance' => $totalLimit,
            'created_at' => $createdAt,
            'currency' => $currency
        ];

        if ($syncCvv) {
            $cardDetails = $this->getCardDetails($sourceId);
            $expiryMonth = $cardDetails['expiry_month'];
            $expiryYear = $cardDetails['expiry_year'];
            $formattedExpiry = sprintf('%02d/%02d', $expiryMonth, $expiryYear % 100);

            $result['number'] = $cardDetails['card_number'];
            $result['cvv'] = $cardDetails['cvv'];
            $result['expiration'] = $formattedExpiry;
        }

        return $result;
    }

    public function setTotalLimit(string $sourceId, float $totalLimit): bool
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}/update";

        $payload = [
            'authorization_controls' => [
                'transaction_limits' => [
                    'limits' => [
                        [
                            'interval' => 'ALL_TIME',
                            'amount' => $totalLimit
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withToken($token)->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("failed to set total limit, status: {$response->status()}, body: {$response->body()}");
            return false;
        }

        return true;
    }

    public function setPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        $card = Card::where('source_id', $sourceId)->first();
        if (!$card) {
            return false;
        }

        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}/update";

        $payload = [
            'authorization_controls' => [
                'transaction_limits' => [
                    'limits' => [
                        [
                            'interval' => 'PER_TRANSACTION',
                            'amount' => $perTransLimit
                        ],
                        [
                            'interval' => 'ALL_TIME',
                            'amount' => $card->limits ?? 0
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withToken($token)->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("failed to set per trans limit, status: {$response->status()}, body: {$response->body()}");
            return false;
        }

        return true;
    }

    public function getCardDetails(string $sourceId): array
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards/{$sourceId}/details";

        $response = Http::withToken($token)->get($endpoint);

        if (!$response->successful()) {
            Log::error("failed to get card details: {$sourceId}");
            throw new Exception("failed to get card details");
        }

        return $response->json();
    }

    public function syncTransactions(string $sourceId): array
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/transactions";

        $pageNum = 0;
        $params = [
            'page_num' => $pageNum,
            'page_size' => 200,
            'card_id' => $sourceId
        ];

        $allTransactions = [];
        $hasMore = true;

        while ($hasMore) {
            $response = Http::withToken($token)->get($endpoint, $params);

            if (!$response->successful()) {
                Log::error("failed to sync card trans, status: {$response->status()}, body: {$response->body()}");
                throw new Exception('failed to sync card trans');
            }

            $hasMore = $response->json('has_more');
            $items = $response->json('items', []);

            foreach ($items as $item) {
                $allTransactions[] = $this->formatTransaction($item);
            }

            $params['page_num']++;
        }

        return $allTransactions;
    }

    public function syncAllTransactions(): array
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/transactions";

        $pageNum = 0;
        $params = [
            'page_num' => $pageNum,
            'page_size' => 200
        ];

        $allTransactions = [];
        $hasMore = true;

        while ($hasMore) {
            $response = Http::withToken($token)->timeout(120)->connectTimeout(120)->get($endpoint, $params);

            if (!$response->successful()) {
                Log::error("failed to sync all card trans, status: {$response->status()}, body: {$response->body()}");
                throw new Exception('failed to sync all card trans');
            }

            $hasMore = $response->json('has_more');
            $items = $response->json('items', []);

            foreach ($items as $item) {
                $allTransactions[] = $this->formatTransaction($item);
            }

            $params['page_num']++;
        }

        return $allTransactions;
    }

    public function getAllCards(array $options = []): array
    {
        $token = $this->getToken();
        $endpoint = "{$this->baseUrl}/api/v1/issuing/cards";

        $response = Http::withToken($token)->get($endpoint);

        if (!$response->successful()) {
            Log::error("failed to pull card list, status: {$response->status()}, body: {$response->body()}");
            throw new Exception('failed to pull card list');
        }

        return $response->json('items', []);
    }

    public function generateRequestId(string $function): string
    {
        $timestamp = date('YmdHis');
        $randomStr = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
        return $function . '-' . $timestamp . '-' . $randomStr;
    }

    private function formatTransaction(array $item): array
    {
        return [
            'transaction_id' => $item['transaction_id'],
            'card_id' => $item['card_id'],
            'status' => $item['status'],
            'transaction_amount' => -floatval($item['transaction_amount']),
            'transaction_date' => Carbon::parse($item['transaction_date']),
            'transaction_type' => $item['transaction_type'],
            'merchant_name' => $item['merchant']['name'],
            'auth_code' => $item['auth_code'],
            'posted_date' => $item['posted_date'] ? Carbon::parse($item['posted_date']) : null,
            'failure_reason' => $item['failure_reason'] ?? "",
        ];
    }
}
