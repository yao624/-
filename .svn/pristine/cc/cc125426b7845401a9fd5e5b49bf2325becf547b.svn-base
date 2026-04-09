<?php

namespace App\Http\Controllers\MetaCopy;

use App\Http\Controllers\Controller;
use App\Models\MetaCopyFolder;
use App\Models\MetaCopyItem;
use App\Models\MetaCopyLibrary;
use App\Models\MetaCopyPerformanceDaily;
use App\Services\Locale\CopywritingTranslationResolver;
use App\Services\MetaCopyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaCopyItemController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('include_children')) {
            $raw = $request->input('include_children');
            if ($raw === 'true') {
                $request->merge(['include_children' => 1]);
            } elseif ($raw === 'false') {
                $request->merge(['include_children' => 0]);
            }
        }

        $request->validate([
            'folder_id' => 'required|string',
            'include_children' => 'sometimes|boolean',
            'keyword' => 'sometimes|string|max:255',
            'date_start' => 'sometimes|date',
            'date_end' => 'sometimes|date|after_or_equal:date_start',
            'pageNo' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1|max:100',
        ]);

        $folder = MetaCopyFolder::query()->findOrFail($request->input('folder_id'));
        $library = $folder->library;
        $user = Auth::user();

        if (! MetaCopyAccess::canViewLibrary($user, $library)) {
            abort(403, '无权查看文案');
        }

        $includeChildren = filter_var($request->input('include_children', false), FILTER_VALIDATE_BOOLEAN);
        $folderIds = $includeChildren
            ? $this->descendantFolderIds($folder)
            : [$folder->id];

        $q = MetaCopyItem::query()
            ->where('library_id', $library->id)
            ->whereIn('folder_id', $folderIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('keyword')) {
            $kw = '%'.$request->input('keyword').'%';
            $q->where(function ($sub) use ($kw) {
                $sub->where('primary_text', 'like', $kw)
                    ->orWhere('headline', 'like', $kw)
                    ->orWhere('description', 'like', $kw)
                    ->orWhere('remark', 'like', $kw);
            });
        }

        $pageSize = (int) $request->input('pageSize', 10);
        $pageNo = (int) $request->input('pageNo', 1);

        $paginator = $q->paginate($pageSize, ['*'], 'page', $pageNo);

        $items = $paginator->getCollection()->values();
        $itemIds = $items->pluck('id')->all();
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        $perfMap = [];
        if ($itemIds !== []) {
            $perfQuery = MetaCopyPerformanceDaily::query()
                ->selectRaw('copy_item_id, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend, SUM(conversions) as conversions, SUM(revenue) as revenue')
                ->whereIn('copy_item_id', $itemIds)
                ->groupBy('copy_item_id');

            if ($dateStart) {
                $perfQuery->whereDate('stat_date', '>=', $dateStart);
            }
            if ($dateEnd) {
                $perfQuery->whereDate('stat_date', '<=', $dateEnd);
            }

            $perfRows = $perfQuery->get();
            foreach ($perfRows as $row) {
                $perfMap[$row->copy_item_id] = [
                    'impressions' => (int) ($row->impressions ?? 0),
                    'clicks' => (int) ($row->clicks ?? 0),
                    'spend' => (float) ($row->spend ?? 0),
                    'conversions' => (int) ($row->conversions ?? 0),
                    'revenue' => (float) ($row->revenue ?? 0),
                ];
            }
        }

        return response()->json([
            'data' => $items->map(fn (MetaCopyItem $i) => $this->toItemArray($i, $perfMap[$i->id] ?? null))->values(),
            'pageSize' => $paginator->perPage(),
            'pageNo' => $paginator->currentPage(),
            'totalPage' => $paginator->lastPage(),
            'totalCount' => $paginator->total(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'library_id' => 'required|string',
            'folder_id' => 'required|string',
            'primary_text' => 'nullable|string',
            'headline' => 'nullable|string|max:512',
            'description' => 'nullable|string',
            'translations' => 'sometimes|array|nullable',
            'remark' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,active',
        ]);

        $library = MetaCopyLibrary::query()->findOrFail($request->input('library_id'));
        $user = Auth::user();

        if (! MetaCopyAccess::canWriteLibrary($user, $library)) {
            abort(403, '无权新增文案');
        }

        $folder = MetaCopyFolder::query()
            ->where('library_id', $library->id)
            ->where('id', $request->input('folder_id'))
            ->firstOrFail();

        $item = MetaCopyItem::create([
            'library_id' => $library->id,
            'folder_id' => $folder->id,
            'primary_text' => $request->input('primary_text'),
            'headline' => $request->input('headline'),
            'description' => $request->input('description'),
            'translations' => $this->normalizeTranslationsPayload($request->input('translations')),
            'remark' => $request->input('remark'),
            'status' => $request->input('status', 'active'),
            'created_by' => $user->id,
        ]);

        return response()->json(['data' => $this->toItemArray($item)], 201);
    }

    public function update(Request $request, MetaCopyItem $item)
    {
        $user = Auth::user();
        $library = $item->library;
        if (! MetaCopyAccess::canWriteLibrary($user, $library)) {
            abort(403, '无权编辑文案');
        }

        $request->validate([
            'folder_id' => 'sometimes|string',
            'primary_text' => 'sometimes|nullable|string',
            'headline' => 'sometimes|nullable|string|max:512',
            'description' => 'sometimes|nullable|string',
            'translations' => 'sometimes|array|nullable',
            'remark' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:draft,active',
        ]);

        if ($request->has('folder_id')) {
            $folder = MetaCopyFolder::query()
                ->where('library_id', $library->id)
                ->where('id', $request->input('folder_id'))
                ->firstOrFail();
            $item->folder_id = $folder->id;
        }

        $item->fill($request->only(['primary_text', 'headline', 'description', 'remark', 'status']));
        if ($request->has('translations')) {
            $item->translations = $this->normalizeTranslationsPayload($request->input('translations'));
        }
        $item->save();

        return response()->json(['data' => $this->toItemArray($item)]);
    }

    public function show(MetaCopyItem $item)
    {
        $user = Auth::user();
        $library = $item->library;
        if (! MetaCopyAccess::canViewLibrary($user, $library)) {
            abort(403, '无权查看文案');
        }

        return response()->json(['data' => $this->toItemArray($item)]);
    }

    public function destroy(MetaCopyItem $item)
    {
        $user = Auth::user();
        $library = $item->library;
        if (! MetaCopyAccess::canDeleteInLibrary($user, $library)) {
            abort(403, '无权删除文案');
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    public function batchDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string',
        ]);

        $user = Auth::user();
        $ids = $request->input('ids');

        $items = MetaCopyItem::query()->whereIn('id', $ids)->get();
        foreach ($items as $item) {
            if (! MetaCopyAccess::canDeleteInLibrary($user, $item->library)) {
                abort(403, '批量删除中存在无权限的文案');
            }
        }

        MetaCopyItem::query()->whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'deleted' => count($ids)]);
    }

    /**
     * @return list<string>
     */
    private function descendantFolderIds(MetaCopyFolder $root): array
    {
        $ids = [$root->id];
        $queue = [$root->id];

        while ($queue !== []) {
            $pid = array_shift($queue);
            $children = MetaCopyFolder::query()->where('parent_id', $pid)->pluck('id');
            foreach ($children as $cid) {
                $ids[] = $cid;
                $queue[] = $cid;
            }
        }

        return $ids;
    }

    private function toItemArray(MetaCopyItem $item, ?array $performance = null): array
    {
        $perf = $performance ?? [
            'impressions' => 0,
            'clicks' => 0,
            'spend' => 0,
            'conversions' => 0,
            'revenue' => 0,
        ];

        return [
            'id' => $item->id,
            'library_id' => $item->library_id,
            'folder_id' => $item->folder_id,
            'primary_text' => $item->primary_text,
            'headline' => $item->headline,
            'description' => $item->description,
            'translations' => $item->translations ?? [],
            'remark' => $item->remark,
            'status' => $item->status,
            'impressions' => $perf['impressions'],
            'clicks' => $perf['clicks'],
            'spend' => $perf['spend'],
            'conversions' => $perf['conversions'],
            'revenue' => $perf['revenue'],
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  mixed  $payload
     * @return array<string, array<string, mixed>>|null
     */
    private function normalizeTranslationsPayload(mixed $payload): ?array
    {
        $norm = CopywritingTranslationResolver::normalizeTranslationsInput($payload);
        $clean = [];
        foreach ($norm as $loc => $fields) {
            $has = ($fields['primary_text'] ?? null) !== null
                || ($fields['headline'] ?? null) !== null
                || ($fields['description'] ?? null) !== null;
            if ($has) {
                $clean[$loc] = array_filter($fields, static fn ($v) => $v !== null && $v !== '');
            }
        }

        return $clean === [] ? null : $clean;
    }
}
