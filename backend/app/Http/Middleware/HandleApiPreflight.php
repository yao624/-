<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 浏览器跨域预检 OPTIONS 不应进入 auth:sanctum / tenant 等中间件，
 * 否则易出现 401/500 且响应缺少 CORS 头，前端表现为「请求不到接口」。
 */
class HandleApiPreflight
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', Response::HTTP_NO_CONTENT);
        }

        return $next($request);
    }
}
