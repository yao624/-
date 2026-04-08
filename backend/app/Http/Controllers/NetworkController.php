<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClickResource;
use App\Http\Resources\ConversionResource;
use App\Http\Resources\NetworkResource;
use App\Http\Resources\TagResource;
use App\Jobs\TriggerNetworkFetchClicks;
use App\Jobs\TriggerNetworkFetchConversions;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\FbAd;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\Network;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use function Psy\debug;

class NetworkController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        # TODO: 根据 user id 查询
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
            'endpoint' => $request->get('endpoint'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $user_id = Auth::id();
        $networks = Network::searchByTagNames($tagNames)->search($searchableFields)->where('user_id', $user_id)
            ->orderBy($sortField, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => NetworkResource::collection($networks->items()),
            'pageSize' => $networks->perPage(),
            'pageNo' => $networks->currentPage(),
            'totalPage' => $networks->lastPage(),
            'totalCount' => $networks->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store networks');
        $request->validate([
            'name' => 'required|unique:networks',
            'system_type' => 'required|in:Cake,Everflow,Jumb,Keitaro',
            'aff_id' => 'required',
            'endpoint' => 'required',
            'apikey' => 'required',
            'click_placeholder' => 'required',
            'active' => 'required',
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

        // 获取当前登录的用户
        $user = Auth::user();

        $network = Network::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'system_type' => $request->input('system_type'),
            'aff_id' => $request->input('aff_id'),
            'endpoint' => $request->input('endpoint'),
            'apikey' => $request->input('apikey'),
            'click_placeholder' => $request->input('click_placeholder'),
            'notes' => $request->input('notes', ''),
            'active' => $request->input('active', false)
        ]);

        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $network->tags()->sync($tagIds);

        // 预加载 tags
        $network->load('tags');

        return new NetworkResource($network);
    }

    /**
     * Display the specified resource.
     */
    public function show(Network $network)
    {
        return new NetworkResource($network);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Network $network)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'name' => [
                'required',
                'unique:networks,name,' . $network->id,
            ],
            'system_type' => ['required', 'in:Cake,Everflow,Keitaro,Jumb'],
            'aff_id' => ['required'],
            'endpoint' => ['required'],
            'apikey' => ['required'],
            'click_placeholder' => ['required'],
            'active' => ['required', 'boolean'], // 确保 active 是布尔类型
        ]);

        // 更新模型实例
        $network->update($validatedData);
        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $network->tags()->sync($tagIds);

        // 返回资源
        return new NetworkResource($network);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Network $network)
    {
        $network->delete();
        return response()->json(null, 204);
    }

    public function fetch_clicks(Request $request)
    {
        $request->validate([
            'network_ids' => 'required|array',
            'network_ids.*' => 'required|string',
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|gte:date_start',
        ]);

        TriggerNetworkFetchClicks::dispatch($request->input('network_ids'), $request->input('date_start'),
            $request->input('date_stop'))->onQueue('network');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function fetch_conversions(Request $request)
    {
        $request->validate([
            'network_ids' => 'required|array',
            'network_ids.*' => 'required|string',
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|gte:date_start',
        ]);

        TriggerNetworkFetchConversions::dispatch($request->input('network_ids'), $request->input('date_start'),
            $request->input('date_stop'))->onQueue('network');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function fetch_all(Request $request)
    {
        $request->validate([
            'network_ids' => 'required|array',
            'network_ids.*' => 'required|string',
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d',
        ]);

        TriggerNetworkFetchClicks::dispatch($request->input('network_ids'), $request->input('date_start'),
            $request->input('date_stop'))->onQueue('network');
        TriggerNetworkFetchConversions::dispatch($request->input('network_ids'), $request->input('date_start'),
            $request->input('date_stop'))->onQueue('network');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function fetch_keitaro(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d',
        ]);

        $keitaro_ids[] = env('KEITARO_ID', '');

        TriggerNetworkFetchClicks::dispatch($keitaro_ids, $request->input('date_start'),
            $request->input('date_stop'))->onQueue('network');
        TriggerNetworkFetchConversions::dispatch($keitaro_ids, $request->input('date_start'),
            $request->input('date_stop'))->onQueue('network');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function clicks(Request $request)
    {
        $request->validate([
            'click_date_start' => 'nullable|numeric',
            'click_date_stop' => 'nullable|numeric|gte:click_date_start',
            'network_ids' => 'array',
            'network_ids.*' => 'string',
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'string',
            'adset_ids' => 'array',
            'adset_ids.*' => 'string',
            'ad_ids' => 'array',
            'ad_ids.*' => 'string',
            'geos' => 'array|nullable'
        ]);

        $sortField = $request->get('sortField', 'click_datetime');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'offer_source_name' => $request->get('offer_name'),
            'sub_2' => $request->get('sub_2')
        ];

        $clicks = Click::search($searchableFields);

        $click_date_start = $request->input('click_date_start');
        $click_date_stop = $request->input('click_date_stop');
        if ($click_date_start) {
            $clicks = $clicks->where('click_datetime', '>=', Carbon::createFromTimestamp($click_date_start));
        }
        if ($click_date_stop) {
            $clicks = $clicks->where('click_datetime', '<=', Carbon::createFromTimestamp($click_date_stop));
        }

        $network_ids = $request->get('network_ids');
        if ($network_ids) {
            Log::debug('network id');
            Log::debug($network_ids);
            $clicks = $clicks->whereIn('network_id', $network_ids);
        }

        $campaign_ids = $request->get('campaign_ids');
        if ($campaign_ids) {
            Log::debug("not empty");
            Log::debug($campaign_ids);
            $clicks->whereIn('fb_campaign_source_id', $campaign_ids);
        }

        $adset_ids = $request->get('adset_ids');
        if ($adset_ids) {
            $clicks = $clicks->whereIn('fb_adset_source_id', $adset_ids);
        }

        $ad_ids = $request->get('ad_ids');
        if ($ad_ids) {
            $clicks = $clicks->whereIn('fb_ad_source_id', $ad_ids);
        }

        $offer_names = $request->get('offer_names');
        if ($offer_names) {
            $clicks = $clicks->where(function ($query) use ($offer_names) {
                foreach ($offer_names as $offer_name) {
                    $query->orWhere('offer_source_name', 'LIKE', '%' . $offer_name . '%');
                }
            });
        }

        $geos = $request->get('geos');
        if ($geos) {
            $clicks = $clicks->whereIn('country_code', $geos);
        }

        $clicks = $clicks->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => ClickResource::collection($clicks->items()),
            'pageSize' => $clicks->perPage(),
            'pageNo' => $clicks->currentPage(),
            'totalPage' => $clicks->lastPage(),
            'totalCount' => $clicks->total(),
        ];
    }

    public function conversions(Request $request)
    {

        $request->validate([
            'click_date_start' => 'nullable|numeric',
            'click_date_stop' => 'nullable|numeric|gte:click_date_start',
            'network_ids' => 'array',
            'network_ids.*' => 'string',
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'string',
            'adset_ids' => 'array',
            'adset_ids.*' => 'string',
            'ad_ids' => 'array',
            'ad_ids.*' => 'string',
            'conv_type' => 'in:all,sale,lead',
            'geos' => 'nullable|array'
        ]);

        $sortField = $request->get('sortField', 'conversion_datetime');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'offer_source_name' => $request->get('offer_name'),
            'network_id' => $request->get('network_id'),
            'sub_2' => $request->get('sub_2')
        ];

        $convs = Conversion::search($searchableFields);

        $conv_date_start = $request->input('conv_date_start');
        $conv_date_stop = $request->input('conv_date_stop');
        if ($conv_date_start) {
            $convs = $convs->where('conversion_datetime', '>=', Carbon::createFromTimestamp($conv_date_start));
        }
        if ($conv_date_stop) {
            $convs = $convs->where('conversion_datetime', '<=', Carbon::createFromTimestamp($conv_date_stop));
        }

        $network_ids = $request->get('network_ids');
        if ($network_ids) {
            $convs = $convs->whereIn('network_id', $network_ids);
        }

        $campaign_ids = $request->get('campaign_ids');
        if ($campaign_ids) {
            $convs->whereIn('fb_campaign_source_id', $campaign_ids);
        }

        $adset_ids = $request->get('adset_ids');
        if ($adset_ids) {
            $convs = $convs->whereIn('fb_adset_source_id', $adset_ids);
        }

        $ad_ids = $request->get('ad_ids');
        if ($ad_ids) {
            $convs = $convs->whereIn('fb_ad_source_id', $ad_ids);
        }

        $offer_names = $request->get('offer_names');
        if ($offer_names) {
            $convs = $convs->where(function ($query) use ($offer_names) {
                foreach ($offer_names as $offer_name) {
                    $query->orWhere('offer_source_name', 'LIKE', '%' . $offer_name . '%');
                }
            });
        }

        $conv_type = $request->get('conv_type');
        if ($conv_type === 'sale') {
            $convs = $convs->where('price', '>', 0);
        } else if($conv_type === 'lead') {
            $convs = $convs->where('price', '=', 0);
        }

        $geos = $request->get('geos');
        if ($geos) {
            Log::debug("not empty");
            Log::debug($campaign_ids);
            $convs->whereIn('country_code', $geos);
        } else {
            Log::debug("empty");
        }

        $convs = $convs->orderBy($sortField, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => ConversionResource::collection($convs->items()),
            'pageSize' => $convs->perPage(),
            'pageNo' => $convs->currentPage(),
            'totalPage' => $convs->lastPage(),
            'totalCount' => $convs->total(),
        ];
    }

    public function associateNetworksToSubidMapping(Request $request)
    {
        // 验证请求数据
        $request->validate([
            'network_ids' => 'required|array',
            'subid_mapping_id' => 'required|exists:subid_mappings,id',
        ]);

        // 获取请求的数据
        // TODO: 校验 user id 与 network 的 user
        $networkIds = $request->input('network_ids');

        $user_id = Auth::id();
        $cleanNetworkIds = Network::query()->whereIn('id', $networkIds)->where('user_id', $user_id)
            ->pluck('id');
        $subidMappingId = $request->input('subid_mapping_id');

        // 更新 network 模型记录
        Network::whereIn('id', $cleanNetworkIds)->update(['subid_mapping_id' => $subidMappingId]);

        return response()->json([
            'message' => 'Networks associated successfully.',
        ]);
    }

    public function delete_network_cv(Request $request)
    {
        $request->validate([
            'network_ids' => 'required|array',
        ]);

        $networkIds = $request->input('network_ids');
        $user_id = Auth::id();
        $cleanNetworkIds = Network::query()->whereIn('id', $networkIds)->where('user_id', $user_id)
            ->pluck('id');

        Network::query()->whereIn('id', $cleanNetworkIds)->each(function ($query) {
            $query->conversions()->delete();
        });

        return response()->json([
            'message' => 'done',
        ]);
    }

    public function delete_network_clicks(Request $request)
    {
        $request->validate([
            'network_ids' => 'required|array',
        ]);

        $networkIds = $request->input('network_ids');
        $user_id = Auth::id();
        $cleanNetworkIds = Network::query()->whereIn('id', $networkIds)->where('user_id', $user_id)
            ->pluck('id');

        Network::query()->whereIn('id', $cleanNetworkIds)->each(function ($query) {
            $query->clicks()->delete();
        });

        return response()->json([
            'message' => 'done',
        ]);
    }
}
