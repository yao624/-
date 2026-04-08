<?php

namespace App\Services;

use App\Models\MetaTagFolders;
use App\Models\MetaTags;
use App\Models\MetaTagOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaTagFoldersService
{
    /**
     * 获取文件夹列表
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getList(array $filters = [])
    {
        $query = MetaTagFolders::query();

        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $query->where('name', 'like', "%{$filters['keyword']}%");
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['is_del'])) {
            $query->where('is_del', $filters['is_del']);
        }

        return $query->withCount(['metaTags as tag_count'])
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * 创建文件夹
     *
     * @param array $data
     * @return MetaTagFolders
     * @throws \Exception
     */
    public function create(array $data): MetaTagFolders
    {
        try {
            return MetaTagFolders::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create folder', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新文件夹
     *
     * @param MetaTagFolders $folder
     * @param array $data
     * @return MetaTagFolders
     * @throws \Exception
     */
    public function update(MetaTagFolders $folder, array $data): MetaTagFolders
    {
        if (isset($data['parent_id']) && $data['parent_id'] == $folder->id) {
            throw new \Exception('不能将自己设置为父级文件夹');
        }

        try {
            $folder->update($data);
            return $folder->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update folder', ['folder_id' => $folder->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除文件夹
     *
     * @param MetaTagFolders $folder
     * @param bool $force 是否硬删除
     * @return bool
     * @throws \Exception
     */
    public function delete(MetaTagFolders $folder): bool
    {
        DB::beginTransaction();
        try {
            // 删除文件夹下的标签及选项
            $tags = MetaTags::where('folder_id', $folder->id)->get();
            foreach ($tags as $tag) {
                MetaTagOptions::where('tag_id', $tag->id)->delete();
            }
            MetaTags::where('folder_id', $folder->id)->delete();

            // 彻底删除文件夹
            $folder->forceDelete();

            DB::commit();
            Log::info('Folder deleted', ['folder_id' => $folder->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete folder', ['folder_id' => $folder->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
