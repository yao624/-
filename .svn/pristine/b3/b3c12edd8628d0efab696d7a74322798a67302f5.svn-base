<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RoleController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $pageSize = $request->get('pageSize', 10);
            $pageNo = $request->get('pageNo', 1);

            $query = Role::with('permissions');

            if ($request->has('name') && $request->get('name')) {
                $query->where('name', 'like', '%' . $request->get('name') . '%');
            }

            if ($request->has('status') && $request->get('status') !== '') {
                $query->where('status', $request->get('status'));
            }

            $query->orderBy('id', 'desc');

            $roles = $query->paginate($pageSize, ['*'], 'page', $pageNo);

            return response()->json([
                'status' => true,
                'data' => $roles->items(),
                'pageSize' => $roles->perPage(),
                'pageNo' => $roles->currentPage(),
                'totalPage' => $roles->lastPage(),
                'totalCount' => $roles->total(),
            ]);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'description' => 'nullable|max:255',
                'status' => 'nullable|integer',
                'permission_ids' => 'nullable|array',
                'permission_ids.*' => 'integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors(),
                ], 401);
            }

            $role = Role::create([
                'name' => $request->get('name'),
                'description' => $request->get('description', ''),
                'status' => $request->get('status', 1),
            ]);

            if ($request->has('permission_ids')) {
                $role->permissions()->sync($request->get('permission_ids'));
            }

            return response()->json([
                'status' => true,
                'message' => 'Role created successfully',
                'data' => $role->load('permissions'),
            ], 200);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::with('permissions')->find($id);

            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $role,
            ]);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|max:50',
                'description' => 'nullable|max:255',
                'status' => 'nullable|integer',
                'permission_ids' => 'nullable|array',
                'permission_ids.*' => 'integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors(),
                ], 401);
            }

            $role->update($request->only([
                'name', 'description', 'status'
            ]));

            if ($request->has('permission_ids')) {
                $role->permissions()->sync($request->get('permission_ids'));
            }

            return response()->json([
                'status' => true,
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions'),
            ]);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found',
                ], 404);
            }

            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();

            return response()->json([
                'status' => true,
                'message' => 'Role deleted successfully',
            ]);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function assignPermissions(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors(),
                ], 401);
            }

            $role->permissions()->sync($request->get('permission_ids'));

            return response()->json([
                'status' => true,
                'message' => 'Permissions assigned successfully',
                'data' => $role->load('permissions'),
            ]);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
