<?php

namespace App\Http\Controllers;

use App\Models\BusinessManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessManagerController extends Controller
{
    private function bmLogColumns(): array
    {
        static $cached = null;
        if (is_array($cached)) return $cached;
        try {
            $cached = Schema::getColumnListing('business_manager_operation_logs');
        } catch (\Throwable $e) {
            $cached = [];
        }
        return $cached;
    }

    public function operationLogs(Request $request): JsonResponse
    {
        $page = max((int) $request->query('page', 1), 1);
        $pageSize = max((int) $request->query('pageSize', 20), 1);
        $search = trim((string) $request->query('search', ''));
        $businessManagerId = $request->query('business_manager_id');
        $operationType = trim((string) $request->query('operation_type', ''));
        $status = trim((string) $request->query('status', ''));
        $columns = $this->bmLogColumns();

        $query = DB::table('business_manager_operation_logs as l')
            ->leftJoin('business_managers as bm', 'bm.id', '=', 'l.business_manager_id');
        if ($businessManagerId !== null && $businessManagerId !== '') {
            $query->where('l.business_manager_id', (int) $businessManagerId);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('bm.name', 'like', "%{$search}%")
                    ->orWhere('bm.business_id', 'like', "%{$search}%")
                    ->orWhere('l.business_manager_id', 'like', "%{$search}%");
            });
        }
        if ($operationType !== '') {
            $field = in_array('operation_type', $columns, true) ? 'operation_type' : (in_array('action_type', $columns, true) ? 'action_type' : null);
            if ($field) $query->where("l.{$field}", $operationType);
        }
        if ($status !== '') {
            $field = in_array('status', $columns, true) ? 'status' : (in_array('result_status', $columns, true) ? 'result_status' : null);
            if ($field) $query->where("l.{$field}", $status);
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->select([
                'l.*',
                'bm.name as business_manager_name',
            ])
            ->get()
            ->map(function ($row) use ($columns) {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'business_manager_id' => $row->business_manager_id ?? null,
                    'business_manager_name' => $row->business_manager_name ?? '',
                    'operation_type' => $row->operation_type ?? ($row->action_type ?? ''),
                    'status' => $row->status ?? ($row->result_status ?? ''),
                    'message' => $row->message ?? ($row->error_message ?? ''),
                    'operator_name' => $row->operator_name ?? ($row->operator ?? ''),
                    'created_at' => $row->created_at ?? null,
                    'request_data' => in_array('request_data', $columns, true) ? $row->request_data : null,
                    'response_data' => in_array('response_data', $columns, true) ? $row->response_data : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rows,
            'total' => $total,
        ]);
    }

    public function operationLogsStats(): JsonResponse
    {
        $columns = $this->bmLogColumns();
        $statusField = in_array('status', $columns, true) ? 'status' : (in_array('result_status', $columns, true) ? 'result_status' : null);

        $completed = 0;
        $failed = 0;
        $running = 0;
        if ($statusField !== null) {
            $completed = (int) DB::table('business_manager_operation_logs')->where($statusField, 'success')->count();
            $failed = (int) DB::table('business_manager_operation_logs')->whereIn($statusField, ['failed', 'error'])->count();
            $running = (int) DB::table('business_manager_operation_logs')->where($statusField, 'running')->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'business_managers' => [
                    'total' => (int) BusinessManager::query()->count(),
                ],
                'sync_status' => [
                    'completed' => $completed,
                    'failed' => $failed,
                    'running' => $running,
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $page = max((int) $request->query('page', 1), 1);
        $pageSize = max((int) $request->query('pageSize', 20), 1);
        $search = trim((string) $request->query('search', ''));
        $businessId = trim((string) $request->query('business_id', ''));
        $status = trim((string) $request->query('status', ''));

        $query = BusinessManager::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_id', 'like', "%{$search}%");
            });
        }
        if ($businessId !== '') $query->where('business_id', 'like', "%{$businessId}%");
        if ($status !== '') $query->where('status', $status);

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'total' => $total,
        ]);
    }

    public function show(BusinessManager $businessManager): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $businessManager]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'business_id' => 'required|string|max:64|unique:business_managers,business_id',
            'access_token' => 'nullable|string',
            'sync_frequency' => 'nullable|integer|min:1|max:168',
            'status' => 'nullable|in:active,inactive,error',
            'use_proxy' => 'nullable|boolean',
            'proxy_ip' => 'nullable|string|max:100',
            'proxy_port' => 'nullable|string|max:20',
            'proxy_username' => 'nullable|string|max:100',
            'proxy_password' => 'nullable|string|max:255',
        ]);

        $bm = BusinessManager::create(array_merge([
            'sync_frequency' => 24,
            'status' => 'active',
            'use_proxy' => false,
            'ad_accounts_count' => 0,
            'closecard' => 0,
        ], $data));

        $this->logOperation($bm->id, 'create', 'success', 'created', $request->all(), null, null, $bm->toArray());

        return response()->json(['success' => true, 'data' => $bm]);
    }

    public function update(Request $request, BusinessManager $businessManager): JsonResponse
    {
        $before = $businessManager->toArray();
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:200',
            'business_id' => 'sometimes|required|string|max:64|unique:business_managers,business_id,' . $businessManager->id,
            'access_token' => 'nullable|string',
            'sync_frequency' => 'nullable|integer|min:1|max:168',
            'status' => 'nullable|in:active,inactive,error',
            'use_proxy' => 'nullable|boolean',
            'proxy_ip' => 'nullable|string|max:100',
            'proxy_port' => 'nullable|string|max:20',
            'proxy_username' => 'nullable|string|max:100',
            'proxy_password' => 'nullable|string|max:255',
        ]);

        $businessManager->fill($data)->save();
        $this->logOperation($businessManager->id, 'update', 'success', 'updated', $request->all(), null, $before, $businessManager->toArray());

        return response()->json(['success' => true, 'data' => $businessManager->fresh()]);
    }

    public function destroy(BusinessManager $businessManager): JsonResponse
    {
        $before = $businessManager->toArray();
        $businessManager->delete();
        $this->logOperation($businessManager->id, 'delete', 'success', 'deleted', null, null, $before, null);
        return response()->json(['success' => true]);
    }

    public function testToken(BusinessManager $businessManager): JsonResponse
    {
        $this->logOperation($businessManager->id, 'test_token', 'success', 'token test mocked success');
        return response()->json(['success' => true, 'message' => 'token test success (mock)']);
    }

    public function sync(BusinessManager $businessManager): JsonResponse
    {
        $this->logOperation($businessManager->id, 'sync', 'running', 'sync task submitted');
        return response()->json(['success' => true, 'message' => 'sync task submitted']);
    }

    public function checkStatus(BusinessManager $businessManager): JsonResponse
    {
        $this->logOperation($businessManager->id, 'check_status', 'success', 'status check mocked success');
        return response()->json(['success' => true, 'message' => 'status check success (mock)']);
    }

    public function testAssign(Request $request, BusinessManager $businessManager): JsonResponse
    {
        $data = $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
        ]);
        $results = array_map(static fn($email) => ['email' => $email, 'status' => 'success'], $data['emails']);
        $this->logOperation($businessManager->id, 'test_assign', 'success', 'assign mocked success', $data, ['results' => $results]);
        return response()->json(['success' => true, 'data' => ['results' => $results]]);
    }

    public function getLockCardSetting(BusinessManager $businessManager): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['closecard' => (int) $businessManager->closecard]]);
    }

    public function updateLockCardSetting(Request $request, BusinessManager $businessManager): JsonResponse
    {
        $data = $request->validate(['closecard' => 'required|integer|in:0,1']);
        $businessManager->closecard = (int) $data['closecard'];
        $businessManager->save();
        $this->logOperation($businessManager->id, 'lock_card', 'success', 'lock card setting updated', $data);
        return response()->json(['success' => true, 'data' => ['closecard' => (int) $businessManager->closecard]]);
    }

    private function logOperation(
        ?int $businessManagerId,
        string $operationType,
        string $status,
        ?string $message = null,
        $requestData = null,
        $responseData = null,
        $beforeData = null,
        $afterData = null
    ): void {
        $user = Auth::user();
        $columns = $this->bmLogColumns();
        $bm = null;
        if ($businessManagerId !== null) {
            $bm = BusinessManager::query()->find($businessManagerId);
        }
        $payload = [];
        $put = static function (array &$arr, array $cols, string $key, $value): void {
            if (in_array($key, $cols, true)) $arr[$key] = $value;
        };

        $put($payload, $columns, 'business_manager_id', $businessManagerId);
        $put($payload, $columns, 'business_manager_name', $bm?->name);
        $put($payload, $columns, 'business_id', $bm?->business_id);
        $put($payload, $columns, 'operation_type', $operationType);
        $put($payload, $columns, 'action_type', $operationType);
        $put($payload, $columns, 'operator_id', $user?->id);
        $put($payload, $columns, 'operator_name', $user?->name);
        $put($payload, $columns, 'operator', $user?->name);
        $put($payload, $columns, 'status', $status);
        $put($payload, $columns, 'operation_status', $status);
        $put($payload, $columns, 'result_status', $status);
        $put($payload, $columns, 'message', $message);
        $put($payload, $columns, 'error_message', $message);
        $put($payload, $columns, 'request_data', $requestData);
        $put($payload, $columns, 'response_data', $responseData);
        $put($payload, $columns, 'before_data', $beforeData);
        $put($payload, $columns, 'after_data', $afterData);

        if (in_array('updated_at', $columns, true) && !isset($payload['updated_at'])) $payload['updated_at'] = now();
        if (in_array('created_at', $columns, true) && !isset($payload['created_at'])) $payload['created_at'] = now();

        if (!empty($payload)) {
            DB::table('business_manager_operation_logs')->insert($payload);
        }
    }
}
