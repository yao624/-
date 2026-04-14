<?php

namespace App\Http\Controllers;

use App\Services\FilterOptionService;
use Illuminate\Http\Request;

class PublicController extends BaseController
{
    public function __construct(
        private FilterOptionService $filterOptionService
    ) {}

    /**
     * 获取筛选选项数据
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterOptionData(Request $request)
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'required|string',
            'language' => 'string|in:en,zh',
        ]);

        $keys = $request->input('keys', []);
        $language = $request->input('language', 'zh');

        $allFilterOptions = $this->filterOptionService->getAllFilterOptions($language, $keys);

        $result = [];
        foreach ($keys as $key) {
            if (isset($allFilterOptions[$key])) {
                $result[] = $allFilterOptions[$key];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
