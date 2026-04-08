<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetTenantDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 获取当前认证用户
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // 从 Token 中获取 tenant_uuid
        // 注意：这需要在步骤8中修改 PersonalAccessToken 模型，添加 tenant_uuid 字段
        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Token not found'
            ], 401);
        }


        return $next($request);
    }
}

