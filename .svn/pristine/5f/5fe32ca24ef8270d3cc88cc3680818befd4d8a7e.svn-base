<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardResource;
use App\Http\Resources\CardResourceWithoutInsight;
use App\Jobs\CardCancel;
use App\Jobs\CardCreate;
use App\Jobs\CardFreeze;
use App\Jobs\CardOneShotPerTrans;
use App\Jobs\CardOneShotTotalLimit;
use App\Jobs\CardSetBalance;
use App\Jobs\CardSetSingleTransLimit;
use App\Jobs\CardSync;
use App\Jobs\CardUnfreeze;
use App\Jobs\TriggerCardSync;
use App\Models\Card;
use App\Utils\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\CardProviderService;

class CardController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'applied_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];

        $searchableFields = [
            'name' => $request->get('name'),
            'number' => $request->get('number'),
            'notes' => $request->get('notes'),
            'status' => $request->get('status'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $cards = Card::with('cardProvider')->searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => CardResource::collection($cards->items()),
            'pageSize' => $cards->perPage(),
            'pageNo' => $cards->currentPage(),
            'totalPage' => $cards->lastPage(),
            'totalCount' => $cards->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /* 开卡，支持批量
        参数: prefix, number, balance, card_provider_id
        */
        $request->validate([
            'prefix' => 'nullable|string|max:20',
            'number' => 'required|numeric|gt:0',
            'balance' => 'numeric|gte:20',
            'card_provider_id' => 'required|exists:card_providers,id'
        ]);

        // 检查provider是否active
        $cardProvider = \App\Models\CardProvider::find($request->card_provider_id);
        if (!$cardProvider || !$cardProvider->active) {
            return response()->json([
                'message' => 'Provider不存在或已被禁用',
                'success' => false
            ], 400);
        }

        $default_prefix = "Card_" . Str::random(8);
        $prefix = $request->input('prefix');
        if (empty($prefix)) {
            $prefix = $default_prefix;
        }

        $number = $request->input('number');
        $balance = $request->input('balance');
        $cardProviderId = $request->input('card_provider_id');

        for($i=0; $i<$number; $i++) {
            $card_name = "{$prefix}-{$i}";
            CardCreate::dispatch($card_name, $balance, $cardProviderId)->onQueue('cards')->delay(now()->addSeconds(($i + 1) * 5));
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Card $card)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Card $card)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Card $card)
    {
        //
    }

    public function freeze(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
            'sync' => 'boolean'
        ]);

        $ids = collect($request->input('ids'));
        $sync = $request->input('sync', false);

        $existed_cards = Card::query()->whereIn('id', $ids)->get();

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($existed_cards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->freezeCard($card->source_id);

                    // 只在成功时刷新卡片信息以获取最新状态
                    if ($success) {
                        $card->refresh();
                    }

                    $results[] = [
                        'success' => $success,
                        'message' => $success ? '冻结成功' : '冻结失败',
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '冻结失败: ' . $e->getMessage(),
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                    Telegram::sendMessage("冻结卡片失败: " . $card->source_id);
                }
            }

            return response()->json([
                'message' => '同步执行完成',
                'success' => true,
                'results' => $results
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($existed_cards as $index => $card)
            {
                CardFreeze::dispatch($card->id)->onQueue('cards')->delay(now()->addSeconds($index+1));
            }

            return response()->json([
                'message' => trans('message.task_submitted', [], $this->language),
                'success' => true
            ]);
        }
    }

    public function unfreeze(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
            'sync' => 'boolean'
        ]);

        $ids = collect($request->input('ids'));
        $sync = $request->input('sync', false);

        $existed_cards = Card::query()->whereIn('id', $ids)->get();

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($existed_cards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->unfreezeCard($card->source_id);

                    // 只在成功时刷新卡片信息以获取最新状态
                    if ($success) {
                        $card->refresh();
                    }

                    $results[] = [
                        'success' => $success,
                        'message' => $success ? '解冻成功' : '解冻失败',
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '解冻失败: ' . $e->getMessage(),
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                }
            }

            return response()->json([
                'message' => '同步执行完成',
                'success' => true,
                'results' => $results
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($existed_cards as $index => $card)
            {
                CardUnfreeze::dispatch($card->id)->onQueue('cards')->delay(now()->addSeconds($index+1));
            }

            return response()->json([
                'message' => trans('message.task_submitted', [], $this->language),
                'success' => true
            ]);
        }
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
        ]);

        $ids = collect($request->input('ids'));

        $existed_cards = Card::query()->whereIn('id', $ids)->pluck('id');
        foreach ($existed_cards as $index => $card_id)
        {
            CardCancel::dispatch($card_id)->onQueue('cards')->delay(now()->addSeconds($index));
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function transfer_in(Request $request)
    {

    }

    public function transfer_out(Request $request)
    {

    }

    public function sync_all(Request $request)
    {
        TriggerCardSync::dispatch()->onQueue('cards');

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
            'sync' => 'boolean'
        ]);

        $ids = collect($request->input('ids'));
        $sync = $request->input('sync', false);

        $existed_cards = Card::query()->whereIn('id', $ids)->get();

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($existed_cards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $result = $provider->syncCard($card->source_id, true); // 同步包含敏感信息

                    // 将从API获取的最新信息更新到数据库
                    if (!empty($result)) {
                        $card->fill($result);
                        $card->save();
                    }

                    $results[] = [
                        'success' => true,
                        'message' => '同步成功',
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '同步失败: ' . $e->getMessage(),
                        'card' => new CardResource($card)
                    ];
                }
            }

            return response()->json([
                'message' => '同步执行完成',
                'success' => true,
                'results' => $results
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($existed_cards as $index => $card)
            {
                CardSync::dispatch($card->source_id, true)->onQueue('cards')->delay(now()->addSeconds($index));
            }

            return response()->json([
                'message' => trans('message.task_submitted', [], $this->language),
                'success' => true
            ]);
        }
    }

    public function one_shot_total_limit(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
        ]);

        $ids = collect($request->input('ids'));

        $existed_cards = Card::query()->whereIn('id', $ids)->get();
        foreach ($existed_cards as $index => $card)
        {
            CardOneShotTotalLimit::dispatch($card->source_id, 2)->onQueue('cards')->delay(now()->addSeconds($index));
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function one_shot_per_transaction(Request $request)
    {
        $existed_cards = Card::query()->get();
        foreach ($existed_cards as $index => $card)
        {
            CardOneShotPerTrans::dispatch($card->source_id, 1200)->onQueue('cards')->delay(now()->addSeconds($index));
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function set_total_limit(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
            'limits' => 'required|numeric'
        ]);

        $ids = collect($request->input('ids'));
        $limits = $request->input('limits');

        $existed_cards = Card::query()->whereIn('id', $ids)->get();
        foreach ($existed_cards as $index => $card)
        {
            CardOneShotTotalLimit::dispatch($card->source_id, $limits)->onQueue('cards')->delay(now()->addSeconds($index));
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function set_balance(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
            'balance' => 'required|numeric|min:0',
            'sync' => 'boolean'
        ]);

        $ids = collect($request->input('ids'));
        $balance = $request->input('balance');
        $sync = $request->input('sync', false);

        $existed_cards = Card::query()->whereIn('id', $ids)->get();

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($existed_cards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->setBalance($card->source_id, $balance);

                    // 只在成功时刷新卡片信息以获取最新余额
                    if ($success) {
                        $card->refresh();
                    }

                    $results[] = [
                        'success' => $success,
                        'message' => $success ? "余额设置成功: {$balance}" : '余额设置失败',
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '余额设置失败: ' . $e->getMessage(),
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                }
            }

            return response()->json([
                'message' => '同步执行完成',
                'success' => true,
                'results' => $results
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($existed_cards as $index => $card)
            {
                CardSetBalance::dispatch($card->id, $balance)->onQueue('cards')->delay(now()->addSeconds($index));
            }

            return response()->json([
                'message' => trans('message.task_submitted', [], $this->language),
                'success' => true
            ]);
        }
    }

    public function set_single_trans_limit(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|max:40',
            'limits' => 'required|numeric|min:0',
            'sync' => 'boolean'
        ]);

        $ids = collect($request->input('ids'));
        $limits = $request->input('limits');
        $sync = $request->input('sync', false);

        $existed_cards = Card::query()->whereIn('id', $ids)->get();

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($existed_cards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->setPerTransactionLimit($card->source_id, $limits);

                    // 只在成功时刷新卡片信息以获取最新限额
                    if ($success) {
                        $card->refresh();
                    }

                    $results[] = [
                        'success' => $success,
                        'message' => $success ? "单笔限额设置成功: {$limits}" : '单笔限额设置失败',
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '单笔限额设置失败: ' . $e->getMessage(),
                        'card' => new CardResourceWithoutInsight($card)
                    ];
                }
            }

            return response()->json([
                'message' => '同步执行完成',
                'success' => true,
                'results' => $results
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($existed_cards as $index => $card)
            {
                CardSetSingleTransLimit::dispatch($card->id, $limits)->onQueue('cards')->delay(now()->addSeconds($index));
            }

            return response()->json([
                'message' => trans('message.task_submitted', [], $this->language),
                'success' => true
            ]);
        }
    }
}
