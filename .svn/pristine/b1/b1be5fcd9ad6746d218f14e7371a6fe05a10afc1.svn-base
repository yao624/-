<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardTransaction;
use App\Jobs\CardSync;
use App\Jobs\CardSyncTransactions;
use App\Jobs\FbWebhookProcessor;
use App\Utils\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookController extends Controller
{
    /**
     * 处理 Adpos Provider 的 webhook 回调
     */
    public function vccCallback(Request $request)
    {
        try {
            // 记录接收到的webhook数据
            Log::info('VCC Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->header(),
                'ip' => $request->ip(),
                'raw_body' => $request->getContent()
            ]);

            $data = $request->all();

            // 必要的字段验证
            if (!isset($data['card_number'])) {
                Log::warning('VCC Webhook missing card_number', $data);
                return response()->json(['status' => 'error', 'message' => 'Missing card_number'], 400);
            }

            $cardNumber = $data['card_number'];

            // 1. 根据卡号查询卡片是否在数据库中
            $card = Card::where('number', $cardNumber)->first();

            if (!$card) {
                // 2. 如果卡片不存在，发送telegram通知并直接返回
                $message = "⚠️ Webhook: 卡片未同步到数据库\n卡号: {$this->maskCardNumber($cardNumber)}";
                Telegram::sendMessage($message);

                Log::warning('VCC Webhook: Card not found in database', [
                    'card_number' => $this->maskCardNumber($cardNumber)
                ]);

                return response()->json(['status' => 'success', 'message' => 'processed'], 200);
            }

            // 3. 检查卡片是否绑定了Facebook广告账户
            $hasAdAccounts = $card->fbAdAccounts()->exists();

            if (!$hasAdAccounts) {
                // 发送telegram warning通知
                $transactionAmount = $data['billing_amount'] ?? 'N/A';
                $merchantName = $data['merchant_name'] ?? 'Unknown Merchant';
                $status = $data['status'] ?? 'Unknown';

                $message = "⚠️ 非Facebook交易警告\n" .
                          "卡号: {$this->maskCardNumber($cardNumber)}\n" .
                          "金额: $transactionAmount USD\n" .
                          "商户: $merchantName\n" .
                          "状态: $status";

                Telegram::sendMessage($message);

                Log::warning('VCC Webhook: Non-Facebook transaction detected', [
                    'card_number' => $this->maskCardNumber($cardNumber),
                    'amount' => $transactionAmount,
                    'merchant' => $merchantName,
                    'status' => $status
                ]);
            }

            // 4. 保存交易记录
            $this->saveTransaction($card, $data);

            // 5. 触发异步任务
            // CardSync - 同步卡片信息，第二个参数为false
            CardSync::dispatch($card->source_id, false)
                ->onQueue('cards')
                ->delay(now()->addSeconds(5));

            // CardSyncTransactions - 同步最近2天的交易
            $now = now();
            $twoDaysAgo = $now->copy()->subDays(2);

            CardSyncTransactions::dispatch(
                $twoDaysAgo->timestamp,  // start_time
                $now->timestamp,         // stop_time
                null,                    // after
                null,                    // status
                null,                    // provider
                $card->source_id         // cardSourceId
            )->onQueue('transactions')
             ->delay(now()->addSeconds(10));

            Log::info('VCC Webhook processed successfully', [
                'card_id' => $card->id,
                'transaction_id' => $data['id'] ?? null,
                'jobs_dispatched' => ['CardSync', 'CardSyncTransactions']
            ]);

            return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            // 任何异常都发送telegram通知
            $errorMessage = "🚨 VCC Webhook 处理异常\n" .
                          "错误: {$e->getMessage()}\n" .
                          "文件: {$e->getFile()}:{$e->getLine()}";

            Telegram::sendMessage($errorMessage);

            Log::error('VCC Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            // 因为要让第三方知道处理了，所以即使出错，也要返回ok
            return response()->json(['status' => 'ok', 'message' => 'processed'], 200);
        }
    }

    /**
     * 保存交易记录
     */
    private function saveTransaction(Card $card, array $data)
    {
        try {
            // 参照AdposProvider的映射逻辑
            $transactionData = [
                'card_id' => $card->id,
                'source_id' => $data['id'] ?? null,
                'status' => $this->mapTransactionStatus($data['status'] ?? 'unknown'),
                'transaction_amount' => isset($data['billing_amount']) ?
                    round(floatval($data['billing_amount']), 2) : 0.00,
                'currency' => $data['billing_currency'] ?? 'USD',
                'transaction_date' => isset($data['transaction_unix_timestamp']) ?
                    Carbon::createFromTimestamp($data['transaction_unix_timestamp']) : now(),
                'transaction_type' => $data['transaction_type'] ?? 'authorization',
                'merchant_name' => $data['merchant_name'] ?? 'Unknown Merchant',
                'custom_1' => $data['billing_status'] ?? null, // billing_status 映射到 custom_1
                'posted_date' => $this->calculatePostedDate($data),
                'failure_reason' => $data['fail_reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            // 检查交易是否已存在
            if ($transactionData['source_id']) {
                $existingTransaction = CardTransaction::where('source_id', $transactionData['source_id'])
                    ->where('card_id', $card->id)
                    ->first();

                if ($existingTransaction) {
                    // 更新现有交易
                    $existingTransaction->update($transactionData);
                    Log::info('Updated existing transaction from webhook', [
                        'transaction_id' => $transactionData['source_id'],
                        'card_id' => $card->id
                    ]);
                } else {
                    // 创建新交易
                    CardTransaction::create($transactionData);
                    Log::info('Created new transaction from webhook', [
                        'transaction_id' => $transactionData['source_id'],
                        'card_id' => $card->id
                    ]);
                }
            } else {
                Log::warning('Transaction missing source_id, skipping save', $data);
            }

        } catch (\Exception $e) {
            Log::error('Failed to save transaction from webhook', [
                'error' => $e->getMessage(),
                'card_id' => $card->id,
                'transaction_data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * 映射交易状态
     */
    private function mapTransactionStatus(string $status): string
    {
        $statusMap = [
            'approved' => 'approved',
            'declined' => 'declined',
            'pending' => 'pending',
        ];

        return $statusMap[strtolower($status)] ?? 'unknown';
    }

    /**
     * 计算posted_date
     */
    private function calculatePostedDate(array $data)
    {
        $billingStatus = $data['billing_status'] ?? null;

        if ($billingStatus === 'declined') {
            // 如果billing_status为declined，posted_date设置为transaction_date
            return isset($data['transaction_unix_timestamp']) ?
                Carbon::createFromTimestamp($data['transaction_unix_timestamp']) : now();
        }

        // 其他情况暂时设为null，等待后续同步更新
        return null;
    }

    /**
     * 处理 Facebook Webhook 回调
     */
    public function fbWebhook(Request $request)
    {
        try {
            // 记录接收到的webhook数据
            Log::info('Facebook Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->header(),
                'ip' => $request->ip(),
                'raw_body' => $request->getContent()
            ]);

            $data = $request->all();

            // 安全校验 - 检查key
            $expectedKey = "9bPx4gu973VFJtizfc4CRlw71b1yDSFws";
            if (!isset($data['key']) || $data['key'] !== $expectedKey) {
                Log::warning('Facebook Webhook: Invalid or missing key', [
                    'provided_key' => $data['key'] ?? 'null',
                    'ip' => $request->ip()
                ]);
                return response()->json(['status' => 'error', 'message' => 'Invalid key'], 401);
            }

            // 检查data参数
            if (!isset($data['data'])) {
                Log::warning('Facebook Webhook: Missing data parameter');
                return response()->json(['status' => 'error', 'message' => 'Missing data'], 400);
            }

            // 将数据放到异步任务处理
            FbWebhookProcessor::dispatch($data['data'])
                ->onQueue('frontend')
                ->delay(now()->addSeconds(1));

            Log::info('Facebook Webhook dispatched to job', [
                'data' => $data['data']
            ]);

            // 直接返回成功
            return response()->json(['status' => 'success', 'message' => 'Webhook received and queued for processing'], 200);

        } catch (\Exception $e) {
            // 任何异常都发送telegram通知
            $errorMessage = "🚨 Facebook Webhook 处理异常\n" .
                          "错误: {$e->getMessage()}\n" .
                          "文件: {$e->getFile()}:{$e->getLine()}";

            Telegram::sendMessage($errorMessage);

            Log::error('Facebook Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            // 返回成功状态让Facebook知道我们接收了webhook
            return response()->json(['status' => 'ok', 'message' => 'processed'], 200);
        }
    }

    /**
     * 掩码卡号显示
     */
    private function maskCardNumber(string $cardNumber): string
    {
        if (strlen($cardNumber) <= 8) {
            return $cardNumber; // 如果卡号太短，直接返回
        }

        $showFirst = 4;
        $showLast = 4;
        $maskLength = strlen($cardNumber) - ($showFirst + $showLast);
        $mask = str_repeat('*', $maskLength);
        $firstPart = substr($cardNumber, 0, $showFirst);
        $lastPart = substr($cardNumber, -1 * $showLast);

        return $firstPart . $mask . $lastPart;
    }
}
