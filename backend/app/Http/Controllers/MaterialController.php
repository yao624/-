<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbPixelResource;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\NetworkResource;
use App\Models\Material;
use App\Models\Network;
use App\Models\Tag;
use App\Models\User;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbPage;
use App\Utils\FbUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Pipeline;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
            'notes' => $request->get('notes'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

//        $materials = Material::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
//            ->with('sharedWith')->whereHas('sharedWith', function ($query) {
//                $query->where('user_id', auth()->id());
//            })
//            ->orderBy('id', $sortDirection)
//            ->paginate($pageSize, ['*'], 'page', $pageNo);

        $materials = Material::searchByTagNames($tagNames)
            ->search($searchableFields)
            ->with('sharedWith')
            ->where(function ($query) {
                $query->where('user_id', auth()->id()) // 当前用户创建的 Material
                ->orWhereHas('sharedWith', function ($subQuery) {
                    $subQuery->where('user_id', auth()->id()); // 被分享给当前用户的 Material
                });
            });

        // 添加 type 查询参数支持
        if ($request->has('type') && in_array($request->get('type'), ['image', 'video'])) {
            $materials->where('type', $request->get('type'));
        }

        $materials = $materials->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => MaterialResource::collection($materials->items()),
            'pageSize' => $materials->perPage(),
            'pageNo' => $materials->currentPage(),
            'totalPage' => $materials->lastPage(),
            'totalCount' => $materials->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('file')) {
            $file = $request->file('file');
            Log::debug("mime: {$file->getMimeType()}");
        }
        $request->validate([
            'file' => 'required|mimes:zip,png,jpg,jpeg,mp4,mov,qt,ogg|max:102400',
            'name' => 'required',
            'notes' => 'nullable',
            'new_tags' => [
                'sometimes', // 只有当 new_tags 存在时才应用后续的规则
                'array',     // new_tags 必须是一个数组
            ],
            'new_tags.*' => [
                'string',    // new_tags 数组中的每个元素必须是字符串
                'distinct',  // new_tags 数组中的每个元素必须是唯一的
                Rule::notIn(Tag::pluck('name')->toArray()), // new_tags 数组中的每个元素不能在 Tag 模型的 name 字段值中
            ],
        ]);

        if ($request->hasFile('file')) {

            $userId = auth()->id();

            $file = $request->file('file');
            $folder = now()->format('Y-m-d'); // 今天的日期
            $originalFilename = $file->getClientOriginalName();
            $hashName = $file->hashName();

            // 获取hashName的文件名部分（不包括扩展名）
            $hashNameWithoutExt = pathinfo($hashName, PATHINFO_FILENAME);
            // 获取originalFilename的扩展名部分
            $originalExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            // 创建新的文件名
            $hashName = $hashNameWithoutExt . '.' . $originalExtension;

            $path = $file->storeAs('public/ad_materials/' . $folder, $hashName);

            // 根据文件扩展名设置 type
            $extension = strtolower($originalExtension);
            $type = null;
            if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
                $type = 'image';
            } elseif (in_array($extension, ['mp4', 'mov', 'qt', 'ogg'])) {
                $type = 'video';
            }

            $adMaterial = new Material();
