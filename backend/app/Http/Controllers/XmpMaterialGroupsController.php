<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class XmpMaterialGroupsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $query = DB::table('meta_material_groups')
            ->select(['id', 'group_name as name', 'group_desc as description', 'create_time']);

        if ($search !== '') {
            $query->where('group_name', 'like', '%' . $search . '%');
        }

        $hasPagination = $request->has('pageNo') || $request->has('pageSize');
        if ($hasPagination) {
            $pageNo = max((int) $request->query('pageNo', 1), 1);
            $pageSize = (int) $request->query('pageSize', 20);
            if ($pageSize < 1) {
                $pageSize = 20;
            }
            if ($pageSize > 200) {
                $pageSize = 200;
            }

            $totalCount = (clone $query)->count();
            $rows = $query
                ->orderByDesc('id')
                ->offset(($pageNo - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
        } else {
            $rows = $query->orderBy('id')->limit(200)->get();
            $totalCount = $rows->count();
        }

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'name' => $r->name,
                    'description' => $r->description,
                    'createTime' => $r->create_time,
                ];
            }),
            'totalCount' => $totalCount,
        ]);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));

        if ($name === '') {
            return response()->json(['message' => 'name is required'], 422);
        }

        $exists = DB::table('meta_material_groups')->where('group_name', $name)->exists();
        if ($exists) {
            return response()->json(['message' => 'material group already exists'], 422);
        }

        $id = DB::table('meta_material_groups')->insertGetId([
            'group_name' => $name,
            'group_desc' => $description !== '' ? $description : null,
            'create_time' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => (string) $id,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $name = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));

        if ($name === '') {
            return response()->json(['message' => 'name is required'], 422);
        }

        $exists = DB::table('meta_material_groups')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['message' => 'material group not found'], 404);
        }

        $duplicate = DB::table('meta_material_groups')
            ->where('group_name', $name)
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'material group already exists'], 422);
        }

        DB::table('meta_material_groups')
            ->where('id', $id)
            ->update([
                'group_name' => $name,
                'group_desc' => $description !== '' ? $description : null,
            ]);

        return response()->json([
            'data' => [
                'id' => (string) $id,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
            ],
        ]);
    }

    public function destroy($id)
    {
        $exists = DB::table('meta_material_groups')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['message' => 'material group not found'], 404);
        }

        DB::table('meta_material_groups')->where('id', $id)->delete();

        return response()->json(['message' => 'ok']);
    }
}

