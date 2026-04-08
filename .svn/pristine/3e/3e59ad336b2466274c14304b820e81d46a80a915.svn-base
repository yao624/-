<?php

namespace App\Http\Controllers;

use App\Services\MetaMediaMaterialService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MetaMediaMaterialController extends Controller
{
    use ApiResponse;

    private MetaMediaMaterialService $service;

    public function __construct(MetaMediaMaterialService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /material-library/media-materials/list
     * 媒体素材列表
     */
    public function index(Request $request)
    {
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'materialId' => trim((string) $request->query('materialId', '')),
            'channel' => trim((string) $request->query('channel', '')),
            'useAccount' => trim((string) $request->query('useAccount', '')),
            'belongAccount' => trim((string) $request->query('belongAccount', '')),
            'size' => trim((string) $request->query('size', '')),
            'duration' => trim((string) $request->query('duration', '')),
            'shape' => trim((string) $request->query('shape', '')),
            'format' => trim((string) $request->query('format', '')),
            'source' => trim((string) $request->query('source', '')),
            'materialNote' => trim((string) $request->query('materialNote', '')),
            'createTimeStart' => trim((string) $request->query('createTimeStart', '')),
            'createTimeEnd' => trim((string) $request->query('createTimeEnd', '')),
            'rejectInfo' => trim((string) $request->query('rejectInfo', '')),
        ];

        $pageNo = $request->has('pageNo') ? (int) $request->query('pageNo') : null;
        $pageSize = $request->has('pageSize') ? (int) $request->query('pageSize') : null;

        $result = $this->service->getList($filters, $pageNo, $pageSize);

        return $this->success($result);
    }
}
