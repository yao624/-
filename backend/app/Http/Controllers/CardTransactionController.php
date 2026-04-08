<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardResource;
use App\Http\Resources\CardTransactionResource;
use App\Jobs\CardSync;
use App\Jobs\CardSyncAllTrans;
use App\Jobs\CardSyncAllTransactionsSequential;
use App\Jobs\CardSyncTransactions;
use App\Jobs\CardSyncTrans;
use App\Jobs\TriggerCardSync;
use App\Models\Card;
use App\Models\CardTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardTransactionController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'transaction_date');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'number' => $request->get('number'),
            'status' => $request->get('status'),
            'transaction_date' => $request->get('transaction_date'),
            'transaction_type' => $request->get('transaction_type'),
        ];
        $transaction_date_start = $request->input('transaction_date_start');
        $transaction_date_stop = $request->input('transaction_date_stop');

        $card_number = $request->input('card_number');
        $card_name = $request->input('card_name');


        Log::debug("st: {$transaction_date_start}, stp: {$transaction_date_stop}");

        $card_trans = CardTransaction::search($searchableFields);
        if ($transaction_date_start) {
            $card_trans = $card_trans->where('transaction_date', '>=', $transaction_date_start);
        }
        if ($transaction_date_stop) {
            $card_trans = $card_trans->where('transaction_date', '<=', $transaction_date_stop);
        }

        if ($card_number) {
            $card_trans = CardTransaction::whereHas('card', function ($query) use ($card_number) {
                $query->where('number', $card_number);
            });
        }
        if ($card_name) {
            $card_trans = CardTransaction::whereHas('card', function ($query) use ($card_name) {
                $query->where('name', $card_name);
            });
        }

        $card_trans = $card_trans->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => CardTransactionResource::collection($card_trans->items()),
            'pageSize' => $card_trans->perPage(),
            'pageNo' => $card_trans->currentPage(),
            'totalPage' => $card_trans->lastPage(),
            'totalCount' => $card_trans->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CardTransaction $cardTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CardTransaction $cardTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CardTransaction $cardTransaction)
    {
        //
    }

    public function sync_all(Request $request)
    {
        CardSyncAllTrans::dispatch()->onQueue('cards');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function sync(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
            'start_time' => 'nullable|date',
            'stop_time' => 'nullable|date',
        ]);

        $ids = collect($request->input('ids'));
        $startTime = $request->input('start_time');
        $stopTime = $request->input('stop_time');

        // 如果没有提供时间参数，默认使用最近3天
        if (!$startTime || !$stopTime) {
            $startTime = strtotime('-3 days');
            $stopTime = strtotime('now');
        } else {
            $startTime = strtotime($startTime);
            $stopTime = strtotime($stopTime);
        }

        $existed_cards = Card::query()->whereIn('id', $ids)->get();

        Log::info("Starting transaction sync for cards", [
            'card_count' => $existed_cards->count(),
            'start_time' => date('Y-m-d H:i:s', $startTime),
            'stop_time' => date('Y-m-d H:i:s', $stopTime)
        ]);

        foreach ($existed_cards as $index => $card) {
            CardSyncTransactions::dispatch(
                $startTime,           // start_time
                $stopTime,            // stop_time
                null,                 // after
                null,                 // status
                null,                 // provider
                $card->source_id      // card_source_id
            )->onQueue('transactions')->delay(now()->addSeconds($index * 5));
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true,
            'details' => [
                'cards_count' => $existed_cards->count(),
                'time_range' => [
                    'start' => date('Y-m-d H:i:s', $startTime),
                    'stop' => date('Y-m-d H:i:s', $stopTime)
                ]
            ]
        ]);
    }

        public function sync_all_60_days(Request $request)
    {
        // 获取所有卡片
        $all_cards = Card::query()->get();

        if ($all_cards->isEmpty()) {
            return response()->json([
                'message' => '没有找到任何卡片',
                'success' => false,
                'data' => []
            ]);
        }

        // 计算时间范围：最近60天到今天+1天
        $startTime = strtotime('-120 days');
        $stopTime = strtotime('+1 day'); // 结束日期比今天多一天，考虑时差

        Log::info("Starting sequential 60-day transaction sync for all cards", [
            'total_cards' => $all_cards->count(),
            'start_time' => date('Y-m-d H:i:s', $startTime),
            'stop_time' => date('Y-m-d H:i:s', $stopTime)
        ]);

        // 使用专门的串行同步Job，确保每张卡完成后再处理下一张
        CardSyncAllTransactionsSequential::dispatch(
            $startTime,
            $stopTime,
            $all_cards->pluck('id')->toArray(), // 传递卡片ID数组
            0 // 从第0张卡片开始
        )->onQueue('transactions');

        return response()->json([
            'message' => '全部卡片60天交易串行同步任务已提交',
            'success' => true,
            'data' => [
                'total_cards' => $all_cards->count(),
                'time_range' => [
                    'start_time' => date('Y-m-d H:i:s', $startTime),
                    'stop_time' => date('Y-m-d H:i:s', $stopTime),
                    'days' => 61 // 60天历史数据 + 1天未来数据
                ],
                'execution_info' => [
                    'queue' => 'transactions',
                    'execution_mode' => '真正串行执行：每张卡交易同步完成后才处理下一张',
                    'sync_method' => '每张卡会完整同步所有分页数据',
                    'error_handling' => '单张卡失败不影响其他卡片处理'
                ]
            ]
        ]);
    }
}
