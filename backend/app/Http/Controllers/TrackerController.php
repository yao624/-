<?php

namespace App\Http\Controllers;

use App\Http\Resources\RuleResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TrackerResource;
use App\Jobs\TrackerFetchData;
use App\Models\Rule;
use App\Models\Tag;
use App\Models\Tracker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrackerController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'is_archived' => 'in:true,false',
        ]);

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'url' => $request->get('url'),
        ];

        $trackers = Tracker::search($searchableFields);

        if ($request->get('is_archived')) {
            $trackers = $trackers->where('is_archived', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $trackers = $trackers->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => TrackerResource::collection($trackers->items()),
            'pageSize' => $trackers->perPage(),
            'pageNo' => $trackers->currentPage(),
            'totalPage' => $trackers->lastPage(),
            'totalCount' => $trackers->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'string|nullable',
            'name' => 'required|unique:trackers',
            'type' => 'required|in:keitaro',
            'username' => 'required|string',
            'password' => 'required|string',
            'url' => 'required|string|unique:trackers',
            'is_archived' => 'boolean|nullable',
        ]);

        $tracker = Tracker::query()->create($validated);
        return new TrackerResource($tracker);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function fetchData(Request $request)
    {
        $clean_data = $request->validate([
            'ids' => 'array|nullable',
            'ids.*' => 'string',
        ]);

        $ids = $clean_data['ids'];
        if (is_null($ids)) {
            $trackers = Tracker::all(['id', 'name'])->map(function ($tracker) {
                return [
                    'id' => $tracker->id,
                    'name' => $tracker->name,
                ];
            })->toArray();
        } else {
            $trackers = Tracker::whereIn('id', $ids)->select(['id', 'name'])->get()->map(function ($tracker) {
                return [
                    'id' => $tracker->id,
                    'name' => $tracker->name,
                ];
            })->toArray();
        }

        foreach ($trackers as $tracker) {
            $id = $tracker['id'];
            $name = $tracker['name'];

            $currentDate = Carbon::now();
            $date_start = $currentDate->copy()->subDays(7)->format('Y-m-d');
            $date_stop = $currentDate->copy()->addDay()->format('Y-m-d');

            TrackerFetchData::dispatch($id, $name, $date_start, $date_stop)->onQueue('facebook');
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    function getClickLossData($date_start, $date_stop, $pageNo = 1, $pageSize = 10) {
        // 使用 DB 查询构建器来连接并计算所需数据
        $results = DB::table('tracker_campaigns AS tc')
            ->join('tracker_offer_clicks AS toc', 'tc.id', '=', 'toc.tracker_campaign_id')
            ->leftJoin('clicks AS c', 'toc.subid', '=', 'c.sub_2')
            ->join('trackers AS t', 'tc.tracker_id', '=', 't.id') // 确保 join trackers 表
            ->select(
                'tc.id AS campaign_id',
                'tc.campaign_name AS campaign_name',  // 使用 campaign_name
                't.name AS tracker_name',               // trackers 表的 name
                DB::raw('COUNT(toc.id) AS tracker_offer_clicks_count'),
                DB::raw('COUNT(c.id) AS clicks_count'),
                DB::raw('(COUNT(toc.id) - COUNT(c.id)) / NULLIF(COUNT(toc.id), 0) AS loss_ratio') // 注意：除以零的情况
            )
            ->whereBetween('toc.click_date', [$date_start, $date_stop])
            ->groupBy('tc.id', 'tc.campaign_name', 't.name') // 添加到 groupBy
            ->orderBy('loss_ratio', 'desc') // 先按点损比例排序
            ->paginate($pageSize, ['*'], 'page', $pageNo); // 处理分页

        return $results;
    }

    public function status(Request $request)
    {
        $request->validate([
            'tracker_id' => 'string',
            'date_start' => 'required|integer',
            'date_stop' => 'required|integer|gte:date_start',
            'pageNo' => 'integer|min:1',
            'pageSize' => 'integer|min:1|max:100', // 限制每页大小，防止过大
        ]);

        $pageNo = $request->input('pageNo', 1); // 默认值为 1
        $pageSize = $request->input('pageSize', 10); // 默认值为 10

        // 从 Unix 时间戳转换为 MySQL 日期格式
        $date_start = Carbon::createFromTimestamp($request->input('date_start'))->format('Y-m-d H:i:s');
        $date_stop = Carbon::createFromTimestamp($request->input('date_stop'))->format('Y-m-d H:i:s');

        $query = $this->getClickLossData($date_start, $date_stop, $pageNo, $pageSize);

        return [
            'data' => $query->items(),
            'pageSize' => $query->perPage(),
            'pageNo' => $query->currentPage(),
            'totalPage' => $query->lastPage(),
            'totalCount' => $query->total(),
        ];

    }
}