//            $adMaterial->tag = $request->input('tags');
            $adMaterial->name = $request->input('name');
            $adMaterial->notes = $request->input('notes');
            $adMaterial->filename = $hashName;
            $adMaterial->original_filename = $originalFilename;
            $adMaterial->filepath = str_replace('public/', '', $path);
            $adMaterial->user_id = $userId;
            $adMaterial->type = $type;
            $adMaterial->save();

            $newTags = collect($request->get('new_tags'))->map(function ($name) {
                return Tag::query()->firstOrCreate(['name' => $name])->id;
            });

            // 使用已经有的 tags
            $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
            $adMaterial->tags()->sync($tagIds);

            $createdMaterials = [$adMaterial]; // 存储所有创建的Material

            if ($file->getClientOriginalExtension() === 'zip') {
                // 如果是zip文件，解压
                $zip = new \ZipArchive;
                if ($zip->open(storage_path('app/'.$path)) === TRUE) {
                    $unzipDir = 'public/ad_materials/' . $folder . '/' . pathinfo($originalFilename, PATHINFO_FILENAME);
                    $zip->extractTo(storage_path('app/'.$unzipDir));
                    $zip->close();

                    // 遍历解压后的文件，为每个文件创建一个AdMaterial对象
                    $files = Storage::allFiles($unzipDir);
                    foreach ($files as $filePath) {
                        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                        if (in_array($extension, ['mp4', 'jpg', 'jpeg', 'png'])) {
                            $originalName = pathinfo($filePath, PATHINFO_BASENAME);
                            $hashName = Str::uuid() . '.' . $extension;
                            Storage::move($filePath, $unzipDir . '/' . $hashName);

                            // 根据文件扩展名设置 type
                            $extensionLower = strtolower($extension);
                            $type = null;
                            if (in_array($extensionLower, ['png', 'jpg', 'jpeg'])) {
                                $type = 'image';
                            } elseif (in_array($extensionLower, ['mp4'])) {
                                $type = 'video';
                            }

                            $subMaterial = new Material();
                            $subMaterial->name = $request->input('name');
                            $subMaterial->notes = $request->input('notes');
                            $subMaterial->filename = $hashName;
                            $subMaterial->original_filename = $originalName;
                            $relativePath = str_replace('public/', '', $unzipDir . '/' . $hashName);
                            $subMaterial->filepath = $relativePath;
                            $subMaterial->user_id = $userId;
                            $subMaterial->type = $type;
                            $subMaterial->save();

                            $newTags = collect($request->get('new_tags'))->map(function ($name) {
                                return Tag::query()->firstOrCreate(['name' => $name])->id;
                            });

                            // 使用已经有的 tags
                            $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
                            $subMaterial->tags()->sync($tagIds);

                            $createdMaterials[] = $subMaterial; // 添加到创建的材料列表
                        }
                    }
                }
            }

            // 返回创建的材料信息
            if (count($createdMaterials) === 1) {
                // 单个文件，返回单个资源
                return response()->json([
                    'success' => true,
                    'message' => 'Material created successfully',
                    'data' => new MaterialResource($createdMaterials[0])
                ], 201);
            } else {
                // 多个文件（zip），返回资源数组
                return response()->json([
                    'success' => true,
                    'message' => 'Materials created successfully',
                    'data' => MaterialResource::collection($createdMaterials),
                    'count' => count($createdMaterials)
                ], 201);
            }
        }

        return response()->json(['message' => 'File not uploaded'], 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        // 获取当前用户的 ID
        $currentUserId = auth()->id();

        // 检查当前用户是否是材料的创建者或被分享的用户
        if ($material->user_id !== $currentUserId && !$material->isSharedWith($currentUserId)) {
            // 如果用户没有权限，返回403 Forbidden
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new MaterialResource($material);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        // 检查当前用户是否为材料的创建者
        if ($material->user_id !== auth()->id()) {
            // 如果用户没有权限，返回403 Forbidden
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 验证请求数据
        $validatedData = $request->validate([
            'name' => 'string',
            'notes' => 'string|nullable'
        ]);

        // 更新模型实例
        $material->update($validatedData);
//        // 创建新的 Tag
//        $newTags = collect($request->get('new_tags'))->map(function ($name) {
//            return Tag::query()->firstOrCreate(['name' => $name])->id;
//        });
//
//        // 使用已经有的 tags
//        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
//        $material->tags()->sync($tagIds);

        // 返回资源
        return new MaterialResource($material);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        // 检查当前用户是否为材料的创建者
        if ($material->user_id !== auth()->id()) {
            // 如果用户没有权限，返回403 Forbidden
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 用户有权限，执行删除操作
        $material->delete();

        return response()->json(null, 204);
    }

    public function share(Request $request)
    {
        $request->validate([
            'user_emails' => 'required|array',
            'user_emails.*' => 'exists:users,email', // 确保每个用户ID存在
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:materials,id', // 确保每个材料ID存在
        ]);

        $userIds = User::whereIn('email', $request->user_emails)->pluck('id');

        foreach ($request->resource_ids as $materialId) {
            $material = Material::findOrFail($materialId);

            // 检查当前用户是否为该材料的拥有者
            if ($material->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $material->sharedWith()->syncWithoutDetaching($userIds);

        }

        return response()->json(['message' => 'Materials shared successfully.']);
    }

    public function unshare(Request $request)
    {
        $request->validate([
            'user_emails' => 'required|array',
            'user_emails.*' => 'exists:users,email', // 确保每个用户ID存在
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:materials,id', // 确保每个材料ID存在
        ]);

        $userIds = User::whereIn('email', $request->user_emails)->pluck('id');

        foreach ($request->resource_ids as $materialId) {
            $material = Material::findOrFail($materialId);

            // 检查当前用户是否为该材料的拥有者
            if ($material->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $material->sharedWith()->detach($userIds);
        }

        return response()->json(['message' => 'Materials unshared successfully.']);
    }

    public function uploadToFb(Request $request)
    {
        // 验证请求参数
        $request->validate([
            '*.ad_account' => 'required|string',
            '*.material_id' => 'required|exists:materials,id',
        ]);

        $uploadItems = $request->all();
        $results = [];

        // 使用Pipeline进行批量处理
        $results = collect($uploadItems)->map(function ($item) {
            return $this->processSingleUpload($item);
        })->toArray();

        // 检查是否有成功的上传
        $successCount = collect($results)->where('success', true)->count();
        $totalCount = count($results);

        return [
            'success' => true,
            'message' => "上传完成，成功: {$successCount}/{$totalCount}",
            'data' => $results
        ];
    }

    private function processSingleUpload(array $item)
    {
        try {
            $adAccountSourceId = $item['ad_account'];
            $materialId = $item['material_id'];

            // 1. 查找Material
            $material = Material::find($materialId);
            if (!$material) {
                return [
                    'material_id' => $materialId,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => '',
                    'success' => false,
                    'error' => 'Material not found'
                ];
            }

            // 2. 查找FbAdAccount
            $fbAdAccount = FbAdAccount::where('source_id', $adAccountSourceId)->first();
            if (!$fbAdAccount) {
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => $material->type ?? '',
                    'success' => false,
                    'error' => 'Ad account not found'
                ];
            }

            // 3. 获取对应的FbApiToken (token_type=1, active=true)
            $fbApiToken = FbApiToken::where('token_type', 1)
                ->where('active', true)
                ->whereHas('adAccounts', function($query) use ($fbAdAccount) {
                    $query->where('fb_ad_accounts.id', $fbAdAccount->id);
                })->first();

            if (!$fbApiToken) {
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => $material->type ?? '',
                    'success' => false,
                    'error' => 'No valid API token found'
                ];
            }

            // 4. 根据素材类型进行上传
            if ($material->type === 'image') {
                return $this->uploadImage($material, $fbAdAccount, $fbApiToken, $adAccountSourceId);
            } elseif ($material->type === 'video') {
                return $this->uploadVideo($material, $fbAdAccount, $fbApiToken, $adAccountSourceId);
            } else {
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => $material->type ?? '',
                    'success' => false,
                    'error' => 'Unsupported material type: ' . $material->type
                ];
            }

        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return [
                'material_id' => $materialId,
                'ad_account' => $adAccountSourceId,
                'hash' => '',
                'url' => '',
                'type' => '',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function uploadImage(Material $material, FbAdAccount $fbAdAccount, FbApiToken $fbApiToken, string $adAccountSourceId)
    {
        try {
            // 构建文件路径
            $filePath = storage_path('app/public/' . $material->filepath);

            if (!file_exists($filePath)) {
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => 'image',
                    'success' => false,
                    'error' => 'File not found'
                ];
            }

            // 调用Facebook API上传图片
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/act_{$adAccountSourceId}/adimages";

            $body = [
                'file_path' => $filePath,
                'file_name' => $material->filename
            ];

            // 直接传入null作为fbAccount，因为我们使用api_token模式
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                null,
                'POST',
                $body,
                'create_adimage',
                $fbApiToken->token
            );

            // 将Collection转换为数组便于操作
            if ($response instanceof \Illuminate\Support\Collection) {
                $response = $response->toArray();
            }

            if ($response['success'] && isset($response['images'])) {
                // 获取第一个图片数据（Facebook API返回的是关联数组）
                $imageData = array_values($response['images'])[0];
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => $imageData['hash'] ?? '',
                    'url' => $imageData['url'] ?? '',
                    'type' => 'image',
                    'success' => true
                ];
            } else {
                Log::error('failedresponse: ' . json_encode($response));
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => 'image',
                    'success' => false,
                    'error' => 'Failed to upload image to Facebook'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Image upload error: ' . $e->getMessage());
            return [
                'material_id' => $material->id,
                'ad_account' => $adAccountSourceId,
                'hash' => '',
                'url' => '',
                'type' => 'image',
                'success' => false,
                'error' => 'Failed to upload image to Facebook' //$e->getMessage()
            ];
        }
    }

    private function uploadVideo(Material $material, FbAdAccount $fbAdAccount, FbApiToken $fbApiToken, string $adAccountSourceId)
    {
        try {
            // 构建文件路径
            $filePath = storage_path('app/public/' . $material->filepath);

            if (!file_exists($filePath)) {
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => 'video',
                    'success' => false,
                    'error' => 'File not found'
                ];
            }

            // 调用Facebook API上传视频到ad account media library
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/act_{$adAccountSourceId}/advideos";

            $body = [
                'file_path' => $filePath,
                'file_name' => $material->filename
            ];

            // 直接传入null作为fbAccount，因为我们使用api_token模式
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                null,
                'POST',
                $body,
                'upload_video',
                $fbApiToken->token
            );

            // 将Collection转换为数组便于操作
            if ($response instanceof \Illuminate\Support\Collection) {
                $response = $response->toArray();
            }

            if ($response['success'] && isset($response['id'])) {
                $videoId = $response['id'];

                // 获取视频的source URL，使用ad account token
                $videoUrl = $this->getVideoSourceUrl($videoId, $fbApiToken->token);

                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => $videoId,
                    'url' => $videoUrl,
                    'type' => 'video',
                    'success' => true
                ];
            } else {
                return [
                    'material_id' => $material->id,
                    'ad_account' => $adAccountSourceId,
                    'hash' => '',
                    'url' => '',
                    'type' => 'video',
                    'success' => false,
                    'error' => 'Failed to upload video to Facebook'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Video upload error: ' . $e->getMessage());
            return [
                'material_id' => $material->id,
                'ad_account' => $adAccountSourceId,
                'hash' => '',
                'url' => '',
                'type' => 'video',
                'success' => false,
                'error' => 'Failed to upload video to Facebook' //$e->getMessage()
            ];
        }
    }

    private function getVideoSourceUrl(string $videoId, string $accessToken)
    {
        try {
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/{$videoId}";
            $query = [
                'access_token' => $accessToken,
                'fields' => 'source'
            ];
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                $query,
                'GET',
                null,
                '',
                $accessToken
            );

            // 将Collection转换为数组便于操作
            if ($response instanceof \Illuminate\Support\Collection) {
                $response = $response->toArray();
            }

            if ($response['success'] && isset($response['source'])) {
                return $response['source'];
            }
            Log::error('failedresponse: ' . json_encode($response));

            return '';
        } catch (\Exception $e) {
            Log::error('Get video source URL error: ' . $e->getMessage());
            return '';
        }
    }
}
