<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbAccountResource;
use App\Jobs\FacebookSyncResources;
use App\Models\FbAccount;
use App\Models\Proxy;
use App\Models\Tag;
use App\Utils\Telegram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FbAccountController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'account_ids' => 'array',
            'account_names' => 'array',
            'ad_account_ids' => 'array',
            'ad_account_names' => 'array',
            'page_ids' => 'array',
            'page_names' => 'array',
            'bm_ids' => 'array',
            'bm_names' => 'array',
            'notes' => 'array'
        ]);

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->query('tags', []);

        $admin = auth()->user()->hasRole('admin');

        $searchableFields = [
            'name' => $request->get('name'),
            'username' => $request->get('username'),
        ];

//        $fb_account = FbAccount::searchByTagNames($tagNames)->search($searchableFields);

        if ($admin) {
            // 管理员的话，返回所有的 tag, 非管理员，只返回它自己有权限的 tag
            $fb_account = FbAccount::searchByTagNames($tagNames)->search($searchableFields)->with('tags');
        } else {
            $fb_account = FbAccount::searchByTagNames($tagNames)->search($searchableFields)
                ->where('user_id', auth()->id())->with(['tags' => function ($query) {
                    $query->wherePivot('user_id', auth()->id());
                }]);
        }

        if ($request->get('account_ids')) {
            $fb_account->whereIn('source_id', $request->get('account_ids'));
        }

        $account_names = $request->get('account_names');
        if ($account_names) {
            $fb_account = $fb_account->where(function ($query) use ($account_names) {
                foreach ($account_names as $account_name) {
                    $query->orWhere('name', 'LIKE', '%' . $account_name . '%');
                }
            });
        }

        $fbAdAccountIds = $request->get('ad_account_ids');
        if ($fbAdAccountIds) {
            $fb_account = $fb_account->whereHas('fbAdAccounts', function ($query) use ($fbAdAccountIds) {
                $query->whereIn('fb_ad_accounts.source_id', $fbAdAccountIds);
            });
        }

        $fbAdAccountNames = $request->get('ad_account_names');
        if ($fbAdAccountNames) {
            $fb_account = $fb_account->whereHas('fbAdAccounts', function ($query) use ($fbAdAccountNames) {
                $query->where(function ($innerQuery) use ($fbAdAccountNames) {
                    foreach ($fbAdAccountNames as $name) {
                        $innerQuery->orWhere('fb_ad_accounts.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        $fbPageIds = $request->get('page_ids');
        if ($fbPageIds) {
            $fb_account = $fb_account->whereHas('fbPages', function ($query) use ($fbPageIds) {
                $query->whereIn('fb_pages.source_id', $fbPageIds);
            });
        }

        $fbPageNames = $request->get('page_names');
        if ($fbPageNames) {
            $fb_account = $fb_account->whereHas('fbPages', function ($query) use ($fbPageNames) {
                $query->where(function ($innerQuery) use ($fbPageNames) {
                    foreach ($fbPageNames as $name) {
                        $innerQuery->orWhere('fb_pages.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        $bmIds = $request->get('bm_ids');
        if ($bmIds) {
            $fb_account = $fb_account->whereHas('fbBms', function ($query) use ($bmIds) {
                $query->whereIn('fb_bms.source_id', $bmIds);
            });
        }

        $fbBmNames = $request->get('bm_names');
        if ($fbBmNames) {
            $fb_account = $fb_account->whereHas('fbBms', function ($query) use ($fbBmNames) {
                $query->where(function ($innerQuery) use ($fbBmNames) {
                    foreach ($fbBmNames as $name) {
                        $innerQuery->orWhere('fb_bms.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        if ($request->has('notes')) {
            $notes = $request->input('notes');
            foreach ($notes as $note) {
                $fb_account->orWhere('notes', 'LIKE', '%' . $note . '%');
            }
        }

        $fb_account = $fb_account->with(['proxy', 'fbPages', 'fbAdAccounts', 'fbBusinessUsers', 'fbBms','fbBms.fbBusinessUsers'])
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbAccountResource::collection($fb_account->items()),
            'pageSize' => $fb_account->perPage(),
            'pageNo' => $fb_account->currentPage(),
            'totalPage' => $fb_account->lastPage(),
            'totalCount' => $fb_account->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store networks');

        $userID = auth()->user()->id;

        $request->validate([
            'source_id' => 'string|nullable',
            'name' => 'string|nullable',
            'first_name' => 'string|nullable',
            'last_name' => 'string|nullable',
            'username' => 'string|nullable',
            'password' => 'string|nullable',
            'gender' => 'string|nullable',
            'picture' => 'string|nullable',
            'twofa_key' => 'string|nullable',
            'cookies' => 'string|nullable',
            'token' => 'string|nullable',
            'token_valid' => 'boolean|nullable',
            'useragent' => 'string|nullable',
            'notes' => 'string|nullable',
            'proxy_id' => 'nullable|exists:proxies,id', // 验证 proxy_id 存在于 proxies 表中
            'new_tags' => [
                'sometimes', // 只有当 new_tags 存在时才应用后续的规则
                'array',     // new_tags 必须是一个数组
            ],
            'new_tags.*' => [
                'string',    // new_tags 数组中的每个元素必须是字符串
                'distinct',  // new_tags 数组中的每个元素必须是唯一的
                Rule::notIn(Tag::pluck('name')->toArray()), // new_tags 数组中的每个元素不能在 Tag 模型的 name 字段值中
            ],
            'tags' => 'array|distinct|nullable'
        ]);

        // 获取当前登录的用户
        $user = Auth::user();

        $fb_account = FbAccount::create([
            'user_id' => $userID,
            'name' => $request->input('name'),
            'source_id' => $request->input('source_id'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'gender' => $request->input('gender'),
            'picture' => $request->input('picture'),
            'twofa_key' => $request->input('twofa_key'),
            'cookies' => $request->input('cookies'),
            'token' => $request->input('token'),
            'token_valid' => $request->input('token_valid'),
            'useragent' => $request->input('useragent'),
            'notes' => $request->input('notes'),
        ]);

        if ($request->filled('proxy_id')) {
            $proxy = Proxy::find($request->input('proxy_id'));
            $fb_account->proxy()->associate($proxy);
            $fb_account->save();
        }

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('user_id', $userID)->whereHas('fbAccounts')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('fbAccounts')->where('name', '=' , $tagName)->first();
            $fb_account->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $fb_account->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        // 预加载 tags
        $fb_account->load(['tags', 'proxy', 'fbPages']);

        return new FbAccountResource($fb_account);
    }

    /**
     * Display the specified resource.
     */
    public function show(FbAccount $fbAccount)
    {
        return new FbAccountResource($fbAccount->load(['proxy', 'fbPages']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAccount $fbAccount)
    {
        $userID = auth()->user()->id;
        // 验证请求数据
        $validatedData = $request->validate([
            'twofa_key' => ['nullable'],
            'cookies' => ['nullable'],
            'token' => ['nullable'],
            'useragent' => ['nullable'],
            'proxy_id' => 'nullable|exists:proxies,id',
            'notes' => ['nullable'],
            'tags' => 'array|distinct|nullable'
        ]);

        // 更新模型实例
        $fbAccount->update($validatedData);

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $allTags = $fbAccount->tags()->where('tags.user_id', $userID)->pluck('name');
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('user_id', $userID)->whereHas('fbAccounts')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);
        $toDeletedTags = $allTags->diff($tagNames);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('fbAccounts')->where('name', '=' , $tagName)->first();
            if (!$fbAccount->tags->contains($tag->id)) {
                $fbAccount->tags()->attach($tag->id, ['user_id' => $userID]);
            }
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::query()->firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $fbAccount->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($toDeletedTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('fbAccounts')->where('name', '=' , $tagName)->first();
            $fbAccount->tags()->detach($tag->id);
        }

        if ($request->filled('proxy_id')) {
            $proxy = Proxy::find($request->input('proxy_id'));
            $fbAccount->proxy()->associate($proxy);
            $fbAccount->save();
        }

        // 返回资源
        return new FbAccountResource($fbAccount->load('proxy'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbAccount $fbAccount)
    {
        $fbAccount->delete();
        return response()->json(null, 204);
    }

    public function syncResource(FbAccount $fbAccount): JsonResponse
    {

        // 先创建任务，再调用接口，再看资源是否返回成功，添加model, 添加字段
        FacebookSyncResources::dispatch($fbAccount->id)->onQueue('frontend');
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function batchSyncResource(Request $request): JsonResponse
    {
        $request->validate([
            'ids'=> 'required|array',
            'ids.*' => 'string'
        ]);

        $cleanFbAccountIDs = FbAccount::query()->whereIn('id', $request->get('ids'))->pluck('id');

        // 先创建任务，再调用接口，再看资源是否返回成功，添加model, 添加字段
        foreach ($cleanFbAccountIDs as $id) {
            FacebookSyncResources::dispatch($id)->onQueue('frontend');
        }
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function setTokenValid(Request $request,FbAccount $fbAccount)
    {
        // 验证请求中的 token_valid 值
        $validatedData = $request->validate([
            'token_valid' => 'required|boolean',
        ]);

        // 更新 token_valid 字段的值
        $fbAccount->token_valid = $validatedData['token_valid'];
        $fbAccount->save();

        // 返回成功响应
        return response()->json([
            'message' => 'Token valid status updated successfully',
            'fbAccount' => new FbAccountResource($fbAccount),
            'success' => true
        ]);
    }

    public function archive(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        FbAccount::whereIn('id', $ids)->update(['is_archived' => true]); // 更新数据库
        return response()->json(['message' => 'Accounts archived successfully.']);
    }

    public function unarchive(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        FbAccount::whereIn('id', $ids)->update(['is_archived' => true]); // 更新数据库
        return response()->json(['message' => 'Accounts unarchived successfully.']);
    }

    public function assign(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fb_account_ids' => 'required|array',
            'fb_account_ids.*' => 'exists:fb_accounts,id',
        ]);

        $userId = $validatedData['user_id'];
        $fbAccountIds = $validatedData['fb_account_ids'];

        FbAccount::whereIn('id', $fbAccountIds)->update(['user_id' => $userId]);

        return response()->json(['message' => 'Assignation completed successfully.']);
    }

    public function decrypt_form_data(mixed $b, mixed $c): false|string
    {
        // 从环境变量中读取 rsa 的私钥，base64 过的
        $privateKeyBase64 = env('GEMINI_HELPER_PRIV_KEY', '');
        $privateKeyString = base64_decode($privateKeyBase64);
        $privateKey = openssl_pkey_get_private($privateKeyString);
        if ($privateKey === false) {
            $msg = '读取私钥失败';
            $this->logAndNotify($msg);
            return false;
        }

        $encrypted_aes_key = $b;
        $encrypted_base64_data = $c;

        // 解密数据
        openssl_private_decrypt(base64_decode($encrypted_aes_key), $aes_hex_key, $privateKey);

        // 转化 aes key
        $aes_key = hex2bin($aes_hex_key);

        // 将 AES 加密的 base64 数据 decode
        $encrypted_data_with_iv = base64_decode($encrypted_base64_data);
        // 分离IV和密文
        $iv_from_encrypted = substr($encrypted_data_with_iv, 0, 16);
        $encrypted_data = substr($encrypted_data_with_iv, 16);

        // 解密原始数据包
        $decrypted_data = openssl_decrypt($encrypted_data, 'AES-256-CBC', $aes_key, OPENSSL_RAW_DATA, $iv_from_encrypted);
        return $decrypted_data;
    }

    private function logAndNotify($msg, $data = null)
    {
        Log::warning($msg);
        if ($data) {
            Log::warning($data);
        }
        Telegram::sendMessage($msg);
    }
    public function update_token(Request $request)
    {
        // 检查 key
        $gemini_helper_api_key = env('GEMINI_HELPER_API_KEY', '');
        $request_key = $request->input('apiKey', '');
        if ($request_key !== $gemini_helper_api_key) {
            return response()->json([
                'success' => false,
                'message' => 'unauthorized access'
            ], 403);
        }

        // 解密 数据
        $b = $request->input('b'); // encrypted aes key;
        $c = $request->input('c'); // encrypted data;
        $decrypted_data = $this->decrypt_form_data($b, $c);

        // 检查是否解密成功
        if ($decrypted_data === false) {
            $msg ="Decrypt data failed";
            $this->logAndNotify($msg);
            return response()->json([
                'success' => false,
                'message' => 'decrypt data failed'
            ], 400);
        } else {
            // 解密成功后，转成 json 对象
            $decryptedObject = json_decode($decrypted_data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $msg = 'Decrypt data ok, but formate is not json';
                $this->logAndNotify($msg, $decrypted_data);
                return response()->json([
                    'success' => false,
                    'message' => 'not json data'
                ], 400);
            } else {

                // 解密数据成功，也是 json 对象
                $validator = Validator::make($decryptedObject, [
                    'access_token' => 'required|string',
                    'cookies' => 'required|array',
                    'info' => 'required|array',
                    'info.id' => 'required'
                ]);

                // 检查数据格式
                if ($validator->fails()) {
                    $msg = "Data validation error";
                    $this->logAndNotify($msg, $validator->errors()->toJson());
                    return response()->json([
                        'success' => false,
                        'message' => 'data validation error'
                    ]);
                } else {
                    // 数据包正常，查询 id 是否存在
                    $fb_account_source_id = $decryptedObject['info']['id'];
                    $fb_account = FbAccount::query()->firstWhere('source_id', $fb_account_source_id);
                    if (!$fb_account) {
                        $this->logAndNotify("{$fb_account_source_id} not imported to gemini");
                        return response()->json([
                            'success' => false,
                            'message' => 'Not imported to gemini'
                        ]);
                    }

                    // 保存 cookies 并 enable token
                    $fb_account->cookies = json_encode($decryptedObject['cookies']);
                    $fb_account->token = $decryptedObject['access_token'];
                    $fb_account->token_valid = true;
                    $fb_account->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'updated token',
                        'notes' => $fb_account->notes,
                    ]);
                }
            }
        }

    }

}
