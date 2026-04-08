<?php

namespace App\Http\Controllers\AccountManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\FbAccountResource;
use App\Jobs\FacebookSyncResources;
use App\Models\FbAccount;
use App\Models\Proxy;
use App\Models\Tag;
use App\Traits\ApiResponse;
use App\Utils\Telegram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FbAccountController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
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
            'notes' => 'array',
            'authorization_status' => 'nullable|in:authorized,failed,pending',
            'authorized_by' => 'nullable|string',
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

        $query = $admin
            ? FbAccount::searchByTagNames($tagNames)->search($searchableFields)->with('tags')
            : FbAccount::searchByTagNames($tagNames)->search($searchableFields)
                ->where('user_id', auth()->id())
                ->with(['tags' => fn($q) => $q->wherePivot('user_id', auth()->id())]);

        $this->applyFilters($query, $request);

        // 授权状态过滤
        if ($authorizationStatus = $request->get('authorization_status')) {
            $query->where('authorization_status', $authorizationStatus);
        }

        // 授权人过滤
        if ($authorizedBy = $request->get('authorized_by')) {
            $query->where('authorized_by', $authorizedBy);
        }

        $fbAccount = $query
            ->with(['proxy', 'fbPages', 'fbAdAccounts', 'fbBusinessUsers', 'fbBms', 'fbBms.fbBusinessUsers', 'authorizedBy', 'user'])
            ->withCount('fbAdAccounts as binding_count')
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return $this->success([
            'data' => FbAccountResource::collection($fbAccount->items()),
            'pageSize' => $fbAccount->perPage(),
            'pageNo' => $fbAccount->currentPage(),
            'totalPage' => $fbAccount->lastPage(),
            'totalCount' => $fbAccount->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Log::debug('store networks');

        $validated = $request->validate([
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
            'authorization_status' => 'nullable|in:authorized,failed,pending',
            'authorized_by' => 'nullable|string|exists:users,id',
            'authorized_at' => 'nullable|date',
            'authorization_fail_reason' => 'nullable|string|max:500',
            'useragent' => 'string|nullable',
            'fingerbrowser_id' => 'nullable|string|exists:fingerbrowsers,id',
            'notes' => 'string|nullable',
            'proxy_id' => 'nullable|exists:proxies,id',
            'new_tags' => 'sometimes|array',
            'new_tags.*' => ['string', 'distinct', Rule::notIn(Tag::pluck('name')->toArray())],
            'tags' => 'array|distinct|nullable'
        ]);

        $userID = auth()->id();
        $fbAccount = FbAccount::create([
            'user_id' => $userID,
            'name' => $validated['name'] ?? null,
            'source_id' => $validated['source_id'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'username' => $validated['username'] ?? null,
            'password' => $validated['password'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'picture' => $validated['picture'] ?? null,
            'twofa_key' => $validated['twofa_key'] ?? null,
            'cookies' => $validated['cookies'] ?? null,
            'token' => $validated['token'] ?? null,
            'token_valid' => $validated['token_valid'] ?? null,
            'authorization_status' => $validated['authorization_status'] ?? null,
            'authorized_by' => $validated['authorized_by'] ?? null,
            'authorized_at' => $validated['authorized_at'] ?? null,
            'authorization_fail_reason' => $validated['authorization_fail_reason'] ?? null,
            'useragent' => $validated['useragent'] ?? null,
            'fingerbrowser_id' => $validated['fingerbrowser_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (!empty($validated['proxy_id'])) {
            $fbAccount->proxy()->associate(Proxy::find($validated['proxy_id']));
            $fbAccount->save();
        }

        $this->syncTags($fbAccount, $validated['tags'] ?? [], $userID);

        return $this->success(new FbAccountResource($fbAccount->load(['tags', 'proxy', 'fbPages', 'authorizedBy'])), '创建成功');
    }

    /**
     * Display the specified resource.
     */
    public function show(FbAccount $fbAccount): JsonResponse
    {
        return $this->success(new FbAccountResource($fbAccount->load(['proxy', 'fbPages', 'authorizedBy', 'user'])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAccount $fbAccount): JsonResponse
    {
        $userID = auth()->id();

        $validated = $request->validate([
            'twofa_key' => ['nullable'],
            'cookies' => ['nullable'],
            'token' => ['nullable'],
            'token_valid' => ['boolean'],
            'authorization_status' => 'nullable|in:authorized,failed,pending',
            'authorized_by' => 'nullable|string|exists:users,id',
            'authorized_at' => 'nullable|date',
            'authorization_fail_reason' => 'nullable|string|max:500',
            'useragent' => ['nullable'],
            'fingerbrowser_id' => 'nullable|string|exists:fingerbrowsers,id',
            'proxy_id' => 'nullable|exists:proxies,id',
            'notes' => ['nullable'],
            'tags' => 'array|distinct|nullable'
        ]);

        $fbAccount->update($validated);
        $this->syncTags($fbAccount, $validated['tags'] ?? [], $userID, true);

        if (!empty($validated['proxy_id'])) {
            $fbAccount->proxy()->associate(Proxy::find($validated['proxy_id']));
            $fbAccount->save();
        }

        return $this->success(new FbAccountResource($fbAccount->load(['proxy', 'authorizedBy', 'user'])), '更新成功');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbAccount $fbAccount): JsonResponse
    {
        $fbAccount->delete();
        return $this->success(null, '删除成功', 204);
    }

    /**
     * Sync Facebook resources for a single account.
     */
    public function syncResource(FbAccount $fbAccount): JsonResponse
    {
        FacebookSyncResources::dispatch($fbAccount->id)->onQueue('frontend');
        return $this->success(null, trans('message.task_submitted', [], $this->language));
    }

    /**
     * Batch sync Facebook resources.
     */
    public function batchSyncResource(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);

        $cleanFbAccountIDs = FbAccount::query()->whereIn('id', $validated['ids'])->pluck('id');

        foreach ($cleanFbAccountIDs as $id) {
            FacebookSyncResources::dispatch($id)->onQueue('frontend');
        }

        return $this->success(null, trans('message.task_submitted', [], $this->language));
    }

    /**
     * Set token valid status.
     */
    public function setTokenValid(Request $request, FbAccount $fbAccount): JsonResponse
    {
        $validated = $request->validate(['token_valid' => 'required|boolean']);

        $fbAccount->token_valid = $validated['token_valid'];
        $fbAccount->save();

        return $this->success([
            'fbAccount' => new FbAccountResource($fbAccount),
        ], 'Token valid status updated successfully');
    }

    /**
     * Archive accounts.
     */
    public function archive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string'
        ]);

        FbAccount::whereIn('id', $validated['ids'])->update(['is_archived' => true]);

        return $this->success(null, 'Accounts archived successfully.');
    }

    /**
     * Unarchive accounts.
     */
    public function unarchive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string'
        ]);

        FbAccount::whereIn('id', $validated['ids'])->update(['is_archived' => false]);

        return $this->success(null, 'Accounts unarchived successfully.');
    }

    /**
     * Assign accounts to a user.
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fb_account_ids' => 'required|array',
            'fb_account_ids.*' => 'exists:fb_accounts,id',
        ]);

        FbAccount::whereIn('id', $validated['fb_account_ids'])->update(['user_id' => $validated['user_id']]);

        return $this->success(null, 'Assignation completed successfully.');
    }

    /**
     * Update token from external source.
     */
    public function updateToken(Request $request): JsonResponse
    {
        $geminiHelperApiKey = env('GEMINI_HELPER_API_KEY', '');
        $requestKey = $request->input('apiKey', '');

        if ($requestKey !== $geminiHelperApiKey) {
            return $this->fail('Unauthorized access', 403);
        }

        $decryptedData = $this->decryptFormData(
            $request->input('b'),
            $request->input('c')
        );

        if ($decryptedData === false) {
            $msg = 'Decrypt data failed';
            $this->logAndNotify($msg);
            return $this->fail($msg, 400);
        }

        $decryptedObject = json_decode($decryptedData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $msg = 'Decrypt data ok, but format is not json';
            $this->logAndNotify($msg, $decryptedData);
            return $this->fail('Not json data', 400);
        }

        $validator = Validator::make($decryptedObject, [
            'access_token' => 'required|string',
            'cookies' => 'required|array',
            'info' => 'required|array',
            'info.id' => 'required'
        ]);

        if ($validator->fails()) {
            $msg = 'Data validation error';
            $this->logAndNotify($msg, $validator->errors()->toJson());
            return $this->fail('Data validation error');
        }

        $fbAccountSourceId = $decryptedObject['info']['id'];
        $fbAccount = FbAccount::query()->firstWhere('source_id', $fbAccountSourceId);

        if (!$fbAccount) {
            $this->logAndNotify("{$fbAccountSourceId} not imported to gemini");
            return $this->fail('Not imported to gemini');
        }

        $fbAccount->update([
            'cookies' => json_encode($decryptedObject['cookies']),
            'token' => $decryptedObject['access_token'],
            'token_valid' => true,
        ]);

        return $this->success(['notes' => $fbAccount->notes], 'Updated token');
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->get('account_ids')) {
            $query->whereIn('source_id', $request->get('account_ids'));
        }

        if ($accountNames = $request->get('account_names')) {
            $query->where(function ($q) use ($accountNames) {
                foreach ($accountNames as $name) {
                    $q->orWhere('name', 'LIKE', '%' . $name . '%');
                }
            });
        }

        if ($fbAdAccountIds = $request->get('ad_account_ids')) {
            $query->whereHas('fbAdAccounts', fn($q) => $q->whereIn('fb_ad_accounts.source_id', $fbAdAccountIds));
        }

        if ($fbAdAccountNames = $request->get('ad_account_names')) {
            $query->whereHas('fbAdAccounts', function ($q) use ($fbAdAccountNames) {
                $q->where(function ($innerQ) use ($fbAdAccountNames) {
                    foreach ($fbAdAccountNames as $name) {
                        $innerQ->orWhere('fb_ad_accounts.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        if ($fbPageIds = $request->get('page_ids')) {
            $query->whereHas('fbPages', fn($q) => $q->whereIn('fb_pages.source_id', $fbPageIds));
        }

        if ($fbPageNames = $request->get('page_names')) {
            $query->whereHas('fbPages', function ($q) use ($fbPageNames) {
                $q->where(function ($innerQ) use ($fbPageNames) {
                    foreach ($fbPageNames as $name) {
                        $innerQ->orWhere('fb_pages.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        if ($bmIds = $request->get('bm_ids')) {
            $query->whereHas('fbBms', fn($q) => $q->whereIn('fb_bms.source_id', $bmIds));
        }

        if ($fbBmNames = $request->get('bm_names')) {
            $query->whereHas('fbBms', function ($q) use ($fbBmNames) {
                $q->where(function ($innerQ) use ($fbBmNames) {
                    foreach ($fbBmNames as $name) {
                        $innerQ->orWhere('fb_bms.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        if ($notes = $request->input('notes')) {
            foreach ($notes as $note) {
                $query->orWhere('notes', 'LIKE', '%' . $note . '%');
            }
        }
    }

    /**
     * Sync tags for the account.
     */
    private function syncTags(FbAccount $fbAccount, array $tagNames, int $userId, bool $isUpdate = false): void
    {
        $tagNames = collect($tagNames)->unique();

        if ($isUpdate) {
            $allTags = $fbAccount->tags()->where('tags.user_id', $userId)->pluck('name');
            $existingTags = Tag::query()->where('user_id', $userId)->whereHas('fbAccounts')->whereIn('name', $tagNames)->pluck('name');
            $newTags = $tagNames->diff($existingTags);
            $toDeletedTags = $allTags->diff($tagNames);

            foreach ($existingTags as $tagName) {
                $tag = Tag::query()->where('user_id', $userId)->whereHas('fbAccounts')->where('name', $tagName)->first();
                if ($tag && !$fbAccount->tags->contains($tag->id)) {
                    $fbAccount->tags()->attach($tag->id, ['user_id' => $userId]);
                }
            }

            foreach ($newTags as $tagName) {
                $tag = Tag::query()->firstOrCreate(['name' => $tagName, 'user_id' => $userId]);
                $fbAccount->tags()->attach($tag->id, ['user_id' => $userId]);
            }

            foreach ($toDeletedTags as $tagName) {
                $tag = Tag::query()->where('user_id', $userId)->whereHas('fbAccounts')->where('name', $tagName)->first();
                if ($tag) {
                    $fbAccount->tags()->detach($tag->id);
                }
            }
        } else {
            $existingTags = Tag::query()->where('user_id', $userId)->whereHas('fbAccounts')->whereIn('name', $tagNames)->pluck('name');
            $newTags = $tagNames->diff($existingTags);

            foreach ($existingTags as $tagName) {
                $tag = Tag::query()->where('user_id', $userId)->whereHas('fbAccounts')->where('name', $tagName)->first();
                $fbAccount->tags()->attach($tag->id, ['user_id' => $userId]);
            }

            foreach ($newTags as $tagName) {
                $tag = Tag::query()->firstOrCreate(['name' => $tagName, 'user_id' => $userId]);
                $fbAccount->tags()->attach($tag->id, ['user_id' => $userId]);
            }
        }
    }

    /**
     * Decrypt form data using RSA.
     */
    private function decryptFormData(mixed $b, mixed $c): false|string
    {
        $privateKeyBase64 = env('GEMINI_HELPER_PRIV_KEY', '');
        $privateKeyString = base64_decode($privateKeyBase64);
        $privateKey = openssl_pkey_get_private($privateKeyString);

        if ($privateKey === false) {
            $msg = 'Failed to read private key';
            $this->logAndNotify($msg);
            return false;
        }

        $encryptedAesKey = $b;
        $encryptedBase64Data = $c;

        openssl_private_decrypt(base64_decode($encryptedAesKey), $aesHexKey, $privateKey);
        $aesKey = hex2bin($aesHexKey);

        $encryptedDataWithIv = base64_decode($encryptedBase64Data);
        $ivFromEncrypted = substr($encryptedDataWithIv, 0, 16);
        $encryptedData = substr($encryptedDataWithIv, 16);

        return openssl_decrypt($encryptedData, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $ivFromEncrypted);
    }

    /**
     * Log error and send notification.
     */
    private function logAndNotify(string $msg, mixed $data = null): void
    {
        Log::warning($msg);
        if ($data) {
            Log::warning($data);
        }
        Telegram::sendMessage($msg);
    }

    /**
     * Update authorization status for FB account.
     */
    public function updateAuthorizationStatus(Request $request, FbAccount $fbAccount): JsonResponse
    {
        $validated = $request->validate([
            'authorization_status' => 'required|in:authorized,failed,pending',
            'authorization_fail_reason' => 'nullable|string|max:500',
        ]);

        $fbAccount->update([
            'authorization_status' => $validated['authorization_status'],
            'authorized_by' => auth()->id(),
            'authorized_at' => now(),
            'authorization_fail_reason' => $validated['authorization_fail_reason'] ?? null,
        ]);

        return $this->success(
            new FbAccountResource($fbAccount->load(['authorizedBy', 'user'])),
            '授权状态更新成功'
        );
    }
}
