<?php

namespace App\Http\Controllers;

use App\Http\Resources\CronJobResource;
use App\Models\CronJob;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CronJobController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->query('tags', []);

        $searchableFields = [
            'name' => $request->get('name'),
            'object_type' => $request->get('object_type'),
        ];

        if ($request->has('active')) {
            $searchableFields['active'] = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
        }

        $admin = auth()->user()->hasRole('admin');

//        if ($admin) {
//            // 管理员的话，返回所有的 tag, 非管理员，只返回它自己有权限的 tag
//            $cronjobs = CronJob::searchByTagNames($tagNames)->search($searchableFields)->with('tags');
//        } else {
//            $cronjobs = CronJob::searchByTagNames($tagNames)->search($searchableFields)
//                ->where('user_id', auth()->id())->with(['tags' => function ($query) {
//                    $query->wherePivot('user_id', auth()->id());
//                }]);
//        }

        $cronjobs = CronJob::searchByTagNames($tagNames)->search($searchableFields)
            ->where('user_id', auth()->id())->with(['tags' => function ($query) {
                $query->wherePivot('user_id', auth()->id());
            }]);

        $cronjobs = $cronjobs->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => CronJobResource::collection($cronjobs->items()),
            'pageSize' => $cronjobs->perPage(),
            'pageNo' => $cronjobs->currentPage(),
            'totalPage' => $cronjobs->lastPage(),
            'totalCount' => $cronjobs->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store cronjob');

        $userID = auth()->user()->id;
        $request->validate([
            'name' => [
                'required',
                Rule::unique('cron_jobs', 'name')->where(function ($query) use ($userID) {
                    return $query->where('user_id', $userID)
                        ->whereNull('deleted_at');
                }),
            ],
            'object_type' => 'string',
            'object_value' => 'array',
            'object_value.*' => 'string',
            'timezone' => 'string',
            'start_time' => 'string|nullable',
            'stop_time' => 'string|nullable',
            'active' => 'boolean',
            'notes' => 'string|nullable',
            'tags' => 'array|distinct|nullable'
        ]);

        $cronjob = CronJob::create([
            'name' => $request->input('name'),
            'object_type' => $request->input('object_type'),
            'object_value' => $request->input('object_value'),
            'timezone' => $request->input('timezone'),
            'start_time' => $request->input('start_time'),
            'stop_time' => $request->input('stop_time'),
            'user_id' => $userID,
            'active' => $request->input('active'),
            'notes' => $request->input('notes'),
        ]);

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('user_id', $userID)->whereHas('cronJobs')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('cronJobs')->where('name', '=' , $tagName)->first();
            $cronjob->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $cronjob->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        $cronjob->load('tags');

        return new CronJobResource($cronjob);
    }

    /**
     * Display the specified resource.
     */
    public function show(CronJob $cronJob)
    {
        $this->authorize('view', $cronJob);
        $cronJob->load('tags');
        return new CronJobResource($cronJob);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CronJob $cronJob)
    {
        $this->authorize('update', $cronJob);

        $userID = auth()->user()->id;
        // 验证请求数据

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('cron_jobs', 'name')->where(function ($query) use ($userID) {
                    return $query->where('user_id', $userID)
                        ->whereNull('deleted_at');
                })->ignore($cronJob->id)->whereNull('deleted_at')
            ],
            'object_type' => 'string',
            'object_value' => 'array',
            'object_value.*' => 'string',
            'timezone' => 'string',
            'start_time' => 'string|nullable',
            'stop_time' => 'string|nullable',
            'active' => 'boolean',
            'notes' => 'string|nullable',
            'tags' => 'array|distinct|nullable'
        ]);

        // 更新模型实例
        $cronJob->update($validatedData);

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $allTags = $cronJob->tags()->where('tags.user_id', $userID)->pluck('name');
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('user_id', $userID)->whereHas('cronJobs')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);
        $toDeletedTags = $allTags->diff($tagNames);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('cronJobs')->where('name', '=' , $tagName)->first();
            if (!$cronJob->tags->contains($tag->id)) {
                $cronJob->tags()->attach($tag->id, ['user_id' => $userID]);
            }
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $cronJob->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($toDeletedTags as $tagName) {
            Log::debug("delete tag: {$tagName}");
            $tag = Tag::query()->where('user_id', $userID)->whereHas('cronJobs')->where('name', '=' , $tagName)->first();
            if ($tag) {
                $cronJob->tags()->detach($tag->id);
            }
        }

        $cronJob->load('tags');

        // 返回资源
        return new CronJobResource($cronJob);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CronJob $cronJob)
    {
        Log::debug("delete job:");
        $this->authorize('delete', $cronJob);
        $cronJob->delete();
        return response()->json(null, 204);
    }

    public function batchDelete(Request $request)
    {
        $userID = auth()->user()->id;
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);
        $ids = $request->get('ids');
        CronJob::query()->where('user_id', $userID)->whereIn('id', $ids)->delete();

        return response()->json(null, 204);
    }
}
