<?php

namespace App\Http\Controllers;

use App\Models\FbPixel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdAccountMigrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = max((int) $request->query('page', 1), 1);
        $pageSize = max((int) $request->query('pageSize', 20), 1);
        $sortField = (string) $request->query('sortField', 'created_time');
        $sortOrder = $request->query('sortOrder') === 'ascend' ? 'asc' : 'desc';
        $keyword = trim((string) $request->query('keyword', ''));
        $status = trim((string) $request->query('account_status', ''));
        $bmId = trim((string) $request->query('bm_id', ''));
        $currency = trim((string) $request->query('currency', ''));

        $allowedSort = ['created_time', 'account_id', 'name', 'account_status', 'currency', 'timezone_name'];
        if (!in_array($sortField, $allowedSort, true)) $sortField = 'created_time';

        $query = DB::table('ad_accounts as a')
            ->leftJoin('business_managers as bm', 'bm.id', '=', 'a.business_manager_id');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('a.name', 'like', "%{$keyword}%")
                    ->orWhere('a.account_id', 'like', "%{$keyword}%");
            });
        }
        if ($status !== '') $query->where('a.account_status', $status);
        if ($currency !== '') $query->where('a.currency', $currency);
        if ($bmId !== '') $query->where('a.business_manager_id', $bmId);

        $total = (clone $query)->count();
        $rows = $query->orderBy("a.{$sortField}", $sortOrder)
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->select([
                'a.account_id',
                'a.name',
                'a.account_status',
                'a.currency',
                'a.timezone_name',
                'a.created_time',
                'a.business_manager_id',
                'bm.name as bm_name',
            ])
            ->get()
            ->map(static function ($row) {
                $created = $row->created_time ?? null;
                if ($created && is_string($created)) {
                    // 若为 ISO 格式，截断到秒；否则原样返回
                    $created = str_replace('T', ' ', substr($created, 0, 19));
                }
                return [
                    // 前端用 record.id 作为操作 id，这里直接使用 ad_accounts.account_id（Facebook 的账户ID）
                    'id' => (string) ($row->account_id ?? ''),
                    'source_id' => (string) ($row->account_id ?? ''),
                    'name' => (string) ($row->name ?? ''),
                    'account_status' => (string) ($row->account_status ?? ''),
                    'currency' => (string) ($row->currency ?? ''),
                    'timezone_name' => (string) ($row->timezone_name ?? ''),
                    'created_at' => $created,
                    'bm_id' => $row->business_manager_id ?? null,
                    'bm_name' => (string) ($row->bm_name ?? ''),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function filterOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => DB::table('ad_accounts')->whereNotNull('account_status')->distinct()->pluck('account_status')->values(),
                'currencies' => DB::table('ad_accounts')->whereNotNull('currency')->distinct()->pluck('currency')->values(),
                'bms' => DB::table('business_managers')->select('id', 'name', 'business_id')->orderByDesc('id')->limit(200)->get(),
            ],
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $taskId = 'adm_' . uniqid();
        $query = DB::table('ad_accounts as a')
            ->leftJoin('business_managers as bm', 'bm.id', '=', 'a.business_manager_id');
        $keyword = trim((string) $request->input('keyword', ''));
        $status = trim((string) $request->input('account_status', ''));
        $currency = trim((string) $request->input('currency', ''));

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('a.name', 'like', "%{$keyword}%")->orWhere('a.account_id', 'like', "%{$keyword}%");
            });
        }
        if ($status !== '') $query->where('a.account_status', $status);
        if ($currency !== '') $query->where('a.currency', $currency);

        $csv = "source_id,name,account_status,bm_name,currency,timezone_name,created_at\n";
        foreach ($query->limit(5000)->select([
            'a.account_id',
            'a.name',
            'a.account_status',
            'a.currency',
            'a.timezone_name',
            'a.created_time',
            'bm.name as bm_name',
        ])->get() as $row) {
            $csv .= implode(',', [
                $this->csv($row->account_id),
                $this->csv($row->name),
                $this->csv($row->account_status),
                $this->csv($row->bm_name ?? ''),
                $this->csv($row->currency),
                $this->csv($row->timezone_name),
                $this->csv($row->created_time ? str_replace('T', ' ', substr((string) $row->created_time, 0, 19)) : ''),
            ]) . "\n";
        }

        Cache::put("ad_export_{$taskId}", ['status' => 'completed', 'content' => $csv], now()->addMinutes(15));
        return response()->json(['success' => true, 'data' => ['task_id' => $taskId]]);
    }

    public function exportTaskStatus(Request $request): JsonResponse
    {
        $taskId = (string) $request->query('id', '');
        $task = Cache::get("ad_export_{$taskId}");
        if (!$task) return response()->json(['success' => false, 'status' => 'failed', 'message' => '任务不存在'], 404);
        return response()->json(['success' => true, 'data' => ['status' => $task['status']]]);
    }

    public function exportTaskDownload(Request $request): StreamedResponse|JsonResponse
    {
        $taskId = (string) $request->query('id', '');
        $task = Cache::get("ad_export_{$taskId}");
        if (!$task || empty($task['content'])) {
            return response()->json(['success' => false, 'message' => '导出文件不存在'], 404);
        }

        return response()->streamDownload(function () use ($task) {
            echo $task['content'];
        }, "ad-accounts-{$taskId}.csv", ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file']);
        return response()->json([
            'success' => true,
            'message' => '导入任务已提交',
        ]);
    }

    public function updateName(Request $request, string $adAccount): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $updated = DB::table('ad_accounts')
            ->where('account_id', $adAccount)
            ->update(['name' => $data['name']]);

        if ($updated <= 0) {
            return response()->json(['success' => false, 'message' => '广告账户不存在'], 404);
        }

        $row = DB::table('ad_accounts')->where('account_id', $adAccount)->first();
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function pixels(string $adAccount): JsonResponse
    {
        // TODO: 迁移版列表基于 ad_accounts；像素关联关系仍以 fb_ad_accounts/fb_pixels 为主时，需要补齐映射逻辑。
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function pixelOperation(Request $request, string $adAccount): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:attach,detach',
            'pixel_id' => 'nullable|string',
            'name' => 'nullable|string|max:255',
        ]);
        if ($data['action'] === 'attach') {
            $pixel = null;
            if (!empty($data['pixel_id'])) {
                $pixel = FbPixel::query()->find($data['pixel_id']);
            }
            if (!$pixel) {
                $pixel = FbPixel::query()->create([
                    'source_id' => 'px_' . uniqid(),
                    'name' => $data['name'] ?? ('Pixel ' . date('His')),
                ]);
            }
            // TODO: 迁移版列表基于 ad_accounts；像素绑定逻辑待补齐映射关系后实现
        } else {
            $pixelId = (string) ($data['pixel_id'] ?? '');
            // TODO: 迁移版列表基于 ad_accounts；像素解绑逻辑待补齐映射关系后实现
        }
        return response()->json(['success' => true]);
    }

    private function csv(?string $value): string
    {
        $v = (string) ($value ?? '');
        $v = str_replace('"', '""', $v);
        return '"' . $v . '"';
    }
}
