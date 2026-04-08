<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();

        // 使用自定义的 hasPermission 方法检查权限
//        if (!$user || !$user->hasPermission($permission)) {
//            return response()->json([
//                'code' => 403,
//                'message' => '无访问权限.',
//            ], 403);
//        }

        return $next($request);
    }
}
