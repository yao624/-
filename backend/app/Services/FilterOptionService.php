<?php

namespace App\Services;

use App\Models\FbAdAccount;
use App\Models\MetaOrganization;
use App\Models\MetaTagFolders;
use App\Models\MetaTagOptions;

class FilterOptionService
{
    /**
     * 获取所有筛选选项的翻译文本
     *
     * @return array
     */
    public function getTranslations(): array
    {
        return [
            'adAccount' => [
                'en' => 'Ad Account',
                'zh' => '广告账户',
            ],
            'channel' => [
                'en' => 'Channel',
                'zh' => '渠道',
            ],
            'campaign' => [
                'en' => 'Campaign',
                'zh' => '广告系列',
            ],
            'adGroup' => [
                'en' => 'Ad Group',
                'zh' => '广告组',
            ],
            'tagIds' => [
                'en' => 'Tag',
                'zh' => '标签',
            ],
            'designer' => [
                'en' => 'Designer',
                'zh' => '设计师',
            ],
            'creator' => [
                'en' => 'Creator',
                'zh' => '创意人',
            ],
            'accountStatus' => [
                'en' => 'Account Status',
                'zh' => '账户状态',
            ],
            'authorizationStatus' => [
                'en' => 'Authorization Status',
                'zh' => '授权状态',
            ],
            'timezone' => [
                'en' => 'Timezone',
                'zh' => '时区',
            ],
        ];
    }

