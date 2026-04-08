<?php

namespace App\Http\Controllers;

use App\Http\Resources\RequestLogResource;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class RequestLogController extends Controller
{
    /**
     * 获取请求日志列表
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'requested_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 20);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'user_name' => $request->get('user_name'),
            'ip_address' => $request->get('ip_address'),
            'request_method' => $request->get('request_method'),
            'request_path' => $request->get('request_path'),
            'response_status' => $request->get('response_status'),
            'date_start' => $request->get('date_start'),
            'date_end' => $request->get('date_end'),
            'datetime_start' => $request->get('datetime_start'),
            'datetime_end' => $request->get('datetime_end'),
        ];

        $query = RequestLog::with('user');

        // 用户名筛选
        if (!empty($searchableFields['user_name'])) {
            $query->whereHas('user', function ($q) use ($searchableFields) {
                $q->where('name', 'like', '%' . $searchableFields['user_name'] . '%');
            });
        }

        // IP地址筛选
        if (!empty($searchableFields['ip_address'])) {
            $query->where('ip_address', 'like', '%' . $searchableFields['ip_address'] . '%');
        }

        // 请求方法筛选
        if (!empty($searchableFields['request_method'])) {
            $query->where('request_method', $searchableFields['request_method']);
        }

        // 请求路径筛选
        if (!empty($searchableFields['request_path'])) {
            $query->where('request_path', 'like', '%' . $searchableFields['request_path'] . '%');
        }

        // 响应状态筛选
        if (!empty($searchableFields['response_status'])) {
            $query->where('response_status', $searchableFields['response_status']);
        }

        // 时间范围筛选 - 优先使用精确时间
        if (!empty($searchableFields['datetime_start'])) {
            $query->where('requested_at', '>=', $searchableFields['datetime_start']);
        } elseif (!empty($searchableFields['date_start'])) {
            $query->where('requested_at', '>=', $searchableFields['date_start'] . ' 00:00:00');
        }

        if (!empty($searchableFields['datetime_end'])) {
            $query->where('requested_at', '<=', $searchableFields['datetime_end']);
        } elseif (!empty($searchableFields['date_end'])) {
            $query->where('requested_at', '<=', $searchableFields['date_end'] . ' 23:59:59');
        }

        // 排序和分页
        $logs = $query->orderBy($sortField, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => RequestLogResource::collection($logs->items()),
            'pageSize' => $logs->perPage(),
            'pageNo' => $logs->currentPage(),
            'totalPage' => $logs->lastPage(),
            'totalCount' => $logs->total(),
        ];
    }

    /**
     * 获取单个请求日志详情
     */
    public function show(string $id): RequestLogResource
    {
        $log = RequestLog::with('user')->findOrFail($id);
        return new RequestLogResource($log);
    }

    /**
     * 获取请求统计信息
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_start' => 'sometimes|date_format:Y-m-d',
            'date_end' => 'sometimes|date_format:Y-m-d',
            'datetime_start' => 'sometimes|date_format:Y-m-d H:i:s',
            'datetime_end' => 'sometimes|date_format:Y-m-d H:i:s',
        ]);

        $query = RequestLog::query();

        // 时间范围筛选
        if (!empty($validated['datetime_start'])) {
            $query->where('requested_at', '>=', $validated['datetime_start']);
        } elseif (!empty($validated['date_start'])) {
            $query->where('requested_at', '>=', $validated['date_start'] . ' 00:00:00');
        }

        if (!empty($validated['datetime_end'])) {
            $query->where('requested_at', '<=', $validated['datetime_end']);
        } elseif (!empty($validated['date_end'])) {
            $query->where('requested_at', '<=', $validated['date_end'] . ' 23:59:59');
        }

        // 基础统计
        $totalRequests = $query->count();
        $uniqueUsers = $query->whereNotNull('user_id')->distinct('user_id')->count();
        $uniqueIps = $query->distinct('ip_address')->count();
        $avgResponseTime = $query->whereNotNull('response_time')->avg('response_time');

        // 按请求方法统计
        $methodStats = $query->selectRaw('request_method, COUNT(*) as count')
            ->groupBy('request_method')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'request_method');

        // 按响应状态统计
        $statusStats = $query->selectRaw('response_status, COUNT(*) as count')
            ->whereNotNull('response_status')
            ->groupBy('response_status')
            ->orderBy('response_status')
            ->get()
            ->pluck('count', 'response_status');

        // 按小时统计（最近24小时）
        $hourlyStats = $query->selectRaw('HOUR(requested_at) as hour, COUNT(*) as count')
            ->where('requested_at', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        // 热门路径
        $topPaths = $query->selectRaw('request_path, COUNT(*) as count')
            ->groupBy('request_path')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'path' => $item->request_path,
                    'count' => $item->count,
                ];
            });

        // 热门IP
        $topIps = $query->selectRaw('ip_address, COUNT(*) as count')
            ->groupBy('ip_address')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'ip' => $item->ip_address,
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_requests' => $totalRequests,
                    'unique_users' => $uniqueUsers,
                    'unique_ips' => $uniqueIps,
                    'avg_response_time' => $avgResponseTime ? round($avgResponseTime, 2) : null,
                    'avg_response_time_formatted' => $avgResponseTime ? round($avgResponseTime, 2) . 'ms' : null,
                ],
                'method_stats' => $methodStats,
                'status_stats' => $statusStats,
                'hourly_stats' => $hourlyStats,
                'top_paths' => $topPaths,
                'top_ips' => $topIps,
            ],
        ]);
    }

    /**
     * 清理旧的请求日志
     */
    public function cleanup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? config('request_logging.retention_days', 30);
        $cutoffDate = now()->subDays($days);

        $deletedCount = RequestLog::where('requested_at', '<', $cutoffDate)->delete();

        return response()->json([
            'success' => true,
            'message' => "已删除 {$days} 天前的请求日志",
            'data' => [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
