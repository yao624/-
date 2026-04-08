<?php

namespace App\Http\Controllers\MetaTask;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskCenterController extends Controller
{
    /**
     * 将前端时间范围参数解析为起止时间。
     * 约定：传入 [start, end]；start/end 为空则返回 null。
     *
     * @param mixed $range
     * @return array{0:mixed,1:mixed}
     */
    private function parseDateRange(mixed $range): array
    {
        if (!is_array($range) || count($range) < 2) {
            return [null, null];
        }

        $start = $range[0] ?? null;
        $end = $range[1] ?? null;

        $start = $start ?: null;
        $end = $end ?: null;

        return [$start, $end];
    }

    private function parseCreatorId(mixed $creator): ?int
    {
        if ($creator === null || $creator === '') {
            return null;
        }

        // 前端可能用固定值（例如：管理员：admin），这里做最小映射
        if ($creator === 'admin') {
            return 1;
        }

        return is_numeric($creator) ? (int) $creator : null;
    }

    private function authOperatorId(): ?int
    {
        $id = Auth::id();
        if ($id === null || $id === '') {
            return null;
        }

        return is_numeric($id) ? (int) $id : null;
    }

    public function index(Request $request)
    {
        $pageNo = (int) $request->input('pageNo', 1);
        $pageSize = (int) $request->input('pageSize', 20);

        $baseQuery = DB::table('meta_task_jobs as j');

        // 与前端任务中心参数一一对应（支持驼峰和下划线两种写法）
        $type = $request->input('type');
        $status = $request->input('status');
        $result = $request->input('result');
        $submitMethod = $request->input('submitMethod', $request->input('submit_method'));
        $channel = $request->input('channel');
        $account = $request->input('account');
        $asset = $request->input('asset');
        $batchId = $request->input('batchId', $request->input('batch_id'));
        $taskId = $request->input('taskId', $request->input('id'));
        $creator = $this->parseCreatorId($request->input('creator'));

        // 时间范围：播放时间/提交时间 -> submit_time，执行时间 -> end_time
        [$dateStart, $dateEnd] = $this->parseDateRange($request->input('dateRange'));
        [$submitStart, $submitEnd] = $this->parseDateRange(
            $request->input('submitTimeRange', $request->input('submit_time_range'))
        );
        [$executeStart, $executeEnd] = $this->parseDateRange(
            $request->input('executeTimeRange', $request->input('end_time_range'))
        );

        // 按条件过滤
        if ($type !== null && $type !== '') {
            $baseQuery->where('j.type', $type);
        }

        if ($status !== null && $status !== '') {
            $baseQuery->where('j.status', $status);
        }

        if ($result !== null && $result !== '') {
            $baseQuery->where('j.result', $result);
        }

        if ($submitMethod !== null && $submitMethod !== '') {
            $baseQuery->where('j.submit_method', $submitMethod);
        }

        if ($channel !== null && $channel !== '') {
            $baseQuery->where('j.channel', $channel);
        }

        if ($account !== null && $account !== '') {
            $baseQuery->where('j.account', $account);
        }

        if ($asset !== null && $asset !== '') {
            $baseQuery->where('j.asset', $asset);
        }

        if ($batchId !== null && $batchId !== '') {
            $baseQuery->where('j.batch_id', $batchId);
        }

        if ($taskId !== null && $taskId !== '') {
            $baseQuery->where('j.id', $taskId);
        }

        if ($creator !== null) {
            $baseQuery->where('j.creator_id', $creator);
        }

        // 播放时间 > 提交时间优先（同样落到 submit_time 字段）
        $submitFrom = $dateStart ?? $submitStart;
        $submitTo = $dateEnd ?? $submitEnd;

        if ($submitFrom !== null) {
            $baseQuery->where('j.submit_time', '>=', $submitFrom);
        }
        if ($submitTo !== null) {
            $baseQuery->where('j.submit_time', '<=', $submitTo);
        }

        if ($executeStart !== null) {
            $baseQuery->where('j.end_time', '>=', $executeStart);
        }
        if ($executeEnd !== null) {
            $baseQuery->where('j.end_time', '<=', $executeEnd);
        }

        $selectColumns = [
            'j.id',
            'j.type',
            'j.status',
            'j.result',
            'j.submit_method',
            'j.submit_time',
            'j.end_time',
            'j.creator_id',
            'j.channel',
            'j.account',
            'j.asset',
            'j.batch_id',
        ];

        $totalCount = (clone $baseQuery)->count('j.id');

        $rows = $baseQuery
            ->select($selectColumns)
            ->orderByDesc('j.updated_at')
            ->orderByDesc('j.id')
            ->forPage($pageNo, $pageSize)
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'account' => $row->account ?? '-',
                'submit_time' => $row->submit_time,
                'end_time' => $row->end_time,
                'submit_method' => $row->submit_method,
                // 前端任务中心页面用固定值：管理员(admin)，这里做最小兼容展示
                'creator' => $row->creator_id === 1 ? 'admin' : ($row->creator_id !== null ? (string) $row->creator_id : '-'),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ]);
    }

    public function show(int $id)
    {
        $job = DB::table('meta_task_jobs')->where('id', $id)->first();
        if (! $job) {
            abort(404, '任务不存在');
        }

        $items = DB::table('meta_task_job_items')
            ->where('job_id', $id)
            ->orderBy('id')
            ->get([
                'id',
                'job_id',
                'target_type',
                'target_id',
                'target_name',
                'status',
                'result',
                'message',
                'started_at',
                'finished_at',
                'retry_count',
                'original_item_id',
                'payload',
            ]);

        $operationLogs = DB::table('meta_task_operation_logs')
            ->where('job_id', $id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'job_id',
                'job_item_id',
                'action_type',
                'operator_id',
                'action_payload',
                'result_status',
                'result_message',
                'created_at',
            ]);

        return response()->json([
            'data' => [
                'job' => [
                    'id' => (string) $job->id,
                    'type' => $job->type,
                    'status' => $job->status,
                    'result' => $job->result,
                    'submit_method' => $job->submit_method,
                    'submit_time' => $job->submit_time,
                    'end_time' => $job->end_time,
                    'creator_id' => $job->creator_id,
                    'channel' => $job->channel,
                    'account' => $job->account,
                    'asset' => $job->asset,
                    'batch_id' => $job->batch_id,
                    'source_job_id' => $job->source_job_id,
                    'payload' => $job->payload,
                ],
                'items' => $items->map(function ($item) {
                    return [
                        'id' => (string) $item->id,
                        'job_id' => (string) $item->job_id,
                        'target_type' => $item->target_type,
                        'target_id' => $item->target_id,
                        'target_name' => $item->target_name,
                        'status' => $item->status,
                        'result' => $item->result,
                        'message' => $item->message,
                        'started_at' => $item->started_at,
                        'finished_at' => $item->finished_at,
                        'retry_count' => (int) $item->retry_count,
                        'original_item_id' => $item->original_item_id !== null ? (string) $item->original_item_id : null,
                        'payload' => $item->payload,
                    ];
                })->values(),
                'operation_logs' => $operationLogs->map(function ($log) {
                    return [
                        'id' => (string) $log->id,
                        'job_id' => (string) $log->job_id,
                        'job_item_id' => $log->job_item_id !== null ? (string) $log->job_item_id : null,
                        'action_type' => $log->action_type,
                        'operator_id' => $log->operator_id,
                        'action_payload' => $log->action_payload,
                        'result_status' => $log->result_status,
                        'result_message' => $log->result_message,
                        'created_at' => $log->created_at,
                    ];
                })->values(),
            ],
        ]);
    }

    public function operationLogs(int $id)
    {
        $job = DB::table('meta_task_jobs')->where('id', $id)->first();
        if (! $job) {
            abort(404, '任务不存在');
        }

        $operationLogs = DB::table('meta_task_operation_logs')
            ->where('job_id', $id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'job_id',
                'job_item_id',
                'action_type',
                'operator_id',
                'action_payload',
                'result_status',
                'result_message',
                'created_at',
            ]);

        return response()->json([
            'data' => $operationLogs->map(function ($log) {
                return [
                    'id' => (string) $log->id,
                    'job_id' => (string) $log->job_id,
                    'job_item_id' => $log->job_item_id !== null ? (string) $log->job_item_id : null,
                    'action_type' => $log->action_type,
                    'operator_id' => $log->operator_id,
                    'action_payload' => $log->action_payload,
                    'result_status' => $log->result_status,
                    'result_message' => $log->result_message,
                    'created_at' => $log->created_at,
                ];
            })->values(),
        ]);
    }

    public function copy(int $id)
    {
        $job = DB::table('meta_task_jobs')->where('id', $id)->first();
        if (! $job) {
            abort(404, '任务不存在');
        }

        $operatorId = $this->authOperatorId();
        $now = now();

        return DB::transaction(function () use ($id, $job, $operatorId, $now) {
            $payloadArr = [];
            if ($job->payload !== null) {
                $decoded = is_array($job->payload) ? $job->payload : json_decode((string) $job->payload, true);
                if (is_array($decoded)) {
                    $payloadArr = $decoded;
                }
            }
            $payloadArr['copied_from_job_id'] = (string) $job->id;
            $newPayloadJson = json_encode($payloadArr, JSON_UNESCAPED_UNICODE);

            $newJobId = DB::table('meta_task_jobs')->insertGetId([
                'type' => $job->type,
                'status' => 'processing',
                'result' => null,
                'submit_method' => $job->submit_method,
                'submit_time' => $now,
                'end_time' => null,
                'creator_id' => $operatorId,
                'channel' => $job->channel,
                'account' => $job->account,
                'asset' => $job->asset,
                'batch_id' => $job->batch_id,
                'source_job_id' => $job->id,
                'payload' => $newPayloadJson,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $oldItems = DB::table('meta_task_job_items')
                ->where('job_id', $job->id)
                ->orderBy('id')
                ->get();

            foreach ($oldItems as $item) {
                $itemPayloadArr = [];
                if ($item->payload !== null) {
                    $decoded = is_array($item->payload) ? $item->payload : json_decode((string) $item->payload, true);
                    if (is_array($decoded)) {
                        $itemPayloadArr = $decoded;
                    }
                }
                $itemPayloadArr['copied_from_item_id'] = (string) $item->id;

                DB::table('meta_task_job_items')->insert([
                    'job_id' => $newJobId,
                    'target_type' => $item->target_type,
                    'target_id' => $item->target_id,
                    'target_name' => $item->target_name,
                    'status' => 'processing',
                    'result' => null,
                    'message' => null,
                    'started_at' => $now,
                    'finished_at' => null,
                    'retry_count' => (int) $item->retry_count + 1,
                    'original_item_id' => $item->id,
                    'payload' => json_encode($itemPayloadArr, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('meta_task_operation_logs')->insert([
                'job_id' => $newJobId,
                'job_item_id' => null,
                'action_type' => 'COPY',
                'operator_id' => $operatorId,
                'action_payload' => json_encode(['source_job_id' => (string) $job->id], JSON_UNESCAPED_UNICODE),
                'result_status' => 'SUCCESS',
                'result_message' => '复制任务并生成草稿',
                'created_at' => $now,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'new_job_id' => (string) $newJobId,
                ],
            ]);
        });
    }

    public function retryFailed(int $id)
    {
        $job = DB::table('meta_task_jobs')->where('id', $id)->first();
        if (! $job) {
            abort(404, '任务不存在');
        }

        $operatorId = $this->authOperatorId();
        $now = now();

        $failedItems = DB::table('meta_task_job_items')
            ->where('job_id', $job->id)
            ->where(function ($q) {
                $q->where('result', 'fail')
                    ->orWhere('status', 'fail');
            })
            ->get();

        if ($failedItems->isEmpty()) {
            abort(422, '该任务没有可重试的失败对象');
        }

        return DB::transaction(function () use ($job, $failedItems, $operatorId, $now) {
            $payloadArr = [];
            if ($job->payload !== null) {
                $decoded = is_array($job->payload) ? $job->payload : json_decode((string) $job->payload, true);
                if (is_array($decoded)) {
                    $payloadArr = $decoded;
                }
            }
            $payloadArr['retry_from_job_id'] = (string) $job->id;
            $payloadArr['retry_item_count'] = (int) $failedItems->count();
            $newPayloadJson = json_encode($payloadArr, JSON_UNESCAPED_UNICODE);

            $newJobId = DB::table('meta_task_jobs')->insertGetId([
                'type' => $job->type,
                'status' => 'processing',
                'result' => null,
                'submit_method' => $job->submit_method,
                'submit_time' => $now,
                'end_time' => null,
                'creator_id' => $operatorId,
                'channel' => $job->channel,
                'account' => $job->account,
                'asset' => $job->asset,
                'batch_id' => $job->batch_id,
                'source_job_id' => $job->id,
                'payload' => $newPayloadJson,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($failedItems as $item) {
                $itemPayloadArr = [];
                if ($item->payload !== null) {
                    $decoded = is_array($item->payload) ? $item->payload : json_decode((string) $item->payload, true);
                    if (is_array($decoded)) {
                        $itemPayloadArr = $decoded;
                    }
                }
                $itemPayloadArr['retry_from_item_id'] = (string) $item->id;

                $newItemId = DB::table('meta_task_job_items')->insertGetId([
                    'job_id' => $newJobId,
                    'target_type' => $item->target_type,
                    'target_id' => $item->target_id,
                    'target_name' => $item->target_name,
                    'status' => 'processing',
                    'result' => null,
                    'message' => null,
                    'started_at' => $now,
                    'finished_at' => null,
                    'retry_count' => (int) $item->retry_count + 1,
                    'original_item_id' => $item->id,
                    'payload' => json_encode($itemPayloadArr, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('meta_task_operation_logs')->insert([
                    'job_id' => $newJobId,
                    'job_item_id' => $newItemId,
                    'action_type' => 'RETRY',
                    'operator_id' => $operatorId,
                    'action_payload' => json_encode(['source_item_id' => (string) $item->id], JSON_UNESCAPED_UNICODE),
                    'result_status' => 'SUCCESS',
                    'result_message' => '已发起重试',
                    'created_at' => $now,
                ]);
            }

            DB::table('meta_task_operation_logs')->insert([
                'job_id' => $newJobId,
                'job_item_id' => null,
                'action_type' => 'RETRY_JOB',
                'operator_id' => $operatorId,
                'action_payload' => json_encode(['source_job_id' => (string) $job->id], JSON_UNESCAPED_UNICODE),
                'result_status' => 'SUCCESS',
                'result_message' => '重试失败对象并生成新任务',
                'created_at' => $now,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'new_job_id' => (string) $newJobId,
                ],
            ]);
        });
    }
}

