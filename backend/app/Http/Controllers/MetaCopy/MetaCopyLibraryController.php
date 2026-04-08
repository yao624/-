<?php

namespace App\Http\Controllers\MetaCopy;

use App\Http\Controllers\Controller;
use App\Models\MetaCopyFolder;
use App\Models\MetaCopyItem;
use App\Models\MetaCopyLibrary;
use App\Models\MetaCopyLibraryPermission;
use App\Services\MetaCopyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaCopyLibraryController extends Controller
{
    /**
     * 列表：自动保证每人至少一个「我的文案库」。
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->ensureDefaultLibraries($user);
        $this->ensureDemoCopiesForDefaultAndEnterprise($user->id);

        $query = MetaCopyLibrary::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                    ->orWhere('type', MetaCopyLibrary::TYPE_ENTERPRISE);
            })
            ->orderByRaw("CASE WHEN type = 'personal' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN type = 'personal' AND name = '默认文案库' THEN 0 ELSE 1 END")
            ->orderBy('created_at');

        $data = $query
            ->get()
            ->filter(fn (MetaCopyLibrary $l) => MetaCopyAccess::canViewLibrary($user, $l))
            ->map(fn (MetaCopyLibrary $l) => $this->toLibraryArray($l));

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'type' => 'required|string|in:personal,enterprise',
            'visibility_scope' => 'sometimes|array|nullable',
        ]);

        $user = Auth::user();
        $type = $request->input('type');

        if ($type === MetaCopyLibrary::TYPE_ENTERPRISE && ! $this->canCreateEnterpriseLibrary($user)) {
            abort(403, '无权创建企业文案库');
        }

        $library = MetaCopyLibrary::create([
            'name' => $request->input('name'),
            'type' => $type,
            'owner_user_id' => $user->id,
            'visibility_scope' => $request->input('visibility_scope'),
            'status' => 'active',
        ]);
        $this->ensureRootFolder($library, $user->id);
        // 新建文案库：只保证“根目录”存在，不自动插入演示文案。
        // 演示文案由 index() 的 ensureDemoCopiesForDefaultAndEnterprise 控制，避免把默认库内容“复制”到每个新库。

        if ($type === MetaCopyLibrary::TYPE_ENTERPRISE) {
            MetaCopyLibraryPermission::query()->create([
                'library_id' => $library->id,
                'subject_type' => MetaCopyLibraryPermission::SUBJECT_USER,
                'subject_id' => $user->id,
                'can_manage' => true,
                'can_write' => true,
                'can_delete' => true,
            ]);
        }

        return response()->json(['data' => $this->toLibraryArray($library)]);
    }

    public function show(MetaCopyLibrary $library)
    {
        $user = Auth::user();
        if (! MetaCopyAccess::canViewLibrary($user, $library)) {
            abort(403, '无权查看该文案库');
        }

        return response()->json(['data' => $this->toLibraryArray($library)]);
    }

    public function update(Request $request, MetaCopyLibrary $library)
    {
        $user = Auth::user();
        if (! MetaCopyAccess::canManageLibrary($user, $library)) abort(403, '无权修改该文案库');

        $request->validate([
            'name' => 'sometimes|string|max:191',
            'visibility_scope' => 'sometimes|array|nullable',
            'status' => 'sometimes|string|in:active,disabled',
        ]);

        $library->fill($request->only(['name', 'visibility_scope', 'status']));
        $library->save();

        return response()->json(['data' => $this->toLibraryArray($library)]);
    }

    public function destroy(MetaCopyLibrary $library)
    {
        $user = Auth::user();

        // 仅禁止删除“个人默认文案库”
        if (
            $library->type === MetaCopyLibrary::TYPE_PERSONAL
            && $library->name === '默认文案库'
            && $library->owner_user_id === $user->id
        ) {
            abort(403, '默认库不可删除');
        }

        if (! MetaCopyAccess::canDeleteInLibrary($user, $library)) {
            abort(403, '无权删除该文案库');
        }

        $library->delete();

        return response()->json(['success' => true]);
    }

    private function ensureDefaultLibraries(\Illuminate\Contracts\Auth\Authenticatable $user): void
    {
        $uid = $user->id;
        $exists = MetaCopyLibrary::query()
            ->where('type', MetaCopyLibrary::TYPE_PERSONAL)
            ->where('name', '默认文案库')
            ->where('owner_user_id', $uid)
            ->exists();

        if (! $exists) {
            $lib = MetaCopyLibrary::create([
                'name' => '默认文案库',
                'type' => MetaCopyLibrary::TYPE_PERSONAL,
                'owner_user_id' => $uid,
                'status' => 'active',
            ]);
            $this->ensureRootFolder($lib, $uid);
            $this->ensureDemoCopies($lib, $uid);
        }
    }

    private function canCreateEnterpriseLibrary($user): bool
    {
        $allowRoles = ['admin', 'subadmin', 'optimizer_manager', 'design_manager', '子管理员', '优化经理', '设计经理'];
        return $user->hasRole($allowRoles);
    }

    private function ensureRootFolder(MetaCopyLibrary $library, string $userId): void
    {
        if ($library->folders()->exists()) {
            return;
        }

        MetaCopyFolder::create([
            'library_id' => $library->id,
            'parent_id' => null,
            'name' => '根目录',
            'level' => 1,
            'sort_order' => 0,
            'created_by' => $userId,
        ]);
    }

    private function ensureDemoCopiesForDefaultAndEnterprise(string $userId): void
    {
        $libs = MetaCopyLibrary::query()
            ->where(function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('type', MetaCopyLibrary::TYPE_PERSONAL)
                        ->where('owner_user_id', $userId)
                        ->where('name', '默认文案库');
                })->orWhere(function ($sub) use ($userId) {
                    $sub->where('type', MetaCopyLibrary::TYPE_ENTERPRISE)
                        ->where('owner_user_id', $userId);
                });
            })
            ->get();

        foreach ($libs as $lib) {
            $this->ensureDemoCopies($lib, $userId);
        }
    }

    private function ensureDemoCopies(MetaCopyLibrary $library, string $userId): void
    {
        $hasItems = MetaCopyItem::query()->where('library_id', $library->id)->exists();
        if ($hasItems) return;

        $root = MetaCopyFolder::query()
            ->where('library_id', $library->id)
            ->whereNull('parent_id')
            ->orderBy('created_at')
            ->first();
        if (! $root) return;

        $rows = [
            ['primary_text' => '测试文案A：高转化开场，突出核心卖点', 'headline' => '测试标题A', 'description' => '测试描述A'],
            ['primary_text' => '测试文案B：场景化表达，强调使用收益', 'headline' => '测试标题B', 'description' => '测试描述B'],
            ['primary_text' => '测试文案C：限时优惠引导点击', 'headline' => '测试标题C', 'description' => '测试描述C'],
        ];

        foreach ($rows as $row) {
            MetaCopyItem::query()->create([
                'library_id' => $library->id,
                'folder_id' => $root->id,
                'primary_text' => $row['primary_text'],
                'headline' => $row['headline'],
                'description' => $row['description'],
                'remark' => '系统初始化测试文案',
                'status' => 'active',
                'created_by' => $userId,
            ]);
        }
    }

    private function toLibraryArray(MetaCopyLibrary $library): array
    {
        return [
            'id' => $library->id,
            'name' => $library->name,
            'type' => $library->type,
            'owner_user_id' => $library->owner_user_id,
            'visibility_scope' => $library->visibility_scope,
            'status' => $library->status,
            'created_at' => $library->created_at?->toIso8601String(),
            'updated_at' => $library->updated_at?->toIso8601String(),
        ];
    }
}
