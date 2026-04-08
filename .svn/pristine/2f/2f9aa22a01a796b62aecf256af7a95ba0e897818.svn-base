<?php

namespace App\Http\Controllers;

use App\Http\Resources\CloudflareResource;
use App\Jobs\SyncKeitaroLanderToKv;
use App\Models\Cloudflare;
use Illuminate\Http\Request;

class CloudflareController extends BaseController
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
            'email' => $request->get('email'),
            'notes' => $request->get('notes'),
        ];

        $cards = Cloudflare::search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => CloudflareResource::collection($cards->items()),
            'pageSize' => $cards->perPage(),
            'pageNo' => $cards->currentPage(),
            'totalPage' => $cards->lastPage(),
            'totalCount' => $cards->total(),
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
    public function show(Cloudflare $cloudflare)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cloudflare $cloudflare)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cloudflare $cloudflare)
    {
        //
    }

    public function sync_lander_path_to_kv(Request $request)
    {
        SyncKeitaroLanderToKv::dispatch();
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }
}
