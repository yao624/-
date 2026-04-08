<?php

namespace App\Http\Controllers\MetaReport;

use App\Http\Controllers\Controller;
use App\Models\MetaReportDashboard;
use App\Models\MetaReportDashboardCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaReportDashboardCardController extends Controller
{
    public function index(MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);
        $cards = $metaReportDashboard->cards()->get()->map(fn (MetaReportDashboardCard $card) => $this->toCardArray($card));

        return response()->json(['data' => $cards]);
    }

    public function store(Request $request, MetaReportDashboard $metaReportDashboard)
    {
        $this->authorizeDashboard($metaReportDashboard);

        $request->validate([
            'title' => 'required|string|max:200',
            'chart_type' => 'required|string|max:64',
            'shape' => 'sometimes|string|in:large,medium,small',
            'sort_order' => 'sometimes|integer|min:0',
            'query_config' => 'sometimes|array|nullable',
            'style_config' => 'sometimes|array|nullable',
        ]);

        $card = $metaReportDashboard->cards()->create([
            'title' => $request->input('title'),
            'chart_type' => $request->input('chart_type'),
            'shape' => $request->input('shape', 'medium'),
            'sort_order' => (int) $request->input('sort_order', $metaReportDashboard->cards()->count()),
            'query_config' => $request->input('query_config'),
            'style_config' => $request->input('style_config'),
        ]);

        return response()->json(['data' => $this->toCardArray($card)], 201);
    }

    public function update(Request $request, MetaReportDashboardCard $metaReportDashboardCard)
    {
        $dashboard = $metaReportDashboardCard->dashboard;
        $this->authorizeDashboard($dashboard);

        $request->validate([
            'title' => 'sometimes|string|max:200',
            'chart_type' => 'sometimes|string|max:64',
            'shape' => 'sometimes|string|in:large,medium,small',
            'sort_order' => 'sometimes|integer|min:0',
            'query_config' => 'sometimes|array|nullable',
            'style_config' => 'sometimes|array|nullable',
        ]);

        $metaReportDashboardCard->fill($request->only([
            'title',
            'chart_type',
            'shape',
            'sort_order',
            'query_config',
            'style_config',
        ]));
        $metaReportDashboardCard->save();

        return response()->json(['data' => $this->toCardArray($metaReportDashboardCard)]);
    }

    public function destroy(MetaReportDashboardCard $metaReportDashboardCard)
    {
        $dashboard = $metaReportDashboardCard->dashboard;
        $this->authorizeDashboard($dashboard);
        $metaReportDashboardCard->forceDelete();

        return response()->json(['success' => true]);
    }

    private function authorizeDashboard(MetaReportDashboard $dashboard): void
    {
        $user = Auth::user();
        if ((string) $dashboard->owner_user_id !== (string) $user->id) {
            abort(403, '无权操作该看板');
        }
    }

    private function toCardArray(MetaReportDashboardCard $card): array
    {
        return [
            'id' => $card->id,
            'dashboard_id' => $card->dashboard_id,
            'title' => $card->title,
            'chart_type' => $card->chart_type,
            'shape' => $card->shape,
            'sort_order' => (int) $card->sort_order,
            'query_config' => $card->query_config,
            'style_config' => $card->style_config,
            'created_at' => $card->created_at?->toIso8601String(),
            'updated_at' => $card->updated_at?->toIso8601String(),
        ];
    }
}

