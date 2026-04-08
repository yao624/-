<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Meta 9 步表单 step key（与前端 formData 一致），逻辑原 MetaAdCreationFormDataNormalizer。
     *
     * @var list<string>
     */
    private const META_AD_CREATION_STEP_KEYS = [
        'stepOne',
        'stepTwo',
        'stepDelivery',
        'stepRegion',
        'stepPlacement',
        'stepTargeting',
        'stepBidBudget',
        'stepCreativeSettings',
        'stepCreativeGroup',
    ];

    /**
     * 与 9 步并列写入 form_data 的扩展键（前端 index.vue 根级字段，非「第 N 步」）。
     *
     * @var list<string>
     */
    private const META_AD_CREATION_EXTRA_FORM_KEYS = [
        'splitRules',
    ];

    /**
     * 规范化 form_data：保留 9 步 + 扩展键（如 splitRules）；其余顶层字段丢弃。
     *
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    protected static function normalizeMetaAdCreationFormData(array $formData): array
    {
        $out = [];
        foreach (self::META_AD_CREATION_STEP_KEYS as $key) {
            $out[$key] = isset($formData[$key]) && is_array($formData[$key])
                ? $formData[$key]
                : [];
        }
        foreach (self::META_AD_CREATION_EXTRA_FORM_KEYS as $key) {
            if (isset($formData[$key]) && is_array($formData[$key])) {
                $out[$key] = $formData[$key];
            }
        }

        return $out;
    }

    /**
     * stepOne.adAccount（26 位 ULID）
     */
    protected static function metaAdCreationGetAdAccountId(array $formData): ?string
    {
        $id = $formData['stepOne']['adAccount'] ?? null;

        return $id && strlen((string) $id) === 26 ? (string) $id : null;
    }

    /**
     * stepRegion.regionGroupId（26 位 ULID）
     */
    protected static function metaAdCreationGetRegionGroupId(array $formData): ?string
    {
        $id = $formData['stepRegion']['regionGroupId'] ?? null;

        return $id && strlen((string) $id) === 26 ? (string) $id : null;
    }

    /**
     * stepCreativeGroup.creativeGroupId（26 位 ULID）
     */
    protected static function metaAdCreationGetCreativeGroupId(array $formData): ?string
    {
        $id = $formData['stepCreativeGroup']['creativeGroupId'] ?? null;

        return $id && strlen((string) $id) === 26 ? (string) $id : null;
    }
}
