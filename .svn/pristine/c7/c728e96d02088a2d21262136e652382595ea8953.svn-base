<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdLogResource;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CardResource;
use App\Models\AdLog;
use App\Models\Agent;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdLogController extends BaseController
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

        $searchableFields = [
            'name' => $request->get('name'),
            'number' => $request->get('number'),
            'notes' => $request->get('notes'),
        ];

        $user_id = Auth::id();
        $logs = AdLog::with('campaignPivot', 'adsetPivot', 'adPivot', 'materials', 'adTemplate', 'copywriting',
            'link', 'page', 'pixel', 'adAccount', 'tokenUser', 'user')->search($searchableFields)
            ->where('user_id', $user_id)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => AdLogResource::collection($logs->items()),
            'pageSize' => $logs->perPage(),
            'pageNo' => $logs->currentPage(),
            'totalPage' => $logs->lastPage(),
            'totalCount' => $logs->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AdLog $adLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AdLog $adLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdLog $adLog)
    {
        //
    }
}
