<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardResource;
use App\Http\Resources\FbAdAccountResource;
use App\Jobs\CardFreeze;
use App\Jobs\CardUnfreeze;
use App\Models\Card;
use App\Models\FbAdAccount;
use App\Services\CardProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardFbAdAccountController extends BaseController
{
    /**
     * 关联卡片到Facebook广告账户
     * 支持设置默认卡片
     */
    public function attachCards(Request $request)
    {
        $request->validate([
            'fb_ad_account_id' => 'required|exists:fb_ad_accounts,id',
            'card_ids' => 'required|array',
            'card_ids.*' => 'required|exists:cards,id',
            'default_card_id' => 'nullable|exists:cards,id'
        ]);

        $fbAdAccount = FbAdAccount::find($request->fb_ad_account_id);
        $cardIds = $request->card_ids;
        $defaultCardId = $request->default_card_id;

        // 验证default_card_id是否在card_ids中
        if ($defaultCardId && !in_array($defaultCardId, $cardIds)) {
            return response()->json([
                'message' => '默认卡片必须在关联的卡片列表中',
                'success' => false
            ], 400);
        }

        DB::transaction(function() use ($fbAdAccount, $cardIds, $defaultCardId) {
            // 如果设置了新的默认卡片，先移除旧的默认卡片标识
            if ($defaultCardId) {
                $fbAdAccount->cards()->updateExistingPivot(
                    $fbAdAccount->cards()->pluck('cards.id')->toArray(),
                    ['is_default' => false]
                );
            }

            // 关联卡片
            foreach ($cardIds as $cardId) {
                $isDefault = ($cardId == $defaultCardId);

                // 如果已存在关联，更新pivot数据
                if ($fbAdAccount->cards()->where('card_id', $cardId)->exists()) {
                    $fbAdAccount->cards()->updateExistingPivot($cardId, ['is_default' => $isDefault]);
                } else {
                    // 创建新的关联
                    $fbAdAccount->cards()->attach($cardId, ['is_default' => $isDefault]);
                }
            }
        });

        Log::info("Cards attached to FB Ad Account", [
            'fb_ad_account_id' => $fbAdAccount->id,
            'card_ids' => $cardIds,
            'default_card_id' => $defaultCardId
        ]);

        return response()->json([
            'message' => '卡片关联成功',
            'success' => true,
            'data' => [
                'fb_ad_account' => new FbAdAccountResource($fbAdAccount->load('cards')),
                'attached_cards' => CardResource::collection(Card::whereIn('id', $cardIds)->get())
            ]
        ]);
    }

    /**
     * 解除卡片与Facebook广告账户的关联
     */
    public function detachCards(Request $request)
    {
        $request->validate([
            'fb_ad_account_id' => 'required|exists:fb_ad_accounts,id',
            'card_ids' => 'required|array',
            'card_ids.*' => 'required|exists:cards,id'
        ]);

        $fbAdAccount = FbAdAccount::find($request->fb_ad_account_id);
        $cardIds = $request->card_ids;

        // 解除关联
        $fbAdAccount->cards()->detach($cardIds);

        Log::info("Cards detached from FB Ad Account", [
            'fb_ad_account_id' => $fbAdAccount->id,
            'card_ids' => $cardIds
        ]);

        return response()->json([
            'message' => '卡片解除关联成功',
            'success' => true,
            'data' => [
                'fb_ad_account' => new FbAdAccountResource($fbAdAccount->load('cards')),
                'detached_cards' => CardResource::collection(Card::whereIn('id', $cardIds)->get())
            ]
        ]);
    }

    /**
     * 设置Facebook广告账户的默认卡片
     */
    public function setDefaultCard(Request $request)
    {
        $request->validate([
            'fb_ad_account_id' => 'required|exists:fb_ad_accounts,id',
            'card_id' => 'required|exists:cards,id'
        ]);

        $fbAdAccount = FbAdAccount::find($request->fb_ad_account_id);
        $cardId = $request->card_id;

        // 检查卡片是否已关联到该广告账户
        if (!$fbAdAccount->cards()->where('card_id', $cardId)->exists()) {
            return response()->json([
                'message' => '卡片必须先关联到该广告账户才能设置为默认卡片',
                'success' => false
            ], 400);
        }

        DB::transaction(function() use ($fbAdAccount, $cardId) {
            // 移除所有卡片的默认标识
            $fbAdAccount->cards()->updateExistingPivot(
                $fbAdAccount->cards()->pluck('cards.id')->toArray(),
                ['is_default' => false]
            );

            // 设置新的默认卡片
            $fbAdAccount->cards()->updateExistingPivot($cardId, ['is_default' => true]);
        });

        Log::info("Default card set for FB Ad Account", [
            'fb_ad_account_id' => $fbAdAccount->id,
            'card_id' => $cardId
        ]);

        return response()->json([
            'message' => '默认卡片设置成功',
            'success' => true,
            'data' => [
                'fb_ad_account' => new FbAdAccountResource($fbAdAccount->load('cards')),
                'default_card' => new CardResource(Card::find($cardId))
            ]
        ]);
    }

    /**
     * 获取Facebook广告账户关联的卡片列表
     */
    public function getAccountCards(Request $request)
    {
        $request->validate([
            'fb_ad_account_id' => 'required|exists:fb_ad_accounts,id'
        ]);

        $fbAdAccount = FbAdAccount::with('cards')->find($request->fb_ad_account_id);

        return response()->json([
            'success' => true,
            'data' => [
                'fb_ad_account' => new FbAdAccountResource($fbAdAccount),
                'cards' => CardResource::collection($fbAdAccount->cards),
                'default_card' => $fbAdAccount->defaultCard() ? new CardResource($fbAdAccount->defaultCard()) : null
            ]
        ]);
    }

    /**
     * 批量冻结Facebook广告账户的卡片
     */
    public function freezeAccountCards(Request $request)
    {
        $request->validate([
            'fb_ad_account_ids' => 'required|array',
            'fb_ad_account_ids.*' => 'required|exists:fb_ad_accounts,id',
            'sync' => 'boolean'
        ]);

        $fbAdAccountIds = $request->fb_ad_account_ids;
        $sync = $request->input('sync', false);
        $fbAdAccounts = FbAdAccount::with('cards')->whereIn('id', $fbAdAccountIds)->get();

        $allCards = collect();
        foreach ($fbAdAccounts as $fbAdAccount) {
            $allCards = $allCards->merge($fbAdAccount->cards);
        }

        // 去重
        $uniqueCards = $allCards->unique('id');

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($uniqueCards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->freezeCard($card->source_id);

                    // 刷新卡片信息以获取最新状态
                    $card->refresh();

                    $results[] = [
                        'success' => $success,
                        'message' => $success ? '冻结成功' : '冻结失败',
                        'card' => new CardResource($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '冻结失败: ' . $e->getMessage(),
                        'card' => new CardResource($card)
                    ];
                }
            }

            Log::info("Batch freeze cards for FB Ad Accounts (sync)", [
                'fb_ad_account_ids' => $fbAdAccountIds,
                'card_count' => $uniqueCards->count(),
                'success_count' => collect($results)->where('success', true)->count()
            ]);

            return response()->json([
                'message' => '同步批量冻结执行完成',
                'success' => true,
                'data' => [
                    'fb_ad_accounts_count' => $fbAdAccounts->count(),
                    'cards_count' => $uniqueCards->count(),
                    'results' => $results
                ]
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($uniqueCards as $index => $card) {
                CardFreeze::dispatch($card->id)->onQueue('cards')->delay(now()->addSeconds($index + 1));
            }

            Log::info("Batch freeze cards for FB Ad Accounts (async)", [
                'fb_ad_account_ids' => $fbAdAccountIds,
                'card_count' => $uniqueCards->count()
            ]);

            return response()->json([
                'message' => '批量冻结任务已提交',
                'success' => true,
                'data' => [
                    'fb_ad_accounts_count' => $fbAdAccounts->count(),
                    'cards_count' => $uniqueCards->count(),
                    'cards' => CardResource::collection($uniqueCards)
                ]
            ]);
        }
    }

    /**
     * 批量解冻Facebook广告账户的卡片
     */
    public function unfreezeAccountCards(Request $request)
    {
        $request->validate([
            'fb_ad_account_ids' => 'required|array',
            'fb_ad_account_ids.*' => 'required|exists:fb_ad_accounts,id',
            'sync' => 'boolean'
        ]);

        $fbAdAccountIds = $request->fb_ad_account_ids;
        $sync = $request->input('sync', false);
        $fbAdAccounts = FbAdAccount::with('cards')->whereIn('id', $fbAdAccountIds)->get();

        $allCards = collect();
        foreach ($fbAdAccounts as $fbAdAccount) {
            $allCards = $allCards->merge($fbAdAccount->cards);
        }

        // 去重
        $uniqueCards = $allCards->unique('id');

        if ($sync) {
            // 同步执行
            $results = [];
            $cardProviderService = app(CardProviderService::class);

            foreach ($uniqueCards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->unfreezeCard($card->source_id);

                    // 刷新卡片信息以获取最新状态
                    $card->refresh();

                    $results[] = [
                        'success' => $success,
                        'message' => $success ? '解冻成功' : '解冻失败',
                        'card' => new CardResource($card)
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'message' => '解冻失败: ' . $e->getMessage(),
                        'card' => new CardResource($card)
                    ];
                }
            }

            Log::info("Batch unfreeze cards for FB Ad Accounts (sync)", [
                'fb_ad_account_ids' => $fbAdAccountIds,
                'card_count' => $uniqueCards->count(),
                'success_count' => collect($results)->where('success', true)->count()
            ]);

            return response()->json([
                'message' => '同步批量解冻执行完成',
                'success' => true,
                'data' => [
                    'fb_ad_accounts_count' => $fbAdAccounts->count(),
                    'cards_count' => $uniqueCards->count(),
                    'results' => $results
                ]
            ]);
        } else {
            // 异步执行（原逻辑）
            foreach ($uniqueCards as $index => $card) {
                CardUnfreeze::dispatch($card->id)->onQueue('cards')->delay(now()->addSeconds($index + 1));
            }

            Log::info("Batch unfreeze cards for FB Ad Accounts (async)", [
                'fb_ad_account_ids' => $fbAdAccountIds,
                'card_count' => $uniqueCards->count()
            ]);

            return response()->json([
                'message' => '批量解冻任务已提交',
                'success' => true,
                'data' => [
                    'fb_ad_accounts_count' => $fbAdAccounts->count(),
                    'cards_count' => $uniqueCards->count(),
                    'cards' => CardResource::collection($uniqueCards)
                ]
            ]);
        }
    }

    /**
     * 获取卡片关联的Facebook广告账户列表
     */
    public function getCardAccounts(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:cards,id'
        ]);

        $card = Card::with('fbAdAccounts')->find($request->card_id);

        return response()->json([
            'success' => true,
            'data' => [
                'card' => new CardResource($card),
                'fb_ad_accounts' => FbAdAccountResource::collection($card->fbAdAccounts),
                'default_accounts' => FbAdAccountResource::collection($card->defaultFbAdAccounts)
            ]
        ]);
    }
}
