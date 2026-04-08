<?php

namespace App\Http\Controllers\MetaFb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MetaFbToolController extends Controller
{
    public function pages(Request $request)
    {
        $request->validate([
            'pageNo' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1|max:200',
            'keyword' => 'sometimes|string|max:200',
            'authorization_status' => 'sometimes|string|in:AUTHORIZED,UNAUTHORIZED',
        ]);

        $pageNo = (int) $request->input('pageNo', 1);
        $pageSize = (int) $request->input('pageSize', 10);
        $keyword = trim((string) $request->input('keyword', ''));
        $authorizationStatus = $request->input('authorization_status');

        $query = DB::table('meta_fb_pages as p')
            ->leftJoin('meta_fb_page_auto_rules as r', 'r.page_id', '=', 'p.id')
            ->when($authorizationStatus, fn ($q) => $q->where('p.authorization_status', $authorizationStatus))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $kw = '%'.$keyword.'%';
                $q->where(function ($sub) use ($kw) {
                    $sub->where('p.public_page_name', 'like', $kw)
                        ->orWhere('p.public_page_id', 'like', $kw);
                });
            });

        $totalCount = (clone $query)->count('p.id');

        $rows = $query
            ->select([
                'p.id',
                'p.public_page_name',
                'p.public_page_id',
                'p.rating',
                'p.authorization_status',
                'p.authorization_time',
                DB::raw("COALESCE(r.status, p.auto_rule_status, 'DISABLED') as auto_rule_status"),
                DB::raw("CASE
                    WHEN COALESCE(r.status, p.auto_rule_status, 'DISABLED') = 'ENABLED'
                     AND COALESCE(r.action, 'HIDE_ALL') IN ('HIDE_ALL', 'HIDE_KEYWORDS') THEN 1
                    ELSE p.auto_hide_comments
                END as auto_hide_comments"),
            ])
            ->orderByDesc('p.updated_at')
            ->forPage($pageNo, $pageSize)
            ->get();

        $pageIds = $rows->pluck('id')->all();
        $bindingRows = [];
        if (!empty($pageIds)) {
            $bindingRows = DB::table('meta_fb_page_bindings')
                ->whereIn('page_id', $pageIds)
                ->where('source_type', 'fb_personal_account')
                ->where('status', 'ACTIVE')
                ->select([
                    'page_id',
                    DB::raw('COUNT(*) as cnt'),
                    DB::raw('MIN(source_name) as first_source_name'),
                ])
                ->groupBy('page_id')
                ->get()
                ->keyBy('page_id');
        }

        $data = $rows->map(function ($row) use ($bindingRows) {
            $binding = $bindingRows[$row->id] ?? null;
            return [
                'id' => (string) $row->id,
                'public_page_name' => $row->public_page_name,
                'public_page_id' => $row->public_page_id,
                'auto_rule_status' => $row->auto_rule_status,
                'auto_hide_comments' => (bool) $row->auto_hide_comments,
                'rating' => $row->rating !== null ? (float) $row->rating : null,
                'fb_personal_account' => $binding?->first_source_name ?? '-',
                'fb_personal_account_count' => (int) ($binding?->cnt ?? 0),
                'authorization_status' => $row->authorization_status,
                'authorization_time' => $row->authorization_time,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ]);
    }

    public function comments(Request $request)
    {
        $request->validate([
            'pageNo' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1|max:200',
            'keyword' => 'sometimes|string|max:200',
            'status' => 'sometimes|string|in:VISIBLE,HIDDEN,DELETED',
            'public_page_id' => 'sometimes|string|max:64',
            'post_id' => 'sometimes|string|max:128',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date',
        ]);

        $pageNo = (int) $request->input('pageNo', 1);
        $pageSize = (int) $request->input('pageSize', 10);
        $keyword = trim((string) $request->input('keyword', ''));
        $status = $request->input('status');
        $publicPageId = $request->input('public_page_id');
        $postId = trim((string) $request->input('post_id', ''));
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');

        $query = DB::table('meta_fb_post_comments as c')
            ->join('meta_fb_pages as p', 'p.id', '=', 'c.page_id')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $kw = '%'.$keyword.'%';
                $q->where('c.comment_content', 'like', $kw);
            })
            ->when($status, fn ($q) => $q->where('c.status', $status))
            ->when($publicPageId, fn ($q) => $q->where('p.public_page_id', $publicPageId))
            ->when($postId !== '', fn ($q) => $q->where('c.post_id', 'like', '%'.$postId.'%'))
            ->when($startTime, fn ($q) => $q->where('c.created_time', '>=', $startTime))
            ->when($endTime, fn ($q) => $q->where('c.created_time', '<=', $endTime));

        $totalCount = (clone $query)->count('c.id');

        $rows = $query->select([
            'c.id',
            'c.comment_content',
            'c.post_id',
            'c.status',
            'c.likes',
            'c.replies',
            'c.created_time',
            'p.public_page_name',
            'p.authorization_status as page_authorization_status',
        ])
            ->orderByDesc('c.created_time')
            ->forPage($pageNo, $pageSize)
            ->get();

        $commentIds = $rows->pluck('id')->all();
        $lastActions = [];
        if (!empty($commentIds)) {
            $actionRows = DB::table('meta_fb_comment_actions as a')
                ->join(DB::raw('(SELECT comment_row_id, MAX(created_at) as max_created_at FROM meta_fb_comment_actions GROUP BY comment_row_id) x'), function ($join) {
                    $join->on('a.comment_row_id', '=', 'x.comment_row_id')
                        ->on('a.created_at', '=', 'x.max_created_at');
                })
                ->whereIn('a.comment_row_id', $commentIds)
                ->select(['a.comment_row_id', 'a.action_type'])
                ->get();
            foreach ($actionRows as $actionRow) {
                $lastActions[$actionRow->comment_row_id] = $actionRow->action_type;
            }
        }

        $data = $rows->map(function ($row) use ($lastActions) {
            return [
                'id' => (string) $row->id,
                'comment_content' => $row->comment_content,
                'post_info' => $row->post_id,
                'operation_type' => $lastActions[$row->id] ?? '-',
                'status' => $row->status,
                'likes' => (int) $row->likes,
                'replies' => (int) $row->replies,
                'public_page' => $row->public_page_name,
                'page_authorization_status' => $row->page_authorization_status,
                'created_time' => $row->created_time,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ]);
    }

    public function keywordPacks(Request $request)
    {
        $request->validate([
            'pageNo' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1|max:200',
            'keyword' => 'sometimes|string|max:200',
        ]);

        $pageNo = (int) $request->input('pageNo', 1);
        $pageSize = (int) $request->input('pageSize', 10);
        $keyword = trim((string) $request->input('keyword', ''));

        $query = DB::table('meta_fb_keyword_packs as kp')
            ->when($keyword !== '', fn ($q) => $q->where('kp.pack_name', 'like', '%'.$keyword.'%'));

        $totalCount = (clone $query)->count('kp.id');

        $rows = $query
            ->leftJoin('meta_fb_keywords as k', 'k.pack_id', '=', 'kp.id')
            ->groupBy('kp.id', 'kp.pack_name', 'kp.created_time')
            ->select([
                'kp.id',
                'kp.pack_name',
                'kp.created_time',
                DB::raw('COUNT(k.id) as keyword_count'),
            ])
            ->orderByDesc('kp.created_time')
            ->forPage($pageNo, $pageSize)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (string) $row->id,
            'pack_name' => $row->pack_name,
            'keyword_count' => (int) $row->keyword_count,
            'created_time' => $row->created_time,
        ])->values();

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ]);
    }

    public function pageOptions()
    {
        $data = DB::table('meta_fb_pages')
            ->orderBy('public_page_name')
            ->get(['public_page_id', 'public_page_name'])
            ->map(fn ($row) => ['label' => $row->public_page_name, 'value' => $row->public_page_id])
            ->values();

        return response()->json(['data' => $data]);
    }

    public function getAutoRule(string $id)
    {
        $page = DB::table('meta_fb_pages')->where('id', $id)->first();
        if (!$page) {
            abort(404, '主页不存在');
        }

        $rule = DB::table('meta_fb_page_auto_rules')->where('page_id', $id)->first();

        return response()->json([
            'data' => [
                'page_id' => (string) $id,
                'status' => $rule?->status ?? 'DISABLED',
                'action' => $rule?->action ?? 'HIDE_ALL',
                'keyword_pack_id' => $rule?->keyword_pack_id ? (string) $rule->keyword_pack_id : null,
            ],
        ]);
    }

    public function saveAutoRule(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|string|in:ENABLED,DISABLED',
            'action' => 'required|string|in:HIDE_ALL,HIDE_KEYWORDS',
            'keyword_pack_id' => 'nullable|integer|min:1',
        ]);

        $page = DB::table('meta_fb_pages')->where('id', $id)->first();
        if (!$page) {
            abort(404, '主页不存在');
        }

        $now = now();
        $existing = DB::table('meta_fb_page_auto_rules')->where('page_id', $id)->first();

        $payload = [
            'status' => $request->input('status'),
            'action' => $request->input('action'),
            'keyword_pack_id' => $request->input('keyword_pack_id'),
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('meta_fb_page_auto_rules')
                ->where('page_id', $id)
                ->update($payload);
        } else {
            DB::table('meta_fb_page_auto_rules')->insert(array_merge($payload, [
                'page_id' => $id,
                'created_at' => $now,
            ]));
        }

        DB::table('meta_fb_pages')->where('id', $id)->update([
            'auto_rule_status' => $request->input('status'),
            'auto_hide_comments' => $request->input('status') === 'ENABLED' ? 1 : 0,
            'updated_at' => $now,
        ]);

        return response()->json(['success' => true]);
    }

    public function batchSyncLatest(Request $request)
    {
        $request->validate([
            'page_ids' => 'sometimes|array',
            'page_ids.*' => 'integer',
        ]);

        $pageIds = collect($request->input('page_ids', []))
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $query = DB::table('meta_fb_pages');
        if ($pageIds->isNotEmpty()) {
            $query->whereIn('id', $pageIds->all());
        }

        $affected = $query->update([
            'last_sync_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'affected_count' => $affected]);
    }

    public function batchHideComments(Request $request)
    {
        $request->validate([
            'comment_ids' => 'required|array|min:1',
            'comment_ids.*' => 'integer',
        ]);

        $commentIds = collect($request->input('comment_ids'))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $now = now();
        $affected = DB::table('meta_fb_post_comments')
            ->whereIn('id', $commentIds->all())
            ->update([
                'status' => 'HIDDEN',
                'updated_at' => $now,
            ]);

        $operatorId = Auth::id();
        $rows = $commentIds->map(fn ($id) => [
            'comment_row_id' => $id,
            'action_type' => 'HIDE',
            'action_payload' => json_encode(['from_batch' => true], JSON_UNESCAPED_UNICODE),
            'operator_id' => $operatorId,
            'result_status' => 'SUCCESS',
            'result_message' => '批量隐藏',
            'created_at' => $now,
        ])->all();
        DB::table('meta_fb_comment_actions')->insert($rows);

        return response()->json(['success' => true, 'affected_count' => $affected]);
    }

    public function batchDeleteComments(Request $request)
    {
        $request->validate([
            'comment_ids' => 'required|array|min:1',
            'comment_ids.*' => 'integer',
        ]);

        $commentIds = collect($request->input('comment_ids'))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $now = now();
        $affected = DB::table('meta_fb_post_comments')
            ->whereIn('id', $commentIds->all())
            ->update([
                'status' => 'DELETED',
                'updated_at' => $now,
            ]);

        $operatorId = Auth::id();
        $rows = $commentIds->map(fn ($id) => [
            'comment_row_id' => $id,
            'action_type' => 'DELETE',
            'action_payload' => json_encode(['from_batch' => true], JSON_UNESCAPED_UNICODE),
            'operator_id' => $operatorId,
            'result_status' => 'SUCCESS',
            'result_message' => '批量删除',
            'created_at' => $now,
        ])->all();
        DB::table('meta_fb_comment_actions')->insert($rows);

        return response()->json(['success' => true, 'affected_count' => $affected]);
    }

    public function batchDeleteKeywordPacks(Request $request)
    {
        $request->validate([
            'pack_ids' => 'required|array|min:1',
            'pack_ids.*' => 'integer',
        ]);

        $packIds = collect($request->input('pack_ids'))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $affected = DB::table('meta_fb_keyword_packs')
            ->whereIn('id', $packIds->all())
            ->delete();

        return response()->json(['success' => true, 'affected_count' => $affected]);
    }

    public function createKeywordPack(Request $request)
    {
        $request->validate([
            'pack_name' => 'required|string|max:128',
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required|string|max:255',
            'status' => 'sometimes|string|in:ENABLED,DISABLED',
        ]);

        $keywords = collect($request->input('keywords', []))
            ->map(fn ($k) => trim((string) $k))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($keywords)) {
            abort(422, '关键词不能为空');
        }

        $status = (string) $request->input('status', 'ENABLED');
        $operatorId = Auth::id();
        $now = now();

        $packId = DB::transaction(function () use ($request, $keywords, $status, $operatorId, $now) {
            $packId = DB::table('meta_fb_keyword_packs')->insertGetId([
                'pack_name' => $request->input('pack_name'),
                'status' => $status,
                'created_by' => $operatorId,
                'created_time' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rows = collect($keywords)->values()->map(function ($kw, $idx) use ($packId, $now) {
                return [
                    'pack_id' => $packId,
                    'keyword' => $kw,
                    'match_type' => 'CONTAINS',
                    'priority' => 100 + (int) $idx * 10,
                    'reply_template' => null,
                    'enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::table('meta_fb_keywords')->insert($rows);

            return $packId;
        });

        return response()->json(['success' => true, 'id' => (string) $packId], 201);
    }

    public function updateKeywordPack(Request $request, int $id)
    {
        $request->validate([
            'pack_name' => 'sometimes|string|max:128',
            'keywords' => 'sometimes|array|min:1',
            'keywords.*' => 'required|string|max:255',
            'status' => 'sometimes|string|in:ENABLED,DISABLED',
        ]);

        $pack = DB::table('meta_fb_keyword_packs')->where('id', $id)->first();
        if (! $pack) {
            abort(404, '关键词包不存在');
        }

        $now = now();
        $operatorId = Auth::id();

        DB::transaction(function () use ($request, $id, $pack, $now, $operatorId) {
            $payload = [];
            if ($request->has('pack_name')) {
                $payload['pack_name'] = $request->input('pack_name');
            }
            if ($request->has('status')) {
                $payload['status'] = $request->input('status');
            }

            if (! empty($payload)) {
                $payload['updated_at'] = $now;
                DB::table('meta_fb_keyword_packs')->where('id', $id)->update($payload);
            }

            if ($request->has('keywords')) {
                $keywords = collect($request->input('keywords', []))
                    ->map(fn ($k) => trim((string) $k))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($keywords)) {
                    abort(422, '关键词不能为空');
                }

                DB::table('meta_fb_keywords')->where('pack_id', $id)->delete();

                $rows = collect($keywords)->values()->map(function ($kw, $idx) use ($id, $now) {
                    return [
                        'pack_id' => $id,
                        'keyword' => $kw,
                        'match_type' => 'CONTAINS',
                        'priority' => 100 + (int) $idx * 10,
                        'reply_template' => null,
                        'enabled' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                DB::table('meta_fb_keywords')->insert($rows);
            }
        });

        return response()->json(['success' => true]);
    }

    public function deleteKeywordPack(int $id)
    {
        $pack = DB::table('meta_fb_keyword_packs')->where('id', $id)->first();
        if (! $pack) {
            abort(404, '关键词包不存在');
        }

        DB::table('meta_fb_keyword_packs')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function getKeywordPackKeywords(int $id)
    {
        $pack = DB::table('meta_fb_keyword_packs')->where('id', $id)->first();
        if (! $pack) {
            abort(404, '关键词包不存在');
        }

        $keywords = DB::table('meta_fb_keywords')
            ->where('pack_id', $id)
            ->orderBy('priority')
            ->orderBy('id')
            ->get(['keyword'])
            ->pluck('keyword')
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'pack_id' => (string) $id,
                'keywords' => $keywords,
            ],
        ]);
    }

    public function batchCommentAction(Request $request)
    {
        $request->validate([
            'comment_ids' => 'required|array|min:1',
            'comment_ids.*' => 'integer',
            'action' => 'required|string|in:REPLY,HIDE,UNHIDE,LIKE,UNLIKE,DELETE',
            'reply_text' => 'sometimes|string|max:1000',
        ]);

        $commentIds = collect($request->input('comment_ids'))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $action = (string) $request->input('action');
        $now = now();
        $affected = 0;

        if ($action === 'HIDE') {
            $affected = DB::table('meta_fb_post_comments')
                ->whereIn('id', $commentIds->all())
                ->update(['status' => 'HIDDEN', 'updated_at' => $now]);
        } elseif ($action === 'UNHIDE') {
            $affected = DB::table('meta_fb_post_comments')
                ->whereIn('id', $commentIds->all())
                ->update(['status' => 'VISIBLE', 'updated_at' => $now]);
        } elseif ($action === 'DELETE') {
            $affected = DB::table('meta_fb_post_comments')
                ->whereIn('id', $commentIds->all())
                ->update(['status' => 'DELETED', 'updated_at' => $now]);
        } else {
            // REPLY / LIKE / UNLIKE 先记录动作，后续可替换为真实 Facebook API 调用
            $affected = $commentIds->count();
        }

        $operatorId = Auth::id();
        $payload = ['from_batch' => true];
        if ($action === 'REPLY' && $request->filled('reply_text')) {
            $payload['reply_text'] = $request->input('reply_text');
        }

        $rows = $commentIds->map(fn ($id) => [
            'comment_row_id' => $id,
            'action_type' => $action,
            'action_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'operator_id' => $operatorId,
            'result_status' => 'SUCCESS',
            'result_message' => '批量操作：'.$action,
            'created_at' => $now,
        ])->all();
        DB::table('meta_fb_comment_actions')->insert($rows);

        return response()->json(['success' => true, 'affected_count' => $affected]);
    }
}

