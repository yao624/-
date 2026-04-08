<?php

namespace App\Http\Controllers\MetaCopy;

use App\Http\Controllers\Controller;
use App\Models\MetaCopyFolder;
use App\Models\MetaCopyItem;
use App\Models\MetaCopyLibrary;
use App\Services\MetaCopyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MetaCopyFolderController extends Controller
{
    public function reorder(Request $request)
    {
        $request->validate([
            'library_id' => 'required|string',
            'parent_id' => 'nullable|string',
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'required|string',
        ]);

        $library = MetaCopyLibrary::query()->findOrFail($request->input('library_id'));
        $user = Auth::user();
        if (! MetaCopyAccess::canWriteLibrary($user, $library)) {
            abort(403, '无权重排文件夹');
        }

        $parentId = $request->input('parent_id') ?: null;
        $orderedIds = array_values($request->input('ordered_ids'));

        $folders = MetaCopyFolder::query()
            ->where('library_id', $library->id)
            ->where('parent_id', $parentId)
            ->get(['id', 'sort_order']);

        $existingIds = $folders->pluck('id')->all();
        $missing = array_diff($orderedIds, $existingIds);
        if ($missing !== []) {
            abort(422, '排序列表包含无效文件夹');
        }

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                MetaCopyFolder::query()
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function tree(Request $request)
    {
        $request->validate(['library_id' => 'required|string']);

        $library = MetaCopyLibrary::query()->findOrFail($request->input('library_id'));
        $user = Auth::user();

        if (! MetaCopyAccess::canViewLibrary($user, $library)) {
            abort(403, '无权查看该文案库');
        }

        $folders = MetaCopyFolder::query()
            ->where('library_id', $library->id)
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tree = $this->buildTree($folders, null);

        return response()->json(['data' => $tree]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'library_id' => 'required|string',
            'parent_id' => 'nullable|string',
            'name' => 'required|string|max:191',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $library = MetaCopyLibrary::query()->findOrFail($request->input('library_id'));
        $user = Auth::user();

        if (! MetaCopyAccess::canWriteLibrary($user, $library)) {
            abort(403, '无权在该文案库下新建文件夹');
        }

        $parentId = $request->input('parent_id');
        $level = 1;
        if ($parentId) {
            $parent = MetaCopyFolder::query()
                ->where('library_id', $library->id)
                ->where('id', $parentId)
                ->firstOrFail();
            $level = $parent->level + 1;
            if ($level > MetaCopyFolder::MAX_LEVEL) {
                abort(422, '文件夹层级不能超过 '.MetaCopyFolder::MAX_LEVEL.' 级');
            }
        }

        $folder = MetaCopyFolder::create([
            'library_id' => $library->id,
            'parent_id' => $parentId,
            'name' => $request->input('name'),
            'level' => $level,
            'sort_order' => (int) $request->input('sort_order', 0),
            'created_by' => $user->id,
        ]);

        return response()->json(['data' => $this->toFolderArray($folder)], 201);
    }

    public function update(Request $request, MetaCopyFolder $folder)
    {
        $user = Auth::user();
        $sourceLibrary = $folder->library;

        $request->validate([
            'name' => 'sometimes|string|max:191',
            'sort_order' => 'sometimes|integer|min:0',
            'parent_id' => 'sometimes|nullable|string',
        ]);

        // 如果没有修改 parent_id，则只需要在源文案库内具备修改权限
        if (! $request->has('parent_id')) {
            if (! MetaCopyAccess::canWriteLibrary($user, $sourceLibrary)) {
                abort(403, '无权修改文件夹');
            }
        }

        if ($request->has('parent_id')) {
            $newParentId = $request->input('parent_id');
            if ($newParentId === $folder->id) {
                abort(422, '不能将文件夹移动到自身');
            }

            $newLevel = 1;
            $targetLibrary = $sourceLibrary;
            $descendantIds = $this->descendantFolderIds($folder);
            if ($newParentId) {
                // 允许跨文案库移动：newParent 可能属于其它 library
                $newParent = MetaCopyFolder::query()
                    ->where('id', $newParentId)
                    ->firstOrFail();

                $targetLibrary = $newParent->library;
                if (! MetaCopyAccess::canWriteLibrary($user, $targetLibrary)) {
                    abort(403, '无权在目标文案库下移动文件夹');
                }

                if (in_array($newParent->id, $descendantIds, true)) {
                    abort(422, '不能移动到自身子节点下');
                }

                $newLevel = $newParent->level + 1;
            }

            if (! MetaCopyAccess::canWriteLibrary($user, $targetLibrary)) {
                abort(403, '无权在目标文案库下移动文件夹');
            }

            $delta = $newLevel - $folder->level;
            $targetDeepest = $this->maxDescendantLevel($folder) + $delta;
            if ($targetDeepest > MetaCopyFolder::MAX_LEVEL) {
                abort(422, '移动后层级超过限制（最多 '.MetaCopyFolder::MAX_LEVEL.' 级）');
            }

            DB::transaction(function () use ($folder, $newParentId, $newLevel, $delta, $targetLibrary, $descendantIds) {
                $allIds = array_merge([$folder->id], $descendantIds);

                // 跨文案库时，需要把该文件夹及其所有子孙一起切换到目标 library
                MetaCopyFolder::query()
                    ->whereIn('id', $allIds)
                    ->update(['library_id' => $targetLibrary->id]);

                // 同步该文件夹及其子孙下的文案归属（否则前端切换库后看不到文案）
                MetaCopyItem::query()
                    ->whereIn('folder_id', $allIds)
                    ->update(['library_id' => $targetLibrary->id]);

                $folder->library_id = $targetLibrary->id;
                $folder->parent_id = $newParentId ?: null;
                $folder->level = $newLevel;
                $this->shiftDescendantLevels($folder, $delta);
                $folder->save();
            });
        }

        $folder->fill($request->only(['name', 'sort_order']));
        $folder->save();

        return response()->json(['data' => $this->toFolderArray($folder)]);
    }

    public function destroy(MetaCopyFolder $folder)
    {
        $user = Auth::user();
        $library = $folder->library;
        if (! MetaCopyAccess::canDeleteInLibrary($user, $library)) {
            abort(403, '无权删除文件夹');
        }

        if ($folder->parent_id === null && $library->folders()->whereNull('parent_id')->count() <= 1) {
            abort(403, '不能删除唯一的根目录');
        }

        if ($folder->children()->exists()) {
            abort(422, '请先删除子文件夹');
        }

        if (MetaCopyItem::query()->where('folder_id', $folder->id)->exists()) {
            abort(422, '请先删除文件夹内文案');
        }

        $folder->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, MetaCopyFolder>  $folders
     */
    private function buildTree($folders, ?string $parentId): array
    {
        return $folders
            ->filter(fn (MetaCopyFolder $f) => $f->parent_id === $parentId)
            ->values()
            ->map(function (MetaCopyFolder $f) use ($folders) {
                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'level' => $f->level,
                    'sort_order' => $f->sort_order,
                    'direct_copy_count' => $f->direct_copy_count,
                    'total_copy_count' => $f->total_copy_count,
                    'children' => $this->buildTree($folders, $f->id),
                ];
            })
            ->all();
    }

    private function toFolderArray(MetaCopyFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'library_id' => $folder->library_id,
            'parent_id' => $folder->parent_id,
            'name' => $folder->name,
            'level' => $folder->level,
            'sort_order' => $folder->sort_order,
            'direct_copy_count' => $folder->direct_copy_count,
            'total_copy_count' => $folder->total_copy_count,
        ];
    }

    /**
     * @return list<string>
     */
    private function descendantFolderIds(MetaCopyFolder $root): array
    {
        $ids = [];
        $queue = [$root->id];

        while ($queue !== []) {
            $pid = array_shift($queue);
            $children = MetaCopyFolder::query()->where('parent_id', $pid)->pluck('id')->all();
            foreach ($children as $cid) {
                $ids[] = $cid;
                $queue[] = $cid;
            }
        }

        return $ids;
    }

    private function maxDescendantLevel(MetaCopyFolder $root): int
    {
        $ids = $this->descendantFolderIds($root);
        if ($ids === []) return $root->level;

        $max = (int) MetaCopyFolder::query()->whereIn('id', $ids)->max('level');
        return max($root->level, $max);
    }

    private function shiftDescendantLevels(MetaCopyFolder $root, int $delta): void
    {
        if ($delta === 0) return;
        $ids = $this->descendantFolderIds($root);
        if ($ids === []) return;

        $rows = MetaCopyFolder::query()->whereIn('id', $ids)->get();
        foreach ($rows as $row) {
            $row->level = $row->level + $delta;
            $row->save();
        }
    }
}
