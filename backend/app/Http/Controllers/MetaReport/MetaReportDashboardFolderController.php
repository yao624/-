<?php

namespace App\Http\Controllers\MetaReport;

use App\Http\Controllers\Controller;
use App\Models\MetaReportDashboard;
use App\Models\MetaReportDashboardFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaReportDashboardFolderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $folders = MetaReportDashboardFolder::query()
            ->where('owner_user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $this->buildTree($folders),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'parent_id' => 'sometimes|nullable|string',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $user = Auth::user();
        $parentId = $request->input('parent_id');
        if ($parentId) {
            MetaReportDashboardFolder::query()
                ->where('owner_user_id', $user->id)
                ->where('id', $parentId)
                ->firstOrFail();
        }

        $folder = MetaReportDashboardFolder::query()->create([
            'owner_user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => $request->input('name'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'status' => 'active',
        ]);

        return response()->json(['data' => $this->toFolderArray($folder)], 201);
    }

    public function update(Request $request, MetaReportDashboardFolder $metaReportDashboardFolder)
    {
        $this->authorizeFolder($metaReportDashboardFolder);
        $request->validate([
            'name' => 'sometimes|string|max:200',
            'sort_order' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:active,archived',
        ]);

        $metaReportDashboardFolder->fill($request->only(['name', 'sort_order', 'status']));
        $metaReportDashboardFolder->save();

        return response()->json(['data' => $this->toFolderArray($metaReportDashboardFolder)]);
    }

    public function destroy(MetaReportDashboardFolder $metaReportDashboardFolder)
    {
        $this->authorizeFolder($metaReportDashboardFolder);

        $descendantIds = $this->collectDescendantIds($metaReportDashboardFolder);
        $allFolderIds = array_merge([$metaReportDashboardFolder->id], $descendantIds);

        MetaReportDashboard::query()
            ->whereIn('folder_id', $allFolderIds)
            ->update(['folder_id' => null]);

        MetaReportDashboardFolder::query()
            ->whereIn('id', $allFolderIds)
            ->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeFolder(MetaReportDashboardFolder $folder): void
    {
        $user = Auth::user();
        if ((string) $folder->owner_user_id !== (string) $user->id) {
            abort(403, '无权操作该文件夹');
        }
    }

    private function toFolderArray(MetaReportDashboardFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'owner_user_id' => $folder->owner_user_id,
            'parent_id' => $folder->parent_id,
            'name' => $folder->name,
            'sort_order' => (int) $folder->sort_order,
            'status' => $folder->status,
            'created_at' => $folder->created_at?->toIso8601String(),
            'updated_at' => $folder->updated_at?->toIso8601String(),
        ];
    }

    private function buildTree($folders): array
    {
        $nodes = [];
        foreach ($folders as $folder) {
            $nodes[$folder->id] = $this->toFolderArray($folder) + ['children' => []];
        }

        $tree = [];
        foreach ($folders as $folder) {
            if ($folder->parent_id && isset($nodes[$folder->parent_id])) {
                $nodes[$folder->parent_id]['children'][] = &$nodes[$folder->id];
            } else {
                $tree[] = &$nodes[$folder->id];
            }
        }

        return $tree;
    }

    private function collectDescendantIds(MetaReportDashboardFolder $root): array
    {
        $ids = [];
        $queue = [$root->id];
        while ($queue !== []) {
            $pid = array_shift($queue);
            $children = MetaReportDashboardFolder::query()->where('parent_id', $pid)->pluck('id')->all();
            foreach ($children as $cid) {
                $ids[] = $cid;
                $queue[] = $cid;
            }
        }

        return $ids;
    }
}

