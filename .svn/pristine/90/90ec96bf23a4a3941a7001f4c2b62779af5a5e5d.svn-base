<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // 检查是否启用日志记录
        if (!config('request_logging.enabled', true)) {
            return $next($request);
        }

        // 检查是否只记录认证用户的请求
        if (config('request_logging.authenticated_only', false) && !Auth::check()) {
            return $next($request);
        }

        // 检查是否需要记录此请求
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        $response = $next($request);

        try {
            $this->logRequest($request, $response, $startTime);
        } catch (\Exception $e) {
            // 记录日志失败不应该影响正常请求
            Log::error('Failed to log request: ' . $e->getMessage(), [
                'path' => $request->path(),
                'method' => $request->method(),
                'error' => $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * 检查是否应该跳过记录
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $skipPaths = config('request_logging.skip_paths', []);
        $skipMethods = config('request_logging.skip_methods', []);

        $path = $request->path();
        $method = $request->method();

        // 检查路径白名单
        foreach ($skipPaths as $skipPath) {
            if ($this->matchesPattern($path, $skipPath)) {
                return true;
            }
        }

        // 检查方法白名单
        if (in_array($method, $skipMethods)) {
            return true;
        }

        return false;
    }

    /**
     * 匹配路径模式
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // 转义正则表达式特殊字符，但保留通配符
        $pattern = preg_quote($pattern, '/');
        // 将转义后的通配符替换为正则表达式通配符
        $pattern = str_replace('\*', '.*', $pattern);
        return preg_match('/^' . $pattern . '$/i', $path);
    }

    /**
     * 记录请求信息
     */
    private function logRequest(Request $request, Response $response, float $startTime): void
    {
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000); // 转换为毫秒

        $requestBody = $this->getRequestBody($request);
        $queryParameters = $request->query->all();

        RequestLog::create([
            'user_id' => Auth::id(),
            'ip_address' => $this->getClientIp($request),
            'user_agent' => $request->userAgent(),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'query_parameters' => empty($queryParameters) ? null : $queryParameters,
            'request_body' => empty($requestBody) ? null : $requestBody,
            'response_status' => $response->getStatusCode(),
            'response_time' => $responseTime,
            'requested_at' => now(),
        ]);
    }

    /**
     * 获取请求体数据
     */
    private function getRequestBody(Request $request): ?array
    {
        $contentType = $request->header('Content-Type', '');

        // 只记录JSON和表单数据
        if (str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'application/x-www-form-urlencoded') ||
            str_contains($contentType, 'multipart/form-data')) {

            $body = $request->all();

            // 过滤敏感信息
            $body = $this->filterSensitiveData($body);

            return empty($body) ? null : $body;
        }

        return null;
    }

    /**
     * 过滤敏感数据
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = config('request_logging.sensitive_fields', [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'api_key',
            'secret',
            'private_key',
            'credit_card',
            'ssn',
        ]);

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
    }

    /**
     * 获取客户端真实IP
     */
    private function getClientIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?? '0.0.0.0';
    }
}
