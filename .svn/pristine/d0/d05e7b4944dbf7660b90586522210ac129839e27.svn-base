<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class XmpColumnTemplatesController extends Controller
{
    /**
     * GET /material-library/column-templates
     */
    public function index(Request $request)
    {
        $templates = $this->loadTemplates($request);
        return response()->json([
            'success' => true,
            'data' => [
                'templates' => $templates,
            ],
        ]);
    }

    /**
     * POST /material-library/column-templates
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable',
            'name' => 'required|string|max:100',
            'selectedKeys' => 'nullable|array',
            'selectedKeys.*' => 'nullable',
            'fixedLeftKeys' => 'nullable|array',
            'fixedLeftKeys.*' => 'nullable',
        ]);

        $templates = $this->loadTemplates($request);
        $templateId = (string)($validated['id'] ?? ('tpl_' . uniqid('', true)));

        $normalized = [
            'id' => $templateId,
            'name' => (string)$validated['name'],
            'selectedKeys' => array_values(array_map('strval', $validated['selectedKeys'] ?? [])),
            'fixedLeftKeys' => array_values(array_map('strval', $validated['fixedLeftKeys'] ?? [])),
            'updated_at' => now()->toDateTimeString(),
        ];

        $updated = false;
        foreach ($templates as $idx => $tpl) {
            if ((string)($tpl['id'] ?? '') === $templateId) {
                $templates[$idx] = array_merge($tpl, $normalized);
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $normalized['created_at'] = now()->toDateTimeString();
            $templates[] = $normalized;
        }

        // 保护：最多保留 100 个模板，防止异常膨胀
        if (count($templates) > 100) {
            $templates = array_slice($templates, -100);
        }

        $this->saveTemplates($request, $templates);

        return response()->json([
            'success' => true,
            'message' => $updated ? '模板已更新' : '模板已保存',
            'data' => [
                'template' => $normalized,
                'templates' => $templates,
            ],
        ]);
    }

    private function loadTemplates(Request $request): array
    {
        $path = $this->resolveStoragePath($request);
        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        $raw = Storage::disk('local')->get($path);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        // 兼容两种格式：{templates:[...]} 或直接 [...]
        $templates = $decoded['templates'] ?? $decoded;
        if (!is_array($templates)) {
            return [];
        }

        return array_values(array_filter($templates, function ($tpl) {
            return is_array($tpl) && isset($tpl['name']);
        }));
    }

    private function saveTemplates(Request $request, array $templates): void
    {
        $path = $this->resolveStoragePath($request);
        Storage::disk('local')->put($path, json_encode([
            'templates' => $templates,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function resolveStoragePath(Request $request): string
    {
        $userId = optional($request->user())->id;
        $scope = $userId ? ('user_' . $userId) : 'guest';
        return "material-library/column-templates/{$scope}.json";
    }
}

