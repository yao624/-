<?php

namespace App\Http\Controllers\MetaTask;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ScheduledReportController extends Controller
{
    private function authUserId(): ?int
    {
        $id = Auth::id();
        if ($id === null || $id === '') {
            return null;
        }
        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * 定时报表列表
     */
    public function index(Request $request)
    {
        $pageNo = (int) $request->input('pageNo', 1);
        $pageSize = (int) $request->input('pageSize', 20);

        $query = DB::table('meta_scheduled_reports');

        $name = $request->input('reportName', $request->input('name'));
        $status = $request->input('status');
        $creator = $request->input('creator');

        if ($name !== null && $name !== '') {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($status !== null && $status !== '') {
            // 前端用 ENABLED/DISABLED，表里为 enabled/disabled
            $statusMap = [
                'ENABLED' => 'enabled',
                'DISABLED' => 'disabled',
            ];
            $dbStatus = $statusMap[$status] ?? $status;
            $query->where('status', $dbStatus);
        }

        if ($creator !== null && $creator !== '') {
            if (is_numeric($creator)) {
                $query->where('creator_id', (int) $creator);
            }
        }

        $totalCount = (clone $query)->count('id');

        $rows = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->forPage($pageNo, $pageSize)
            ->get();

        // 预取卡片和最近运行记录
        $reportIds = $rows->pluck('id')->all();

        $cardsByReport = [];
        if (! empty($reportIds)) {
            $cards = DB::table('meta_scheduled_report_cards')
                ->whereIn('report_id', $reportIds)
                ->orderBy('position')
                ->get([
                    'id',
                    'report_id',
                    'title',
                    'report_type',
                    'channel',
                    'data_time_mode',
                    'date_range_start',
                    'date_range_end',
                ]);
            foreach ($cards as $card) {
                $cardsByReport[$card->report_id][] = $card;
            }
        }

        $runsByReport = [];
        if (! empty($reportIds)) {
            $runs = DB::table('meta_scheduled_report_runs')
                ->whereIn('report_id', $reportIds)
                ->orderByDesc('started_at')
                ->orderByDesc('id')
                ->get([
                    'report_id',
                    'status',
                    'started_at',
                    'finished_at',
                ]);
            foreach ($runs as $run) {
                if (! isset($runsByReport[$run->report_id])) {
                    $runsByReport[$run->report_id] = $run;
                }
            }
        }

        $data = $rows->map(function ($row) use ($cardsByReport, $runsByReport) {
            $cards = $cardsByReport[$row->id] ?? [];
            $firstCard = $cards[0] ?? null;

            // 报表内容：简单用「卡片标题列表」拼接
            $reportContent = null;
            if (! empty($cards)) {
                $titles = array_map(fn($c) => $c->title, $cards);
                $reportContent = implode('；', array_slice($titles, 0, 3));
            }

            // 数据时间：取第一张卡片的时间范围
            $pushTime = null;
            if ($firstCard && $firstCard->date_range_start && $firstCard->date_range_end) {
                $pushTime = sprintf('%s 至 %s', $firstCard->date_range_start, $firstCard->date_range_end);
            }

            // 频率展示文案
            $scheduleType = $row->schedule_type;
            $timeOfDay = $row->run_time_of_day;
            $weekday = $row->run_weekday;
            $cron = $row->cron_expression;
            $frequency = null;
            if ($scheduleType === 'daily') {
                $frequency = '每日 ' . ($timeOfDay ?? '');
            } elseif ($scheduleType === 'weekly') {
                $weekdayText = $weekday ? '周' . $weekday : '';
                $frequency = trim($weekdayText . ' ' . ($timeOfDay ?? ''));
            } elseif ($scheduleType === 'custom') {
                $frequency = '自定义 ' . ($cron ?? '');
            }

            // 推送状态：根据最近一次运行记录的 status 简单映射
            $lastRun = $runsByReport[$row->id] ?? null;
            $pushStatus = '未开始';
            if ($lastRun) {
                if ($lastRun->status === 'running' || $lastRun->status === 'queued') {
                    $pushStatus = '生成中';
                } elseif ($lastRun->status === 'success') {
                    $pushStatus = '已生成';
                } elseif ($lastRun->status === 'fail') {
                    $pushStatus = '生成失败';
                }
            }

            return [
                'id' => (string) $row->id,
                'status' => $row->status === 'enabled' ? 'ENABLED' : 'DISABLED',
                'report_name' => $row->name,
                'report_content' => $reportContent,
                'push_status' => $pushStatus,
                'frequency' => $frequency,
                'push_time' => $pushTime,
                'created_time' => $row->created_at,
                'creator' => (string) ($row->creator_id ?? ''),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ]);
    }

    /**
     * 创建定时报表（含一组卡片）
     */
    public function store(Request $request)
    {
        $userId = $this->authUserId();
        if (! $userId) {
            abort(401, '未登录');
        }

        $name = (string) $request->input('name', '');
        if ($name === '') {
            abort(422, '报表名称不能为空');
        }

        $frequencyType = $request->input('frequencyType', 'daily');
        $time = $request->input('time');
        $email = $request->input('email');
        $dataCards = $request->input('dataCards', []);

        $now = now();

        return DB::transaction(function () use ($userId, $name, $frequencyType, $time, $email, $dataCards, $now) {
            $scheduleType = $frequencyType === 'weekly' ? 'weekly' : ($frequencyType === 'custom' ? 'custom' : 'daily');

            $reportId = DB::table('meta_scheduled_reports')->insertGetId([
                'company_id' => 0,
                'name' => $name,
                'description' => null,
                'status' => 'enabled',
                'schedule_type' => $scheduleType,
                'cron_expression' => $scheduleType === 'custom' ? ($time ?? null) : null,
                'run_time_of_day' => $scheduleType !== 'custom' ? ($time ?? null) : null,
                'run_weekday' => null,
                'timezone' => 'Asia/Shanghai',
                'max_card_count' => 5,
                'email_to' => (string) $email,
                'email_cc' => null,
                'last_run_time' => null,
                'next_run_time' => null,
                'last_run_status' => null,
                'creator_id' => $userId,
                'department_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $position = 1;
            foreach ($dataCards as $card) {
                $title = (string) ($card['title'] ?? '');
                if ($title === '') {
                    $title = '数据卡片' . $position;
                }
                $reportType = $card['reportType'] ?? 'comprehensive';
                $channel = $card['level'] ?? 'total';
                $dateRangePreset = $card['dateRange'] ?? null;

                $filters = [
                    'dimensions' => $card['selectedDimensions'] ?? [],
                    'metricFilterEnabled' => $card['metricFilterEnabled'] ?? false,
                    'metricRows' => $card['metricRows'] ?? [],
                    'filterOptions' => $card['filterOptions'] ?? [],
                    'metrics' => $card['selectedMetrics'] ?? [],
                    'date_preset' => $dateRangePreset,
                ];

                DB::table('meta_scheduled_report_cards')->insert([
                    'report_id' => $reportId,
                    'title' => $title,
                    'report_type' => $reportType === 'material' ? 'material' : 'summary',
                    'channel' => $channel === 'total' ? 'summary' : $channel,
                    'data_time_mode' => 'single',
                    'date_range_start' => null,
                    'date_range_end' => null,
                    'compare_date_range_start' => null,
                    'compare_date_range_end' => null,
                    'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
                    'sort_field' => $card['sortBy'] ?? null,
                    'sort_direction' => $card['sortOrder'] ?? null,
                    'limit_rows' => (int) ($card['limit'] ?? 10),
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $position++;
            }

            return response()->json([
                'data' => [
                    'id' => (string) $reportId,
                ],
            ]);
        });
    }

    /**
     * 获取报表详情（含卡片）
     */
    public function show(int $id)
    {
        $report = DB::table('meta_scheduled_reports')->where('id', $id)->first();
        if (! $report) {
            abort(404, '定时报表不存在');
        }

        $cards = DB::table('meta_scheduled_report_cards')
            ->where('report_id', $id)
            ->orderBy('position')
            ->get();

        $dataCards = $cards->map(function ($card) {
            $filters = [];
            if ($card->filters !== null) {
                $decoded = is_array($card->filters) ? $card->filters : json_decode((string) $card->filters, true);
                if (is_array($decoded)) {
                    $filters = $decoded;
                }
            }

            return [
                'id' => (string) $card->id,
                'title' => $card->title,
                'reportType' => $card->report_type === 'material' ? 'material' : 'comprehensive',
                'level' => $card->channel === 'summary' ? 'total' : $card->channel,
                'dateRange' => $filters['date_preset'] ?? null,
                'compare' => $card->data_time_mode === 'compare',
                'selectedDimensions' => $filters['dimensions'] ?? ['日期'],
                'selectedMetrics' => $filters['metrics'] ?? [],
                'sortBy' => $card->sort_field,
                'sortOrder' => $card->sort_direction,
                'limit' => (int) $card->limit_rows,
                'filterOptions' => $filters['filterOptions'] ?? [],
                'metricFilterEnabled' => $filters['metricFilterEnabled'] ?? false,
                'metricRows' => $filters['metricRows'] ?? [],
            ];
        })->values();

        return response()->json([
            'data' => [
                'id' => (string) $report->id,
                'name' => $report->name,
                'frequencyType' => $report->schedule_type,
                'time' => $report->run_time_of_day,
                'email' => $report->email_to,
                'status' => $report->status,
                'dataCards' => $dataCards,
            ],
        ]);
    }

    /**
     * 更新报表（及其卡片，先删后插）
     */
    public function update(int $id, Request $request)
    {
        $report = DB::table('meta_scheduled_reports')->where('id', $id)->first();
        if (! $report) {
            abort(404, '定时报表不存在');
        }

        $name = (string) $request->input('name', $report->name);
        if ($name === '') {
            abort(422, '报表名称不能为空');
        }

        $frequencyType = $request->input('frequencyType', $report->schedule_type);
        $time = $request->input('time', $report->run_time_of_day);
        $email = $request->input('email', $report->email_to);
        $status = $request->input('status', $report->status);
        $dataCards = $request->input('dataCards', null);

        $now = now();

        return DB::transaction(function () use ($id, $name, $frequencyType, $time, $email, $status, $dataCards, $now) {
            $scheduleType = $frequencyType === 'weekly' ? 'weekly' : ($frequencyType === 'custom' ? 'custom' : 'daily');

            DB::table('meta_scheduled_reports')
                ->where('id', $id)
                ->update([
                    'name' => $name,
                    'status' => in_array($status, ['enabled', 'disabled'], true) ? $status : 'enabled',
                    'schedule_type' => $scheduleType,
                    'cron_expression' => $scheduleType === 'custom' ? ($time ?? null) : null,
                    'run_time_of_day' => $scheduleType !== 'custom' ? ($time ?? null) : null,
                    'email_to' => (string) $email,
                    'updated_at' => $now,
                ]);

            if (is_array($dataCards)) {
                DB::table('meta_scheduled_report_cards')
                    ->where('report_id', $id)
                    ->delete();

                $position = 1;
                foreach ($dataCards as $card) {
                    $title = (string) ($card['title'] ?? '');
                    if ($title === '') {
                        $title = '数据卡片' . $position;
                    }
                    $reportType = $card['reportType'] ?? 'comprehensive';
                    $channel = $card['level'] ?? 'total';
                    $dateRangePreset = $card['dateRange'] ?? null;

                    $filters = [
                        'dimensions' => $card['selectedDimensions'] ?? [],
                        'metricFilterEnabled' => $card['metricFilterEnabled'] ?? false,
                        'metricRows' => $card['metricRows'] ?? [],
                        'filterOptions' => $card['filterOptions'] ?? [],
                        'metrics' => $card['selectedMetrics'] ?? [],
                        'date_preset' => $dateRangePreset,
                    ];

                    DB::table('meta_scheduled_report_cards')->insert([
                        'report_id' => $id,
                        'title' => $title,
                        'report_type' => $reportType === 'material' ? 'material' : 'summary',
                        'channel' => $channel === 'total' ? 'summary' : $channel,
                        'data_time_mode' => 'single',
                        'date_range_start' => null,
                        'date_range_end' => null,
                        'compare_date_range_start' => null,
                        'compare_date_range_end' => null,
                        'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
                        'sort_field' => $card['sortBy'] ?? null,
                        'sort_direction' => $card['sortOrder'] ?? null,
                        'limit_rows' => (int) ($card['limit'] ?? 10),
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $position++;
                }
            }

            return response()->json([
                'data' => [
                    'id' => (string) $id,
                ],
            ]);
        });
    }

    /**
     * 删除报表（级联删除卡片和运行记录由外键处理）
     */
    public function destroy(int $id)
    {
        DB::table('meta_scheduled_reports')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * 立即生成并下载报表（简单示例：生成一个本地 xlsx 文件并返回 URL）
     */
    public function download(int $id)
    {
        $report = DB::table('meta_scheduled_reports')->where('id', $id)->first();
        if (! $report) {
            abort(404, '定时报表不存在');
        }

        $now = now();
        $dateStr = $now->format('YmdHis');
        // 文件名示例：summary_report_20260304-20260314_20260331141930_xxx.xlsx
        $fileName = sprintf(
            'summary_report_%s-%s_%s_%s.xlsx',
            $now->format('Ymd'),
            $now->format('Ymd'),
            $dateStr,
            substr(uniqid(), -8)
        );

        $relativePath = 'scheduled-reports/' . $fileName;

        // 简单写入一个示例内容（真实环境可替换为导出真实数据）
        $content = "Scheduled Report: {$report->name}\nGenerated at: {$now->toDateTimeString()}\n";
        Storage::disk('public')->put($relativePath, $content);

        $fullPath = Storage::disk('public')->path($relativePath);

        // 记录一次运行记录
        DB::table('meta_scheduled_report_runs')->insert([
            'report_id' => $id,
            'trigger_type' => 'manual',
            'status' => 'success',
            'message' => '手动下载生成报表',
            'email_to' => $report->email_to ?? '',
            'email_cc' => $report->email_cc ?? null,
            'attachment_url' => $relativePath,
            'started_at' => $now,
            'finished_at' => $now,
            'created_at' => $now,
        ]);

        return response()->download($fullPath, $fileName);
    }
}

