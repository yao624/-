<?php

namespace App\Http\Controllers;

use App\Http\Resources\RuleResource;
use App\Jobs\AutomationPipeline;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RuleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'is_active' => 'in:true,false',
        ]);
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
        ];

        $rules = Rule::query()->where('user_id', auth()->id())->searchByTagNames($tagNames)->search($searchableFields);

        if ($request->get('is_active')) {
            $rules = $rules->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $rules = $rules->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->with(['fbAdAccounts', 'fbCampaigns', 'fbAdsets', 'fbAds'])
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => RuleResource::collection($rules->items()),
            'pageSize' => $rules->perPage(),
            'pageNo' => $rules->currentPage(),
            'totalPage' => $rules->lastPage(),
            'totalCount' => $rules->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("create a rule");
        $request->validate([
            'name' => 'required | string',
            'scope' => 'required | string',
            'date_preset' => 'required | string',
            'relation' => 'required | boolean',
            'conditions' => 'required|array',
            'ad_account_ids' => 'required_without:resource_ids|array',
            'resource_ids' => 'required_without:ad_account_ids|array',
            'resource_ids.*' => 'string',
            'actions' => 'required | array',
            'actions.*' => 'array',
            'white_list' => 'array'
        ]);
        $input = $request->all();

        # TODO: 创建规则这里需要检查 condition 是否符合要求

        $scope = $request->get('scope');
        $allowed_scopes = ['ad_account', 'campaign', 'adset', 'ad', 'camp_tag', 'adset_tag', 'ad_tag'];
        if (!in_array($scope, $allowed_scopes)) {
            Log::warning("$scope is not suported");
            return response()->json([
                "message" => trans('message.scope_not_supported', ['value'=>$scope], $this->language),
                "errors" => [
                    "scope" => [
                        trans('message.scope_not_supported', ['value'=>$scope], $this->language),
                    ]
                ]
            ], 400);
        }

        $date_preset = $request->get('date_preset');
        $allowed_preset = ['today', 'lifetime'];
        if (!in_array($date_preset, $allowed_preset)) {
            Log::warning("$date_preset is not supported");
            return response()->json([
                "message" => trans('message.date_preset_not_supported', ['value'=>$date_preset], $this->language),
                "errors" => [
                    "scope" => [
                        trans('message.date_preset_not_supported', ['value'=>$date_preset], $this->language),
                    ]
                ]
            ], 400);
        }

        $resource_ids = collect($request->get('resource_ids'));
        $rule = DB::transaction(function () use ($request, $input, $scope, $resource_ids) {
            $rule = Rule::create($input);
            $rule->user_id = auth()->id();
            $rule->save();

            if ($scope === 'campaign') {
                $filtered_ids = FbCampaign::query()->whereIn('id', $resource_ids)->pluck('id');
                $rule->fbCampaigns()->sync($filtered_ids);
            } else if ($scope === 'adset') {
                $filtered_ids = FbAdset::query()->whereIn('id', $resource_ids)->pluck('id');
                $rule->fbAdsets()->sync($filtered_ids);
            } else if ($scope === 'ad') {
                $filtered_ids = FbAd::query()->whereIn('id', $resource_ids)->pluck('id');
                $rule->fbAds()->sync($filtered_ids);
            }
            $ad_account_ids = collect($request->get('ad_account_ids'));
            $filtered_ids = FbAdAccount::query()->whereIn('id', $ad_account_ids)->pluck('id');
            $rule->fbAdAccounts()->sync($filtered_ids);

            return $rule;
        });

        $rule = $rule->fresh()->load(['fbAdAccounts', 'fbCampaigns', 'fbAdsets', 'fbAds']);

        return new RuleResource($rule);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rule $rule)
    {
        $this->authorize('view', $rule);
        return new RuleResource($rule->with(['fbAdAccounts', 'fbCampaigns', 'fbAdsets', 'fbAds']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rule $rule)
    {
        Log::info("update a rule");
        $this->authorize('update', $rule);

        $request->validate([
            'name' => 'required | string',
            'scope' => 'required | string',
            'date_preset' => 'required | string',
            'relation' => 'required | boolean',
            'conditions' => 'required|array',
            'ad_account_ids' => 'required_without:resource_ids|array',
            'resource_ids' => 'required_without:ad_account_ids|array',
            'resource_ids.*' => 'string',
            'actions' => 'required | array',
            'actions.*' => 'array',
            'white_list' => 'array'
        ]);

        $old_scope = $rule->scope;
        $new_scope = $request->get('scope');
        if ($new_scope !== $old_scope) {
            Log::warning("scope is not changed");
            return response()->json([
                "message" => trans('message.scope_not_allow_to_change', [], $this->language),
                "errors" => [
                    "scope" => [
                        trans('message.scope_not_allow_to_change', [], $this->language),
                    ]
                ]
            ], 400);
        }

        # TODO: 创建规则这里需要检查 condition 是否符合要求

        $scope = $rule->scope;
        $allowed_scopes = ['ad_account', 'campaign', 'adset', 'ad', 'camp_tag', 'adset_tag', 'ad_tag'];
        if (!in_array($scope, $allowed_scopes)) {
            Log::warning("$scope is not suported");
            return response()->json([
                "message" => trans('message.scope_not_supported', ['value'=>$scope], $this->language),
                "errors" => [
                    "scope" => [
                        trans('message.scope_not_supported', ['value'=>$scope], $this->language),
                    ]
                ]
            ], 400);
        }

        $date_preset = $request->get('date_preset');
        $allowed_preset = ['today', 'lifetime'];
        if (!in_array($date_preset, $allowed_preset)) {
            Log::warning("$date_preset is not supported");
            return response()->json([
                "message" => trans('message.date_preset_not_supported', ['value'=>$date_preset], $this->language),
                "errors" => [
                    "scope" => [
                        trans('message.date_preset_not_supported', ['value'=>$date_preset], $this->language),
                    ]
                ]
            ], 400);
        }

        $resource_ids = collect($request->get('resource_ids'));
        $rule = DB::transaction(function () use ($rule, $request, $scope, $resource_ids) {
            $data = $request->except('scope');
            $rule->update($data);
            if ($scope === 'campaign') {
                $filtered_ids = FbCampaign::query()->whereIn('id', $resource_ids)->pluck('id');
                $rule->fbCampaigns()->sync($filtered_ids);
            } else if ($scope === 'adset') {
                $filtered_ids = FbAdset::query()->whereIn('id', $resource_ids)->pluck('id');
                $rule->fbAdsets()->sync($filtered_ids);
            } else if ($scope === 'ad') {
                $filtered_ids = FbAd::query()->whereIn('id', $resource_ids)->pluck('id');
                $rule->fbAds()->sync($filtered_ids);
            }
            $ad_account_ids = collect($request->get('ad_account_ids'));
            $filtered_ids = FbAdAccount::query()->whereIn('id', $ad_account_ids)->pluck('id');
            $rule->fbAdAccounts()->sync($filtered_ids);

            return $rule;
        });

        $rule = $rule->fresh()->load(['fbAdAccounts', 'fbCampaigns', 'fbAdsets', 'fbAds']);
        return new RuleResource($rule);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rule $rule)
    {
        $this->authorize('delete', $rule);
        $rule->delete();
        return response()->json(null, 204);
    }

    public function batch_delete(Request $request)
    {
        $request->validate([
           'ids' => 'required|array',
           'ids.*' => 'string'
        ]);
        $user = $request->user(); // 获取当前经过认证的用户
        $ids = $request->get('ids');

        // 如果用户尝试使用全局删除，让它失败或者针对你的业务逻辑执行其他处理
        if ($ids[0] == -1) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        } else {
            // 找到用户所拥有的所有规则
            $userOwnedRules = Rule::query()
                ->whereIn('id', $ids)
                ->where('user_id', $user->id)
                ->get();

            // 检查用户试图删除的规则是否都属于他们
            if (count($userOwnedRules) !== count($ids)) {
                return response()->json(['error' => 'Some rules do not belong to the user.'], 403);
            }

            // 删除属于用户的规则
            Rule::query()
                ->whereIn('id', $userOwnedRules->pluck('id'))
                ->delete();
        }

        return response()->json(null, 204);
    }

    /*
     * 批量分配多个规则到多个资源上
     */
    public function assign_rules(Request $request)
    {
        # TODO: 鉴权没有处理
        Log::info("assign a rules");

        $request->validate([
            'rule_ids' => 'required | array',
            'rule_ids.*' => 'string',
            'resource_ids' => 'required | array',
            'resource_ids.*' => 'string'
        ]);

        // 获取存在的 rules，再根据 rule 的 scope，获取已经存在的资源
        $clean_rules = Rule::query()->whereIn('id', $request->get('rule_ids'))->get();
        if (!$clean_rules) {
            return response()->json([
                "message" => "contains invalid rule id",
                "errors" => [
                    "scope" => [
                        "contains invalid rule id"
                    ]
                ]
            ], 400);
        }

        $scope = Rule::query()->firstWhere('id', $clean_rules[0]->id)->scope;
        # 所有的 rule scope 必须相同
        foreach ($clean_rules as $rule) {
            $current_scope = $rule->scope;
            if ($scope != $current_scope) {
                return response()->json([
                    "message" => "rules scope should be the same",
                    "errors" => [
                        "scope" => [
                            "rules scope should be the same"
                        ]
                    ]
                ], 400);
            }
        }

        # 把 rule 绑定到 resource 上
        foreach ($clean_rules as $rule) {
            if ($scope === 'ad_account') {
                $clean_resource_id = FbAdAccount::query()->whereIn('id', $request->get('resource_ids'))->pluck('id');
                $rule->fbAdAccounts()->syncWithoutDetaching($clean_resource_id);
            } else if ($scope === 'campaign') {
                $clean_resource_id = FbCampaign::query()->whereIn('id', $request->get('resource_ids'))->pluck('id');
                $rule->fbCampaigns()->syncWithoutDetaching($clean_resource_id);
            } else if ($scope === 'adset') {
                $clean_resource_id = FbAdset::query()->whereIn('id', $request->get('resource_ids'))->pluck('id');
                $rule->fbAdsets()->syncWithoutDetaching($clean_resource_id);
            } else if ($scope === 'ad') {
                $clean_resource_id = FbAd::query()->whereIn('id', $request->get('resource_ids'))->pluck('id');
                $rule->fbAds()->syncWithoutDetaching($clean_resource_id);
            }
        }
        return response()->json([
            'message' => '',
            'success' => true
        ]);
    }

    public function active(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);
        $ids = $request->get('ids');
        Rule::whereIn('id', $ids)->update(['is_active' => true]);

        return response()->json([
            'message' => 'active successful',
            'success' => true,
        ]);

    }

    public function inactive(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);
        $ids = $request->get('ids');
        Rule::whereIn('id', $ids)->update(['is_active' => false]);

        return response()->json([
            'message' => 'inactive successful',
            'success' => true,
        ]);

    }

    public function triggerAutomationPipeline(Request $request)
    {
        Log::info("trigger automation pipeline");
        AutomationPipeline::dispatch()->onQueue('facebook');
        return response()->json([
            'message' => '正在获取数据并检查规则',
            'success' => true,
        ]);
    }

}
