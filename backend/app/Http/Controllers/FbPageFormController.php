<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbPageFormResource;
use App\Models\FbPageForm;
use Illuminate\Http\Request;

class FbPageFormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 50);
        $pageNo = $request->get('pageNo', 1);


        $searchableFields = [
            'name' => $request->get('name'),
            'page_source_id' => $request->get('page_source_id'),
            'page_name' => $request->get('page_name'),
            'follow_up_action_url' => $request->get('follow_up_action_url'),
            'notes' => $request->get('notes')
        ];

        $fbPageForm = FbPageForm::search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbPageFormResource::collection($fbPageForm->items()),
            'pageSize' => $fbPageForm->perPage(),
            'pageNo' => $fbPageForm->currentPage(),
            'totalPage' => $fbPageForm->lastPage(),
            'totalCount' => $fbPageForm->total(),
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
    public function show(FbPageForm $fbPageForm)
    {
        return $fbPageForm;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbPageForm $fbPageForm)
    {
        $cleanData = $request->validate([
            'notes' => 'string'
        ]);

        $notes = $cleanData['notes'];
        $fbPageForm->notes = $notes;
        $fbPageForm->save();

        return $fbPageForm;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbPageForm $fbPageForm)
    {
        //
    }
}
