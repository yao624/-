<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class LanguageController extends BaseController
{
    /**
     * 获取支持的语言列表
     * 用于FB广告的多语言支持
     *
     * @return JsonResponse
     */
    public function getLanguages(): JsonResponse
    {
        try {
            $configPath = config_path('lang.json');

            if (!File::exists($configPath)) {
                return response()->json([
                    'success' => false,
                    'message' => '语言配置文件不存在'
                ], 404);
            }

            $languagesJson = File::get($configPath);
            $languages = json_decode($languagesJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => '语言配置文件格式错误'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => '获取语言列表成功',
                'data' => $languages
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取语言列表失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 根据标签名获取特定语言
     *
     * @param string $labelName
     * @return JsonResponse
     */
    public function getLanguageByLabel(string $labelName): JsonResponse
    {
        try {
            $configPath = config_path('lang.json');

            if (!File::exists($configPath)) {
                return response()->json([
                    'success' => false,
                    'message' => '语言配置文件不存在'
                ], 404);
            }

            $languagesJson = File::get($configPath);
            $languages = json_decode($languagesJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => '语言配置文件格式错误'
                ], 500);
            }

            $language = collect($languages)->firstWhere('label_name', $labelName);

            if (!$language) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到指定的语言'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => '获取语言信息成功',
                'data' => $language
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取语言信息失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取语言的基本信息（简化版）
     * 只返回必要的字段，减少数据传输量
     *
     * @return JsonResponse
     */
    public function getLanguagesSimple(): JsonResponse
    {
        try {
            $configPath = config_path('lang.json');

            if (!File::exists($configPath)) {
                return response()->json([
                    'success' => false,
                    'message' => '语言配置文件不存在'
                ], 404);
            }

            $languagesJson = File::get($configPath);
            $languages = json_decode($languagesJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => '语言配置文件格式错误'
                ], 500);
            }

            // 只返回必要的字段
            $simplifiedLanguages = collect($languages)->map(function ($language) {
                return [
                    'english_name' => $language['english_name'],
                    'native_name' => $language['native_name'],
                    'label_name' => $language['label_name'],
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => '获取简化语言列表成功',
                'data' => $simplifiedLanguages
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取简化语言列表失败: ' . $e->getMessage()
            ], 500);
        }
    }
}