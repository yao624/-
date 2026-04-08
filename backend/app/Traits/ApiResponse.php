<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;


trait ApiResponse
{
    /**
     * 成功响应
     * @param mixed $data 返回数据
     * @param string $message 提示消息
     * @param int $code HTTP状态码
     * @return JsonResponse
     */
    protected function success(mixed $data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        return $this->jsonResponse($data, $message, true, $code);
    }

    /**
     * 失败响应
     * @param string $message 错误消息
     * @param int $code HTTP错误码
     * @param mixed $data 附加数据
     * @return JsonResponse
     */
    protected function fail(string $message = '操作失败', int $code = 500, mixed $data = null): JsonResponse
    {
        return $this->jsonResponse($data, $message, false, $code);
    }

    /** 统一的JSON响应结构
     * @param $data
     * @param string $message
     * @param bool $status
     * @return JsonResponse
     */
    private function jsonResponse($data, string $message, bool $status, $code): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $code);
    }


}