    /**
     * 获取所有筛选选项数据
     *
     * @param string $language
     * @param array $keys
     * @return array
     */
    public function getAllFilterOptions(string $language, array $keys = []): array
    {
        $translations = $this->getTranslations();
        $hasTagIds = in_array('tagIds', $keys);
        $hasOrg = in_array('designer', $keys) || in_array('creator', $keys);

        $organizationTree = $hasOrg ? $this->getOrganizationTree() : [];

        return [
            'adAccount' => [
                'key' => 'adAccount',
                'label' => $translations['adAccount'][$language] ?? $translations['adAccount']['en'],
                'enabled' => true,
                'disabled' => true,
                'value' => null,
                'options' => $this->getAdAccountOptions(),
            ],
            'channel' => [
                'key' => 'channel',
                'label' => $translations['channel'][$language] ?? $translations['channel']['en'],
                'enabled' => true,
                'disabled' => true,
                'value' => null,
                'options' => [
                    ['label' => 'Meta', 'value' => 'meta'],
                    ['label' => 'Facebook', 'value' => 'facebook'],
                    ['label' => 'TikTok', 'value' => 'tiktok'],
                ],
            ],
            'campaign' => [
                'key' => 'campaign',
                'label' => $translations['campaign'][$language] ?? $translations['campaign']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'options' => [
                    ['label' => '系列A', 'value' => 'campaign_a'],
                    ['label' => '系列B', 'value' => 'campaign_b'],
                    ['label' => '系列C', 'value' => 'campaign_c'],
                ],
            ],
            'adGroup' => [
                'key' => 'adGroup',
                'label' => $translations['adGroup'][$language] ?? $translations['adGroup']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'options' => [
                    ['label' => '广告组A', 'value' => 'adGroup_a'],
                    ['label' => '广告组B', 'value' => 'adGroup_b'],
                    ['label' => '广告组C', 'value' => 'adGroup_c'],
                ],
            ],
            'tagIds' => [
                'key' => 'tagIds',
                'label' => $translations['tagIds'][$language] ?? $translations['tagIds']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'tagTreeData' => $hasTagIds ? [
                    'tagFolders' => $this->getTagFolders(),
                    'tags' => $this->getTags(),
                    'tagOptions' => $this->buildTagOptionsTree(),
                ] : null,
            ],
            'designer' => [
                'key' => 'designer',
                'label' => $translations['designer'][$language] ?? $translations['designer']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'orgData' => $organizationTree,
            ],
            'creator' => [
                'key' => 'creator',
                'label' => $translations['creator'][$language] ?? $translations['creator']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'orgData' => $organizationTree,
            ],
            'accountStatus' => [
                'key' => 'accountStatus',
                'label' => $translations['accountStatus'][$language] ?? $translations['accountStatus']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'options' => [
                    ['label' => '已启用', 'value' => 1],
                    ['label' => '已暂停', 'value' => 2],
                    ['label' => '已删除', 'value' => 3],
                ],
            ],
            'authorizationStatus' => [
                'key' => 'authorizationStatus',
                'label' => $translations['authorizationStatus'][$language] ?? $translations['authorizationStatus']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'options' => [
                    ['label' => '已授权', 'value' => 'authorized'],
                    ['label' => '授权失败', 'value' => 'failed'],
                    ['label' => '已解绑', 'value' => 'pending'],
                ],
            ],
            'timezone' => [
                'key' => 'timezone',
                'label' => $translations['timezone'][$language] ?? $translations['timezone']['en'],
                'enabled' => false,
                'disabled' => false,
                'value' => null,
                'options' => [
                    ['label' => 'GMT-12:00', 'value' => 'GMT-12:00'],
                    ['label' => 'GMT-11:00', 'value' => 'GMT-11:00'],
                    ['label' => 'GMT-10:00', 'value' => 'GMT-10:00'],
                    ['label' => 'GMT-09:00', 'value' => 'GMT-09:00'],
                    ['label' => 'GMT-08:00', 'value' => 'GMT-08:00'],
                    ['label' => 'GMT-07:00', 'value' => 'GMT-07:00'],
                    ['label' => 'GMT-06:00', 'value' => 'GMT-06:00'],
                    ['label' => 'GMT-05:00', 'value' => 'GMT-05:00'],
                    ['label' => 'GMT-04:00', 'value' => 'GMT-04:00'],
                    ['label' => 'GMT-03:30', 'value' => 'GMT-03:30'],
                    ['label' => 'GMT-03:00', 'value' => 'GMT-03:00'],
                    ['label' => 'GMT-02:00', 'value' => 'GMT-02:00'],
                    ['label' => 'GMT-01:00', 'value' => 'GMT-01:00'],
                    ['label' => 'GMT+00:00', 'value' => 'GMT+00:00'],
                    ['label' => 'GMT+01:00', 'value' => 'GMT+01:00'],
                    ['label' => 'GMT+02:00', 'value' => 'GMT+02:00'],
                    ['label' => 'GMT+03:00', 'value' => 'GMT+03:00'],
                    ['label' => 'GMT+03:30', 'value' => 'GMT+03:30'],
                    ['label' => 'GMT+04:00', 'value' => 'GMT+04:00'],
                    ['label' => 'GMT+04:30', 'value' => 'GMT+04:30'],
                    ['label' => 'GMT+05:00', 'value' => 'GMT+05:00'],
                    ['label' => 'GMT+05:30', 'value' => 'GMT+05:30'],
                    ['label' => 'GMT+05:45', 'value' => 'GMT+05:45'],
                    ['label' => 'GMT+06:00', 'value' => 'GMT+06:00'],
                    ['label' => 'GMT+06:30', 'value' => 'GMT+06:30'],
                    ['label' => 'GMT+07:00', 'value' => 'GMT+07:00'],
                    ['label' => 'GMT+08:00', 'value' => 'GMT+08:00'],
                    ['label' => 'GMT+09:00', 'value' => 'GMT+09:00'],
                    ['label' => 'GMT+09:30', 'value' => 'GMT+09:30'],
                    ['label' => 'GMT+10:00', 'value' => 'GMT+10:00'],
                    ['label' => 'GMT+11:00', 'value' => 'GMT+11:00'],
                    ['label' => 'GMT+12:00', 'value' => 'GMT+12:00'],
                    ['label' => 'GMT+13:00', 'value' => 'GMT+13:00'],
                    ['label' => 'GMT+14:00', 'value' => 'GMT+14:00'],
                ],
            ],
        ];
    }

