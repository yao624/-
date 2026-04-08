<?php

namespace App\Http\Controllers\MetaReport;

use App\Http\Controllers\Controller;
use App\Models\MetaReportDashboard;
use App\Models\MetaReportDashboardFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MetaReportDashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'board_type' => 'sometimes|string|max:32',
            'keyword' => 'sometimes|string|max:200',
            'location' => 'sometimes|string|max:32',
            'status' => 'sometimes|string|in:active,archived',
            'folder_id' => 'sometimes|string',
        ]);

        $user = Auth::user();
        $query = MetaReportDashboard::query()
            ->where('owner_user_id', $user->id)
            ->orderByDesc('updated_at');

        if ($request->filled('board_type')) {
            $query->where('board_type', $request->input('board_type'));
        }
        if ($request->filled('location')) {
            $query->where('location', $request->input('location'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->input('folder_id'));
        }
        if ($request->filled('keyword')) {
            $kw = '%'.$request->input('keyword').'%';
            $query->where('name', 'like', $kw);
        }

        $data = $query->get()->map(fn (MetaReportDashboard $dashboard) => $this->toDashboardArray($dashboard));

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'location' => 'sometimes|string|max:32',
            'channel' => 'sometimes|string|max:32',
            'board_type' => 'required|string|in:comprehensive,material,tag,custom,landing',
            'group_compare' => 'sometimes|boolean',
            'default_filters' => 'sometimes|array|nullable',
            'folder_id' => 'sometimes|nullable|string',
        ]);

        $user = Auth::user();

        $folderId = $request->input('folder_id');
        if ($folderId) {
            MetaReportDashboardFolder::query()
                ->where('owner_user_id', $user->id)
                ->where('id', $folderId)
                ->firstOrFail();
        }

        $dashboard = MetaReportDashboard::query()->create([
            'name' => $request->input('name'),
            'folder_id' => $folderId,
            'location' => $request->input('location', 'mine'),
            'channel' => $request->input('channel', 'summary'),
            'board_type' => $request->input('board_type'),
            'group_compare' => (bool) $request->input('group_compare', false),
            'default_filters' => $request->input('default_filters'),
            'last_saved_at' => now(),
            'status' => 'active',
            'owner_user_id' => $user->id,
        ]);

        return response()->json(['data' => $this->toDashboardArray($dashboard)], 201);
    }

    public function show(MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);

        $metaReportDashboard->load('cards');

        return response()->json([
            'data' => $this->toDashboardArray($metaReportDashboard, true),
        ]);
    }

    public function update(Request $request, MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);

        $request->validate([
            'name' => 'sometimes|string|max:200',
            'location' => 'sometimes|string|max:32',
            'channel' => 'sometimes|string|max:32',
            'group_compare' => 'sometimes|boolean',
            'default_filters' => 'sometimes|array|nullable',
            'status' => 'sometimes|string|in:active,archived',
            'folder_id' => 'sometimes|nullable|string',
        ]);

        if ($request->has('folder_id')) {
            $folderId = $request->input('folder_id');
            if ($folderId) {
                MetaReportDashboardFolder::query()
                    ->where('owner_user_id', Auth::id())
                    ->where('id', $folderId)
                    ->firstOrFail();
            }
        }

        $metaReportDashboard->fill($request->only([
            'name',
            'folder_id',
            'location',
            'channel',
            'group_compare',
            'default_filters',
            'status',
        ]));
        $metaReportDashboard->save();

        return response()->json(['data' => $this->toDashboardArray($metaReportDashboard)]);
    }

    public function destroy(MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);
        $metaReportDashboard->delete();

        return response()->json(['success' => true]);
    }

    public function save(MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);
        $metaReportDashboard->last_saved_at = now();
        $metaReportDashboard->save();

        return response()->json(['data' => $this->toDashboardArray($metaReportDashboard)]);
    }

    public function duplicate(Request $request, MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);

        $request->validate([
            'name' => 'required|string|max:200',
            'location' => 'sometimes|string|max:32',
            'folder_id' => 'sometimes|nullable|string',
        ]);

        $user = Auth::user();
        $folderId = $request->input('folder_id');
        if ($folderId) {
            MetaReportDashboardFolder::query()
                ->where('owner_user_id', $user->id)
                ->where('id', $folderId)
                ->firstOrFail();
        }

        $newDashboard = DB::transaction(function () use ($request, $metaReportDashboard, $user) {
            $target = MetaReportDashboard::query()->create([
                'name' => $request->input('name'),
                'folder_id' => $request->input('folder_id'),
                'location' => $request->input('location', 'mine'),
                'channel' => $metaReportDashboard->channel,
                'board_type' => $metaReportDashboard->board_type,
                'group_compare' => $metaReportDashboard->group_compare,
                'default_filters' => $metaReportDashboard->default_filters,
                'last_saved_at' => now(),
                'status' => 'active',
                'owner_user_id' => $user->id,
            ]);

            $metaReportDashboard->load('cards');
            foreach ($metaReportDashboard->cards as $card) {
                $target->cards()->create([
                    'title' => $card->title,
                    'chart_type' => $card->chart_type,
                    'shape' => $card->shape,
                    'sort_order' => $card->sort_order,
                    'query_config' => $card->query_config,
                    'style_config' => $card->style_config,
                ]);
            }

            return $target;
        });

        return response()->json(['data' => $this->toDashboardArray($newDashboard, true)], 201);
    }

    private function authorizeDashboard(MetaReportDashboard $dashboard): void
    {
        $user = Auth::user();
        // 与数据库/序列化类型一致比较，避免 int 与 string 在 !== 下误判（如 id=1 与 "1"）
        if ((string) $dashboard->owner_user_id !== (string) $user->id) {
            abort(403, '无权操作该看板');
        }
    }

    private function toDashboardArray(MetaReportDashboard $dashboard, bool $withCards = false): array
    {
        return [
            'id' => $dashboard->id,
            'name' => $dashboard->name,
            'folder_id' => $dashboard->folder_id,
            'location' => $dashboard->location,
            'channel' => $dashboard->channel,
            'board_type' => $dashboard->board_type,
            'group_compare' => (bool) $dashboard->group_compare,
            'default_filters' => $dashboard->default_filters,
            'last_saved_at' => $dashboard->last_saved_at?->toDateTimeString(),
            'status' => $dashboard->status,
            'owner_user_id' => $dashboard->owner_user_id,
            'created_at' => $dashboard->created_at?->toIso8601String(),
            'updated_at' => $dashboard->updated_at?->toIso8601String(),
            'cards' => $withCards ? $dashboard->cards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'dashboard_id' => $card->dashboard_id,
                    'title' => $card->title,
                    'chart_type' => $card->chart_type,
                    'shape' => $card->shape,
                    'sort_order' => (int) $card->sort_order,
                    'query_config' => $card->query_config,
                    'style_config' => $card->style_config,
                    'created_at' => $card->created_at?->toIso8601String(),
                    'updated_at' => $card->updated_at?->toIso8601String(),
                ];
            })->values() : [],
        ];
    }
}

