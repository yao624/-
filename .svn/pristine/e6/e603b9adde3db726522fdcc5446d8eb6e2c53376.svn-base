<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMetaTagFolderRequest;
use App\Models\MetaTagFolders;
use App\Services\MetaTagFoldersService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaTagFoldersController extends Controller
{
    use ApiResponse;

    protected MetaTagFoldersService $service;

    public function __construct(MetaTagFoldersService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取文件夹列表（树形结构）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'user_id' => Auth::id(),
            'keyword' => $request->input('keyword')
        ];

        $folders = $this->service->getList($filters);

        $folderResult = $folders->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'count' => $folder->tag_count ?? 0,
                'canDelete' => false,
                'isExpanded' => false,
                'childrenLoaded' => true,
                'children' => [],
            ];
        });

        // 根据 folders 的 id 获取标签
        $folderIds = $folders->pluck('id')->toArray();
        $tags = \App\Models\MetaTags::whereIn('folder_id', $folderIds)->with('options')->get();

        $tagResult = $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'folder_id' => $tag->folder_id,
                'tag_object' => $tag->tag_object,
                'tag_object_level1' => $tag->tag_object_level1,
                'options' => $tag->options,
            ];
        });

        return $this->success([
            'folders' => $folderResult,
            'tags' => $tagResult,
        ]);
    }

    /**
     * 创建文件夹
     *
     * @param StoreMetaTagFolderRequest $request
     * @return JsonResponse
     */
    public function create(StoreMetaTagFolderRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();
            $folder = $this->service->create($data);

            return $this->success($folder);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 更新文件夹
     *
     * @param StoreMetaTagFolderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(StoreMetaTagFolderRequest $request, int $id): JsonResponse
    {
        $folder = MetaTagFolders::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$folder) {
            return $this->fail('Folder not found', 404);
        }

        try {
            $data = $request->validated();
            $updated = $this->service->update($folder, $data);

            return $this->success($updated, 'Folder updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 删除文件夹
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $folder = MetaTagFolders::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$folder) {
            return $this->fail('Folder not found', 404);
        }

        try {
            $this->service->delete($folder);

            return $this->success(null, 'Folder deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 获取文件夹详情
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $folder = MetaTagFolders::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$folder) {
            return $this->fail('Folder not found', 404);
        }

        return $this->success($folder);
    }
}
