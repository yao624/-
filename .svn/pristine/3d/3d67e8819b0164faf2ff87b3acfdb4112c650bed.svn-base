<?php

namespace App\Http\Controllers;

use App\Http\Resources\NetworkResource;
use App\Http\Resources\RuleResource;
use App\Http\Resources\SubidMappingResource;
use App\Models\Network;
use App\Models\Rule;
use App\Models\SubidMapping;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubidMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user_id = Auth::id();

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
        ];

        $mappings = SubidMapping::search($searchableFields)->where('user_id', $user_id)
            ->orderBy($sortField, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => SubidMappingResource::collection($mappings->items()),
            'pageSize' => $mappings->perPage(),
            'pageNo' => $mappings->currentPage(),
            'totalPage' => $mappings->lastPage(),
            'totalCount' => $mappings->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store subidmapping');
        $request->validate([
            'name' => 'required|string',
            'subid_1' => 'required|string',
            'subid_2' => 'required|string',
            'subid_3' => 'required|string',
            'subid_4' => 'required|string',
            'subid_5' => 'required|string',
            'fb_campaign_id' => 'nullable|string',
            'fb_adset_id' => 'nullable|string',
            'fb_ad_id' => 'nullable|string',
        ]);

        // 获取当前登录的用户
        $user = Auth::user();

        $mapping = SubidMapping::create([
            'name' => $request->input('name'),
            'user_id' => $user->id,
            'subid_1' => $request->input('subid_1'),
            'subid_2' => $request->input('subid_2'),
            'subid_3' => $request->input('subid_3'),
            'subid_4' => $request->input('subid_4'),
            'subid_5' => $request->input('subid_5'),
            'fb_campaign_id' => $request->input('fb_campaign_id', ''),
            'fb_adset_id' => $request->input('fb_adset_id', ''),
            'fb_ad_id' => $request->input('fb_ad_id', ''),
        ]);

        return new SubidMappingResource($mapping);
    }

    /**
     * Display the specified resource.
     */
    public function show(SubidMapping $subidMapping)
    {
        return new SubidMappingResource($subidMapping);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubidMapping $subidMapping)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubidMapping $subidMapping)
    {
        //
    }
}
