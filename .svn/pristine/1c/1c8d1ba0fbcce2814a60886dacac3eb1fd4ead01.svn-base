<?php

namespace App\Services;

use App\Models\MetaTagFolders;
use App\Models\MetaTagOptions;
use App\Models\MetaTags;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaTagsService
{
    /**
     * 获取标签列表
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getList(array $filters = [])
    {
        $query = MetaTags::query();

        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $query->where('name', 'like', "%{$filters['keyword']}%");
        }

        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['tag_object']) && $filters['tag_object'] !== '') {
            $query->where('tag_object', $filters['tag_object']);
        }

        if (isset($filters['tag_object_level1']) && $filters['tag_object_level1'] !== '') {
            $query->where('tag_object_level1', $filters['tag_object_level1']);
        }

        return $query->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * 创建标签
     *
     * @param array $data
     * @return MetaTags
     * @throws \Exception
     */
    public function create(array $data): MetaTags
    {
        try {
            DB::beginTransaction();

            // 处理 folder_id 为 0 的情况
            if (isset($data['folder_id']) && $data['folder_id'] == 0) {
                $folder = MetaTagFolders::create([
                    'name' => '默认文件夹',
                    'parent_id' => 0,
                    'user_id' => auth()->id() ?? 0,
                    'sort' => 0,
                    'is_del' => 0,
                ]);
                $data['folder_id'] = $folder->id;
            }

            // 提取 options 字段
            $options = $data['options'] ?? [];
            unset($data['options']);

            // 创建标签
            $tag = MetaTags::create($data);

            // 创建选项记录
            if (!empty($options)) {
                foreach ($options as $option) {
                    MetaTagOptions::create([
                        'tag_id' => $tag->id,
                        'name' => $option['name'] ?? '',
                        'description' => $option['description'] ?? '',
                        'url' => $option['url'] ?? '',
                        'remark1' => $option['remark1'] ?? '',
                        'remark2' => $option['remark2'] ?? '',
                    ]);
                }
            }

            DB::commit();

            return $tag->load('options');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create tag', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新标签
     *
     * @param MetaTags $tag
     * @param array $data
     * @return MetaTags
     * @throws \Exception
     */
    public function update(MetaTags $tag, array $data): MetaTags
    {
        try {
            DB::beginTransaction();

            // 提取 options 字段
            $options = $data['options'] ?? [];
            unset($data['options']);

            // 更新标签基本信息
            $tag->update($data);

            // 获取当前选项的 id 列表
            $currentOptionIds = MetaTagOptions::where('tag_id', $tag->id)->pluck('id')->toArray();
            $newOptionIds = [];

            if (!empty($options)) {
                foreach ($options as $option) {
                    if (isset($option['id']) && in_array($option['id'], $currentOptionIds)) {
                        // 存在则更新
                        MetaTagOptions::where('id', $option['id'])->update([
                            'name' => $option['name'] ?? '',
                            'description' => $option['description'] ?? '',
                            'url' => $option['url'] ?? '',
                            'remark1' => $option['remark1'] ?? '',
                            'remark2' => $option['remark2'] ?? '',
                        ]);
                        $newOptionIds[] = $option['id'];
                    } else {
                        // 不存在则创建
                        $newOption = MetaTagOptions::create([
                            'tag_id' => $tag->id,
                            'name' => $option['name'] ?? '',
                            'description' => $option['description'] ?? '',
                            'url' => $option['url'] ?? '',
                            'remark1' => $option['remark1'] ?? '',
                            'remark2' => $option['remark2'] ?? '',
                        ]);
                        $newOptionIds[] = $newOption->id;
                    }
                }
            }

            // 删除不在新列表中的旧选项
            if (!empty($newOptionIds)) {
                MetaTagOptions::where('tag_id', $tag->id)->whereNotIn('id', $newOptionIds)->delete();
            }

            DB::commit();

            return $tag->load('options');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update tag', ['tag_id' => $tag->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除标签
     *
     * @param MetaTags $tag
     * @return bool
     * @throws \Exception
     */
    public function delete(MetaTags $tag): bool
    {
        DB::beginTransaction();
        try {
            // 删除标签的选项
            MetaTagOptions::where('tag_id', $tag->id)->delete();

            // 删除标签
            $tag->delete();

            DB::commit();
            Log::info('Tag deleted', ['tag_id' => $tag->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete tag', ['tag_id' => $tag->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
