<?php

namespace App\Services;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Utils\FbUtils;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 从 Graph GET act_{id}/assigned_users 拉取个号并写入 fb_accounts + 与广告账户的关联。
 * 与 MetaAdCreationBmGraphController 内逻辑一致，供详情接口在库中无数据时按需调用。
 *
 * Graph v23+ 等版本要求查询参数 **business**（BM id），否则会报 (#100) The parameter business is required。
 * 若 business 与广告账户真实所属 BM 不一致，接口常返回 200 且 data 为空，故需多候选 BM 重试，并在仍为空时用
 * BM business_users + assigned_ad_accounts + user 兜底。
 * 若人员为「全 BM / 全部资产」权限，Graph 常不返回每条 assigned_ad_accounts，此时再退化为拉取 BM 下全部 business_users 的 user{}（与 BM「人员」页一致）。
 */
class FbAdAccountAssignedUsersFromGraphService
{
    /** 与 MetaAdCreationBmGraphController 写死 BM 一致，仅当库中无法解析 BM 时使用 */
    private const FALLBACK_BM_SOURCE_ID = '1476379370819673';

    /**
     * 解析广告账户所属 Business Manager 的 Facebook id（数字串）。
     */
    public static function resolveBusinessIdForAdAccount(FbAdAccount $adAccount): string
    {
        $candidates = self::resolveBusinessIdCandidatesForAdAccount($adAccount);

        return $candidates[0] ?? self::FALLBACK_BM_SOURCE_ID;
    }

    /**
     * 按优先级返回用于 assigned_users 的 business 候选（去重）。
     * 顺序：库内 business.id → 关联的各 BM → owner（数字）→ 兜底 BM。
     *
     * @return list<string>
     */
    public static function resolveBusinessIdCandidatesForAdAccount(FbAdAccount $adAccount): array
    {
        $adAccount->loadMissing('fbBms');

        $candidates = [];
        $biz = $adAccount->business;
        if (is_array($biz) && ! empty($biz['id'])) {
            $candidates[] = (string) $biz['id'];
        }
        foreach ($adAccount->fbBms as $bm) {
            if ($bm->source_id !== null && $bm->source_id !== '') {
                $candidates[] = (string) $bm->source_id;
            }
        }
        $owner = $adAccount->owner ?? null;
        if ($owner !== null && $owner !== '' && preg_match('/^\d+$/', (string) $owner)) {
            $candidates[] = (string) $owner;
        }
        $candidates[] = self::FALLBACK_BM_SOURCE_ID;

        $out = [];
        foreach ($candidates as $c) {
            $c = (string) $c;
            if ($c !== '' && ! in_array($c, $out, true)) {
                $out[] = $c;
            }
        }

        return $out;
    }

    public function sync(string $version, FbAdAccount $adAccount, string $token): void
    {
        $actId = $adAccount->source_id;
        if ($actId === null || $actId === '') {
            Log::warning('FbAdAccountAssignedUsersFromGraphService: missing source_id', [
                'fb_ad_account_id' => $adAccount->id,
            ]);

            return;
        }

        $this->refreshBusinessFromGraphIfMissing($version, $adAccount, $token);

        $candidates = self::resolveBusinessIdCandidatesForAdAccount($adAccount);

        Log::info('FbAdAccountAssignedUsersFromGraphService: request candidates', [
            'act_id' => $actId,
            'business_candidates' => $candidates,
            'fb_ad_account_id' => $adAccount->id,
            'graph_access_token_present' => $token !== '',
            'graph_access_token_length' => strlen($token),
        ]);

        $rows = [];
        $usedBusiness = null;
        foreach ($candidates as $businessId) {
            $endpoint = "https://graph.facebook.com/{$version}/act_{$actId}/assigned_users";
            $query = [
                'business' => $businessId,
                'fields' => 'id,name',
                'limit' => 500,
            ];
            try {
                $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $token);
            } catch (\Throwable $e) {
                Log::warning('FbAdAccountAssignedUsersFromGraphService: assigned_users attempt failed', [
                    'act_id' => $actId,
                    'business' => $businessId,
                    'fb_ad_account_id' => $adAccount->id,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }
            $rows = $this->normalizeGraphDataList($resp);
            if ($rows !== []) {
                $usedBusiness = $businessId;
                break;
            }
        }

        $source = 'assigned_users';
        if ($rows === []) {
            Log::info('FbAdAccountAssignedUsersFromGraphService: assigned_users empty for all candidates, trying business_users fallback', [
                'act_id' => $actId,
                'fb_ad_account_id' => $adAccount->id,
                'tried_business' => $candidates,
            ]);
            $fallback = $this->fetchPersonalUsersFromBmBusinessUsers($version, $actId, $token, $candidates);
            $rows = $fallback['users'];
            $source = $fallback['source'];
        }

        $count = $this->persistFbAccountsForAdAccount($adAccount, $actId, $rows);

        Log::info('FbAdAccountAssignedUsersFromGraphService: synced', [
            'act_id' => $actId,
            'fb_ad_account_id' => $adAccount->id,
            'assigned_users_count' => $count,
            'source' => $source,
            'used_business' => $usedBusiness,
            'platform_user_id' => Auth::id(),
        ]);
    }

    /**
     * 库中无 business.id 时拉取 act_{id}?fields=business,owner 并写回，便于后续用正确 BM 查 assigned_users。
     */
    private function refreshBusinessFromGraphIfMissing(string $version, FbAdAccount $adAccount, string $token): void
    {
        $biz = $adAccount->business;
        if (is_array($biz) && ! empty($biz['id'])) {
            return;
        }
        $actId = $adAccount->source_id;
        if ($actId === null || $actId === '') {
            return;
        }
        $endpoint = "https://graph.facebook.com/{$version}/act_{$actId}";
        $query = ['fields' => 'business{id,name},owner'];
        try {
            $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $token);
        } catch (\Throwable $e) {
            Log::warning('FbAdAccountAssignedUsersFromGraphService: refresh act business failed', [
                'act_id' => $actId,
                'fb_ad_account_id' => $adAccount->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }
        $business = data_get($resp, 'business');
        if (is_array($business) && ! empty($business['id'])) {
            $adAccount->business = $business;
        }
        if (data_get($resp, 'owner') !== null) {
            $adAccount->owner = data_get($resp, 'owner');
        }
        if ($adAccount->isDirty()) {
            $adAccount->save();
            Log::info('FbAdAccountAssignedUsersFromGraphService: refreshed business from Graph', [
                'act_id' => $actId,
                'fb_ad_account_id' => $adAccount->id,
                'business_id' => data_get($adAccount->business, 'id'),
            ]);
        }
    }

    /**
     * @param  mixed  $resp  FbUtils::makeRequest 返回值（含 Collection）
     * @return list<array<string, mixed>>
     */
    private function normalizeGraphDataList(mixed $resp): array
    {
        $raw = data_get($resp, 'data');
        if ($raw instanceof Collection) {
            $raw = $raw->all();
        }
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, fn ($row) => is_array($row)));
    }

    /**
     * 先从 business_users 中按 assigned_ad_accounts 匹配本广告账户；若为空再拉 BM 下全部人员（与 BM「人员」页一致）。
     *
     * @param  list<string>  $bmSourceIds
     * @return array{users: list<array{id: string, name: string}>, source: string}
     */
    private function fetchPersonalUsersFromBmBusinessUsers(string $version, string $actId, string $token, array $bmSourceIds): array
    {
        $strict = $this->walkBmBusinessUsers(
            $version,
            $token,
            $bmSourceIds,
            'assigned_ad_accounts.limit(100){account_id,id},user{id,name},name,email,first_name,last_name',
            function (array $bu, string $actIdInner): ?array {
                $aas = $this->normalizeAssignedAdAccountsList($bu);
                if ($aas === []) {
                    return null;
                }
                foreach ($aas as $aa) {
                    if (is_array($aa) && $this->assignedAdAccountRowMatchesAct($actIdInner, $aa)) {
                        return $this->businessUserRowToPersonalUser($bu);
                    }
                }

                return null;
            },
            $actId
        );

        if ($strict !== []) {
            return ['users' => $strict, 'source' => 'business_users_assigned_ad_accounts'];
        }

        Log::info('FbAdAccountAssignedUsersFromGraphService: strict business_users empty, using all BM people with user{}', [
            'act_id' => $actId,
        ]);

        $all = $this->walkBmBusinessUsers(
            $version,
            $token,
            $bmSourceIds,
            'user{id,name},name,email,first_name,last_name',
            function (array $bu, string $actIdInner): ?array {
                unset($actIdInner);

                return $this->businessUserRowToPersonalUser($bu);
            },
            $actId
        );

        return ['users' => $all, 'source' => 'business_users_all_people'];
    }

    /**
     * @param  list<string>  $bmSourceIds
     * @param  callable(array,string): ?array{id: string, name: string}  $mapRow  返回 null 表示跳过该行
     * @return list<array{id: string, name: string}>
     */
    private function walkBmBusinessUsers(
        string $version,
        string $token,
        array $bmSourceIds,
        string $fields,
        callable $mapRow,
        string $actId
    ): array {
        $out = [];
        $seen = [];

        foreach ($bmSourceIds as $bmId) {
            $endpoint = "https://graph.facebook.com/{$version}/{$bmId}/business_users";
            $query = [
                'fields' => $fields,
                'limit' => 500,
            ];
            $next = null;
            $pageIndex = 0;
            do {
                try {
                    $resp = $next
                        ? FbUtils::makeRequest(null, $next, null, 'GET', '', '', $token)
                        : FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $token);
                } catch (\Throwable $e) {
                    Log::warning('FbAdAccountAssignedUsersFromGraphService: business_users page failed', [
                        'bm_id' => $bmId,
                        'message' => $e->getMessage(),
                    ]);
                    break;
                }
                $rows = $this->normalizeGraphDataList($resp);
                Log::info('FbAdAccountAssignedUsersFromGraphService: business_users page', [
                    'bm_id' => $bmId,
                    'page' => $pageIndex,
                    'row_count' => count($rows),
                ]);
                foreach ($rows as $bu) {
                    if (! is_array($bu)) {
                        continue;
                    }
                    $mapped = $mapRow($bu, $actId);
                    if ($mapped === null) {
                        continue;
                    }
                    $uid = (string) ($mapped['source_id'] ?? $mapped['id'] ?? '');
                    if ($uid === '') {
                        continue;
                    }
                    if (isset($seen[$uid])) {
                        continue;
                    }
                    $seen[$uid] = true;
                    $out[] = $mapped;
                }
                $next = data_get($resp, 'paging.next');
                $pageIndex++;
            } while ($next);
        }

        return $out;
    }

    /**
     * @return array{id: string, name: string}|null
     */
    private function businessUserRowToPersonalUser(array $bu): ?array
    {
        $user = $bu['user'] ?? null;
        if (! is_array($user) || empty($user['id'])) {
            return null;
        }
        $uid = (string) $user['id'];
        $name = $user['name'] ?? null;
        if ($name === null || $name === '') {
            $name = trim(
                (($bu['first_name'] ?? '') . ' ' . ($bu['last_name'] ?? ''))
                ?: (string) ($bu['name'] ?? $bu['email'] ?? $uid)
            );
        }

        return [
            'id' => $uid,
            'name' => $name !== '' ? $name : $uid,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeAssignedAdAccountsList(array $bu): array
    {
        $raw = data_get($bu, 'assigned_ad_accounts');
        if ($raw instanceof Collection) {
            $raw = $raw->all();
        }
        if (! is_array($raw)) {
            return [];
        }
        if (isset($raw['data']) && is_array($raw['data'])) {
            $aas = $raw['data'];
        } else {
            $aas = $raw;
        }
        if ($aas instanceof Collection) {
            $aas = $aas->all();
        }
        if (! is_array($aas)) {
            return [];
        }

        return array_values(array_filter($aas, fn ($row) => is_array($row)));
    }

    private function assignedAdAccountRowMatchesAct(string $actId, array $aaRow): bool
    {
        $aid = (string) ($aaRow['account_id'] ?? '');
        if ($aid === '' && isset($aaRow['id'])) {
            $raw = (string) $aaRow['id'];
            $aid = str_starts_with($raw, 'act_') ? substr($raw, 4) : $raw;
        }
        $normAct = preg_replace('/^act_/', '', $actId);
        $normAid = preg_replace('/^act_/', '', $aid);

        return $normAid !== '' && $normAid === $normAct;
    }

    /**
     * 仅从 BM 拉取 Graph `GET /{bm-id}/business_users`，写入 fb_accounts，不依赖广告账户。
     * 对应 Meta 后台「商务设置 → 用户 → 人员」。
     *
     * 优先用嵌套 `user.id`（Facebook 个人用户 ID）；若 Token 未返回 `user`（常见），则用 BM 人员行 `id`
     * 写入 source_id 前缀 `bmu_`，保证与后台名单一致、仍能落库展示。
     *
     * @return Collection<int, FbAccount>
     */
    public function syncPersonalFbAccountsFromBm(string $version, string $bmSourceId, string $token): Collection
    {
        $fields = 'id,name,email,first_name,last_name,role,user{id,name}';
        $rows = $this->walkBmBusinessUsers(
            $version,
            $token,
            [$bmSourceId],
            $fields,
            function (array $bu, string $actIdUnused): ?array {
                unset($actIdUnused);

                return $this->mapBusinessUserRowToFbAccountPayload($bu);
            },
            ''
        );

        $out = collect();
        $platformUserId = Auth::id();
        foreach ($rows as $u) {
            $sid = isset($u['source_id']) ? (string) $u['source_id'] : null;
            if (! $sid) {
                continue;
            }
            $fbAcc = FbAccount::query()->firstOrNew(['source_id' => $sid]);
            $fbAcc->name = $u['name'] ?? $sid;
            if (! empty($u['first_name'])) {
                $fbAcc->first_name = $u['first_name'];
            }
            if (! empty($u['last_name'])) {
                $fbAcc->last_name = $u['last_name'];
            }
            if ($platformUserId && ! $fbAcc->user_id) {
                $fbAcc->user_id = $platformUserId;
            }
            $fbAcc->save();
            $out->push($fbAcc);
        }

        Log::info('FbAdAccountAssignedUsersFromGraphService: syncPersonalFbAccountsFromBm', [
            'bm_source_id' => $bmSourceId,
            'fb_accounts_count' => $out->count(),
            'graph_rows_mapped' => count($rows),
        ]);

        return $out;
    }

    /**
     * 将 business_users 单行转为写入 fb_accounts 的字段。
     * - 有 `user.id`：source_id = Facebook 用户 ID（与 Graph user 一致）
     * - 无 `user`：source_id = `bmu_{business_user_id}`，name 取自 name/email
     *
     * @return array{source_id: string, name: string, first_name?: string, last_name?: string}|null
     */
    private function mapBusinessUserRowToFbAccountPayload(array $bu): ?array
    {
        $user = $bu['user'] ?? null;
        if (is_array($user) && ! empty($user['id'])) {
            $sourceId = (string) $user['id'];
            $name = $user['name'] ?? null;
        } else {
            $buGraphId = isset($bu['id']) ? (string) $bu['id'] : '';
            if ($buGraphId === '') {
                return null;
            }
            $sourceId = 'bmu_' . $buGraphId;
            $name = $bu['name'] ?? null;
        }

        if ($name === null || trim((string) $name) === '') {
            $name = trim(
                (($bu['first_name'] ?? '') . ' ' . ($bu['last_name'] ?? ''))
                ?: (string) ($bu['email'] ?? $sourceId)
            );
        }
        if ($name === '') {
            $name = $sourceId;
        }

        $first = isset($bu['first_name']) ? (string) $bu['first_name'] : '';
        $last = isset($bu['last_name']) ? (string) $bu['last_name'] : '';

        return [
            'source_id' => $sourceId,
            'name' => $name,
            'first_name' => $first !== '' ? $first : null,
            'last_name' => $last !== '' ? $last : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows  每项含 id、name（与 assigned_users 一致）
     */
    private function persistFbAccountsForAdAccount(FbAdAccount $adAccount, string $actId, array $rows): int
    {
        $platformUserId = Auth::id();
        $n = 0;
        foreach ($rows as $u) {
            $sid = isset($u['id']) ? (string) $u['id'] : null;
            if (! $sid) {
                continue;
            }
            $fbAcc = FbAccount::query()->firstOrNew(['source_id' => $sid]);
            $fbAcc->name = $u['name'] ?? $sid;
            if ($platformUserId && ! $fbAcc->user_id) {
                $fbAcc->user_id = $platformUserId;
            }
            $fbAcc->save();

            $adAccount->fbAccounts()->syncWithoutDetaching([
                $fbAcc->id => [
                    'source_id' => $actId,
                    'relation' => 'ASSIGNED',
                ],
            ]);
            $n++;
        }

        return $n;
    }
}
