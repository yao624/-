<?php

namespace App\Http\Controllers\MetaTask;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationCenterController extends Controller
{
    /**
     * 通知列表（当前登录用户）
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        if (! $userId) {
            abort(401, '未登录');
        }

        $pageNo = (int) $request->input('pageNo', 1);
        $pageSize = (int) $request->input('pageSize', 20);

        $baseQuery = DB::table('meta_notifications')
            ->where('user_id', $userId)
            ->where('status', '!=', 'deleted');

        $type = $request->input('type');
        $status = $request->input('status');

        if ($type !== null && $type !== '') {
            $baseQuery->where('type', $type);
        }

        if ($status !== null && $status !== '') {
            $baseQuery->where('status', $status);
        }

        $totalCount = (clone $baseQuery)->count('id');

        $rows = $baseQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($pageNo, $pageSize)
            ->get([
                'id',
                'type',
                'title',
                'content',
                'extra',
                'status',
                'read_at',
                'created_at',
            ]);

        $data = $rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'type' => $row->type,
                'title' => $row->title,
                'content' => $row->content,
                'status' => $row->status,
                'read_at' => $row->read_at,
                'created_at' => $row->created_at,
                'extra' => $row->extra,
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
     * 查看通知详情（并标记为已读）
     */
    public function show(int $id)
    {
        $userId = Auth::id();
        if (! $userId) {
            abort(401, '未登录');
        }

        $notification = DB::table('meta_notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('status', '!=', 'deleted')
            ->first();

        if (! $notification) {
            abort(404, '通知不存在');
        }

        // 标记为已读
        if ($notification->status === 'unread') {
            DB::table('meta_notifications')
                ->where('id', $id)
                ->update([
                    'status' => 'read',
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);

            $notification->status = 'read';
            $notification->read_at = now();
        }

        return response()->json([
            'data' => [
                'id' => (string) $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'content' => $notification->content,
                'status' => $notification->status,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'extra' => $notification->extra,
            ],
        ]);
    }

    /**
     * 批量标记为已读
     */
    public function markAsRead(Request $request)
    {
        $userId = Auth::id();
        if (! $userId) {
            abort(401, '未登录');
        }

        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['success' => true]);
        }

        $now = now();
        DB::table('meta_notifications')
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->where('status', 'unread')
            ->update([
                'status' => 'read',
                'read_at' => $now,
                'updated_at' => $now,
            ]);

        return response()->json(['success' => true]);
    }
}

