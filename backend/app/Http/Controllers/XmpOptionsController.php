<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class XmpOptionsController extends Controller
{
    public function rejectReasonOptions(Request $request)
    {
        $rows = collect();
        try {
            $hasTable = DB::getSchemaBuilder()->hasTable('meta_reject_reason_options');
            if ($hasTable) {
                $rows = DB::table('meta_reject_reason_options')
                    ->select(['id', 'option_label'])
                    ->where('is_active', 1)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }
        } catch (\Throwable $e) {
            $rows = collect();
        }

        if ($rows->isEmpty()) {
            // DB表未创建时兜底，避免前端页面报错
            $rows = collect([
                (object) ['id' => 1, 'option_label' => '所有渠道：未拒审'],
                (object) ['id' => 2, 'option_label' => 'Meta：未拒审'],
                (object) ['id' => 3, 'option_label' => 'Meta：有拒审记录'],
                (object) ['id' => 4, 'option_label' => 'Google：未拒审'],
                (object) ['id' => 5, 'option_label' => 'Google：有拒审记录'],
                (object) ['id' => 6, 'option_label' => 'Tiktok：未拒审'],
                (object) ['id' => 7, 'option_label' => 'Tiktok：有拒审记录'],
                (object) ['id' => 8, 'option_label' => 'Mintegral：未拒审'],
                (object) ['id' => 9, 'option_label' => 'Mintegral：有拒审记录'],
                (object) ['id' => 10, 'option_label' => 'Unity：未拒审'],
                (object) ['id' => 11, 'option_label' => 'Unity：有拒审记录'],
            ]);
        }

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'value' => (int) $r->id,
                    'label' => (string) $r->option_label,
                ];
            }),
            'totalCount' => $rows->count(),
        ]);
    }

    public function systemTags(Request $request)
    {
        $group = trim((string) $request->query('group', 'system_tag'));
        if ($group === '') $group = 'system_tag';

        $rows = DB::table('meta_system_tags')
            ->select(['id', 'tag_key', 'tag_name', 'tag_group', 'sort_order', 'is_active'])
            ->where('tag_group', $group)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'key' => (string) $r->tag_key,
                    'name' => (string) $r->tag_name,
                    'group' => (string) $r->tag_group,
                ];
            }),
            'totalCount' => $rows->count(),
        ]);
    }

    public function tags(Request $request)
    {
        $rows = DB::table('meta_tags')
            ->select([
                'id',
                'name',
                'remark',
                'folder_id',
                'tag_object',
                'tag_object_level1',
                'sort',
            ])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'name' => $r->name,
                    'remark' => $r->remark,
                    'folder_id' => $r->folder_id !== null ? (int) $r->folder_id : null,
                    'tag_object' => $r->tag_object,
                    'tag_object_level1' => $r->tag_object_level1,
                    'sort' => $r->sort !== null ? (int) $r->sort : null,
                ];
            }),
            'totalCount' => $rows->count(),
        ]);
    }

    public function designers(Request $request)
    {
        $rows = DB::table('meta_designers')
            ->select(['id', 'designer_name as name', 'status'])
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'name' => $r->name,
                ];
            }),
            'totalCount' => $rows->count(),
        ]);
    }

    public function creators(Request $request)
    {
        $rows = DB::table('meta_creators')
            ->select(['id', 'creator_name as name', 'status'])
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'name' => $r->name,
                ];
            }),
            'totalCount' => $rows->count(),
        ]);
    }

    public function materialGroups(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $query = DB::table('meta_material_groups')
            ->select(['id', 'group_name as name']);

        if ($search !== '') {
            $query->where('group_name', 'like', '%' . $search . '%');
        }

        $rows = $query->orderBy('id')->limit(200)->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'name' => $r->name,
                ];
            }),
            'totalCount' => $rows->count(),
        ]);
    }

    public function createMaterialGroup(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return response()->json(['message' => 'name is required'], 422);
        }

        $exists = DB::table('meta_material_groups')->where('group_name', $name)->exists();
        if ($exists) {
            return response()->json(['message' => 'material group already exists'], 422);
        }

        $id = DB::table('meta_material_groups')->insertGetId([
            'group_name' => $name,
            'create_time' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => (string) $id,
                'name' => $name,
            ],
        ]);
    }
}