    /**
     * 获取广告账户选项
     *
     * @return array
     */
    private function getAdAccountOptions(): array
    {
        $adAccounts = FbAdAccount::query()
            ->select('source_id', 'name')
            ->get()
            ->map(function ($account) {
                return [
                    'label' => $account->name . ' - ' . $account->source_id,
                    'value' => $account->source_id,
                ];
            })
            ->toArray();

        return empty($adAccounts) ? [] : $adAccounts;
    }

    /**
     * 获取标签文件夹列表
     *
     * @return array
     */
    private function getTagFolders(): array
    {
        $userId = auth()->id() ?? 0;

        $folders = MetaTagFolders::with(['metaTags.options' => function ($query) {
            $query->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })->with('allChildren');
        }])
            ->where('user_id', $userId)
            ->orderBy('sort')
            ->orderBy('id', 'desc')
            ->get();

        return $folders->map(fn($folder) => [
            'id' => $folder->id,
            'name' => $folder->name,
        ])->toArray();
    }

    /**
     * 获取标签列表
     *
     * @return array
     */
    private function getTags(): array
    {
        $userId = auth()->id() ?? 0;

        $folders = MetaTagFolders::with(['metaTags.options' => function ($query) {
            $query->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })->with('allChildren');
        }])
            ->where('user_id', $userId)
            ->orderBy('sort')
            ->orderBy('id', 'desc')
            ->get();

        return $folders->flatMap(fn($folder) => $folder->metaTags->map(fn($tag) => [
            'id' => $tag->id,
            'folder_id' => $folder->id,
            'name' => $tag->name,
        ]))->toArray();
    }

    /**
     * 构建标签选项树
     *
     * @return array
     */
    public function buildTagOptionsTree(): array
    {
        $options = MetaTagOptions::where(function ($q) {
            $q->whereNull('parent_id')->orWhere('parent_id', 0);
        })->with('allChildren')->get();

        return $this->buildTreeRecursive($options);
    }

    /**
     * 递归构建选项树
     *
     * @param \Illuminate\Support\Collection $options
     * @return array
     */
    private function buildTreeRecursive($options): array
    {
        return $options->map(fn($opt) => [
            'id' => $opt->id,
            'tag_id' => $opt->tag_id,
            'name' => $opt->name,
            'children' => $opt->children->isNotEmpty() ? $this->buildTreeRecursive($opt->children) : [],
        ])->toArray();
    }

    /**
     * 获取组织树（含员工），与 /meta-organizations/tree 接口一致
     * 优化：只需2次查询（组织+用户），通过内存分组避免递归查询
     *
     * @return array
     */
    private function getOrganizationTree(): array
    {
        // 一次查询获取所有组织
        $allOrgs = MetaOrganization::with('users')
            ->where('is_del', 0)
            ->orderBy('sort')
            ->orderBy('id', 'desc')
            ->get();

        // 按 parent_id 分组，根节点 parent_id = 0
        $orgsByParent = $allOrgs->groupBy('parent_id');

        // 从根节点开始构建树
        return $this->buildOrgTreeFromGrouped($orgsByParent, 0);
    }

    /**
     * 从分组数据构建组织树（内存中处理，无需递归查询）
     *
     * @param \Illuminate\Support\Collection $orgsByParent
     * @param int $parentId
     * @return array
     */
    private function buildOrgTreeFromGrouped($orgsByParent, int $parentId): array
    {
        $children = $orgsByParent->get($parentId, collect([]));

        return $children->map(function ($org) use ($orgsByParent) {
            $node = [
                'id' => $org->id,
                'parent_id' => $org->parent_id,
                'name' => $org->name,
                'code' => $org->code,
                'type' => 'org',
                'children' => $this->buildOrgTreeFromGrouped($orgsByParent, $org->id),
                'users' => $org->users->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super' => $user->is_super ?? 0,
                ])->toArray(),
            ];

            return $node;
        })->toArray();
    }
}
