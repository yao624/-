<?php

namespace App\Services\Locale;

/**
 * 将国家代码（ISO-3166-1 alpha-2）扩展为 Meta locale 优先列表。
 *
 * 约定：
 * - 输入支持：逗号/空格分隔，如 "US,CA" 或 "US CA"
 * - 输出为 locale 列表（按优先级排序），用于文案翻译解析
 * - 未命中时，回退到 en_US
 *
 * 注意：这不是完整的全球映射，只覆盖常用投放国家；后续可按业务补全。
 */
final class CountryToMetaLocales
{
    /**
     * @return list<string>
     */
    public static function expand(string $countryCodes): array
    {
        $codes = preg_split('/[\s,]+/', strtoupper(trim($countryCodes)), -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($codes) || $codes === []) {
            return [];
        }

        $out = [];
        foreach ($codes as $cc) {
            $cc = trim((string) $cc);
            if ($cc === '') {
                continue;
            }

            foreach (self::countryDefaultLocales($cc) as $loc) {
                $loc = trim((string) $loc);
                if ($loc === '' || in_array($loc, $out, true)) {
                    continue;
                }
                $out[] = $loc;
            }
        }

        if ($out === []) {
            return ['en_US'];
        }

        // 保底追加英文回退（如果不存在）
        if (! in_array('en_US', $out, true)) {
            $out[] = 'en_US';
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function countryDefaultLocales(string $cc): array
    {
        // 常用国家优先映射（可按投放国家逐步补全）
        // 说明：Meta locale 多为 language_COUNTRY（例如 zh_CN, en_US）
        static $map = [
            // English
            'US' => ['en_US'],
            'GB' => ['en_GB', 'en_US'],
            'CA' => ['en_CA', 'en_US', 'fr_CA'],
            'AU' => ['en_AU', 'en_US'],
            'NZ' => ['en_NZ', 'en_US'],
            'IE' => ['en_IE', 'en_GB', 'en_US'],

            // Chinese
            'CN' => ['zh_CN', 'en_US'],
            'TW' => ['zh_TW', 'en_US'],
            'HK' => ['zh_HK', 'zh_TW', 'en_US'],
            'SG' => ['en_SG', 'en_US', 'zh_CN'],

            // Japanese / Korean
            'JP' => ['ja_JP', 'en_US'],
            'KR' => ['ko_KR', 'en_US'],

            // Spanish
            'ES' => ['es_ES', 'en_US'],
            'MX' => ['es_MX', 'es_ES', 'en_US'],
            'AR' => ['es_AR', 'es_ES', 'en_US'],
            'CL' => ['es_CL', 'es_ES', 'en_US'],
            'CO' => ['es_CO', 'es_ES', 'en_US'],
            'PE' => ['es_PE', 'es_ES', 'en_US'],

            // Portuguese
            'BR' => ['pt_BR', 'pt_PT', 'en_US'],
            'PT' => ['pt_PT', 'pt_BR', 'en_US'],

            // French
            'FR' => ['fr_FR', 'en_US'],
            'BE' => ['fr_BE', 'nl_BE', 'en_US'],
            'CH' => ['de_CH', 'fr_CH', 'it_IT', 'en_US'],

            // German / Dutch / Italian
            'DE' => ['de_DE', 'en_US'],
            'AT' => ['de_AT', 'de_DE', 'en_US'],
            'NL' => ['nl_NL', 'en_US'],
            'IT' => ['it_IT', 'en_US'],

            // Russian / Ukrainian
            'RU' => ['ru_RU', 'en_US'],
            'UA' => ['uk_UA', 'ru_RU', 'en_US'],

            // Turkish
            'TR' => ['tr_TR', 'en_US'],

            // SEA (常见)
            'TH' => ['th_TH', 'en_US'],
            'VN' => ['vi_VN', 'en_US'],
            'ID' => ['id_ID', 'en_US'],
            'MY' => ['ms_MY', 'en_US', 'zh_CN'],
            'PH' => ['en_PH', 'en_US'],

            // Middle East / India
            'AE' => ['ar_AR', 'en_US'],
            'SA' => ['ar_AR', 'en_US'],
            'IN' => ['en_IN', 'hi_IN', 'en_US'],
        ];

        return $map[$cc] ?? ['en_US'];
    }
}

