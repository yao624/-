<?php

namespace App\Http\Controllers\MetaReport;

use App\Http\Controllers\Controller;
use App\Models\MetaReportDimensionDict;
use App\Models\MetaReportMetricDict;
use Illuminate\Http\Request;

class MetaReportDictionaryController extends Controller
{
    public function metrics(Request $request)
    {
        $request->validate([
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $query = MetaReportMetricDict::query()->orderBy('sort_order')->orderBy('metric_key');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'active');
        }

        return response()->json([
            'data' => $query->get()->map(fn (MetaReportMetricDict $metric) => $this->toMetricArray($metric))->values(),
        ]);
    }

    public function dimensions(Request $request)
    {
        $request->validate([
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $query = MetaReportDimensionDict::query()->orderBy('sort_order')->orderBy('dimension_key');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'active');
        }

        return response()->json([
            'data' => $query->get()->map(fn (MetaReportDimensionDict $dimension) => $this->toDimensionArray($dimension))->values(),
        ]);
    }

    private function toMetricArray(MetaReportMetricDict $metric): array
    {
        return [
            'id' => $metric->id,
            'metric_key' => $metric->metric_key,
            'metric_name' => $metric->metric_name,
            'metric_name_en' => $metric->metric_name_en,
            'unit' => $metric->unit,
            'data_type' => $metric->data_type,
            'aggregation_type' => $metric->aggregation_type,
            'supported_levels' => $metric->supported_levels ?? [],
            'supported_chart_types' => $metric->supported_chart_types ?? [],
            'is_filterable' => (bool) $metric->is_filterable,
            'is_sortable' => (bool) $metric->is_sortable,
            'is_permission_controlled' => (bool) $metric->is_permission_controlled,
            'permission_slug' => $metric->permission_slug,
            'sort_order' => (int) $metric->sort_order,
            'status' => $metric->status,
            'description' => $metric->description,
        ];
    }

    private function toDimensionArray(MetaReportDimensionDict $dimension): array
    {
        return [
            'id' => $dimension->id,
            'dimension_key' => $dimension->dimension_key,
            'dimension_name' => $dimension->dimension_name,
            'dimension_name_en' => $dimension->dimension_name_en,
            'value_type' => $dimension->value_type,
            'supported_levels' => $dimension->supported_levels ?? [],
            'is_groupable' => (bool) $dimension->is_groupable,
            'is_filterable' => (bool) $dimension->is_filterable,
            'is_default' => (bool) $dimension->is_default,
            'sort_order' => (int) $dimension->sort_order,
            'status' => $dimension->status,
            'description' => $dimension->description,
        ];
    }
}
