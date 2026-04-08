<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Models\MetaTagOptions;
use App\Models\MetaTags;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetaTagOptionsController extends Controller
{
    use ApiResponse;

    /**
     * 创建选项
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'tag_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:500',
            'remark1' => 'nullable|string|max:255',
            'remark2' => 'nullable|string|max:255',
        ]);

        try {
            $option = MetaTagOptions::create([
                'tag_id' => $request->input('tag_id'),
                'parent_id' => $request->input('parent_id'),
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'url' => $request->input('url', ''),
                'remark1' => $request->input('remark1', ''),
                'remark2' => $request->input('remark2', ''),
            ]);

            return $this->success($option);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 获取选项列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $query = MetaTagOptions::query();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        $options = $query->orderBy('id', 'desc')->get();

        return $this->success($options);
    }

    /**
     * 批量更新选项
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'options' => 'required|array',
            'options.*.id' => 'required|integer|exists:meta_tag_options,id',
            'options.*.name' => 'nullable|string|max:100',
            'options.*.description' => 'nullable|string|max:500',
            'options.*.url' => 'nullable|string|max:500',
            'options.*.remark1' => 'nullable|string|max:255',
            'options.*.remark2' => 'nullable|string|max:255',
        ]);

        try {
            $options = $request->input('options');
            $updated = [];

            foreach ($options as $optionData) {
                $option = MetaTagOptions::find($optionData['id']);
                if ($option) {
                    $option->update([
                        'name' => $optionData['name'] ?? $option->name,
                        'description' => $optionData['description'] ?? $option->description,
                        'url' => $optionData['url'] ?? $option->url,
                        'remark1' => $optionData['remark1'] ?? $option->remark1,
                        'remark2' => $optionData['remark2'] ?? $option->remark2,
                    ]);
                    $updated[] = $option->fresh();
                }
            }

            return $this->success($updated);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 批量删除选项
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:meta_tag_options,id',
        ]);

        try {
            $ids = $request->input('ids');

            // 递归获取所有子节点
            $idsToDelete = $this->getAllChildrenIds($ids);

            MetaTagOptions::whereIn('id', $idsToDelete)->delete();

            return $this->success(null, 'Options deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 递归获取所有子节点ID
     *
     * @param array $parentIds
     * @return array
     */
    private function getAllChildrenIds(array $parentIds): array
    {
        $allIds = $parentIds;

        $children = MetaTagOptions::whereIn('parent_id', $parentIds)->pluck('id')->toArray();

        if (!empty($children)) {
            $allIds = array_merge($allIds, $this->getAllChildrenIds($children));
        }

        return array_unique($allIds);
    }

    /**
     * 上传图片
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {

        $request->validate([
            'image' => 'required|image|max:5120', // 5MB = 5120KB
        ]);

        try {
            $dateDir = date('Y-m-d');
            $dir = public_path('uploads/tag-options/' . $dateDir);

            // 确保目录存在
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file = $request->file('image');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $filename);

            $url = '/uploads/tag-options/' . $dateDir . '/' . $filename;


            return $this->success(['url' => $url]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
