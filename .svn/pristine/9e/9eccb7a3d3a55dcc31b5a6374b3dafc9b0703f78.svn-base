<?php

namespace App\Services\Locale;

/**
 * 按优先 locale 列表从 copywritings.translations 中解析正文/标题/描述。
 * 顶层 primary_text/headline/description 为默认回退文案。
 */
final class CopywritingTranslationResolver
{
    /**
     * @param  array<string, mixed>|null  $translations
     * @param  list<string>  $preferredLocales
     * @param  array{primary_text: ?string, headline: ?string, description: ?string}  $base
     * @return array{primary_text: ?string, headline: ?string, description: ?string, resolved_locale: ?string}
     */
    public static function resolveTexts(?array $translations, array $preferredLocales, array $base): array
    {
        $translations = is_array($translations) ? $translations : [];

        foreach ($preferredLocales as $loc) {
            $block = self::findBlock($translations, (string) $loc);
            if ($block === null) {
                continue;
            }
            $pt = $block['primary_text'] ?? null;
            $hl = $block['headline'] ?? null;
            $dc = $block['description'] ?? null;
            $has = (is_string($pt) && trim($pt) !== '')
                || (is_string($hl) && trim($hl) !== '')
                || (is_string($dc) && trim($dc) !== '');
            if (! $has) {
                continue;
            }

            return [
                'primary_text' => (is_string($pt) && trim($pt) !== '') ? $pt : $base['primary_text'],
                'headline' => (is_string($hl) && trim($hl) !== '') ? $hl : $base['headline'],
                'description' => (is_string($dc) && trim($dc) !== '') ? $dc : $base['description'],
                'resolved_locale' => (string) $loc,
            ];
        }

        return [
            'primary_text' => $base['primary_text'],
            'headline' => $base['headline'],
            'description' => $base['description'],
            'resolved_locale' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>|null
     */
    private static function findBlock(array $translations, string $locale): ?array
    {
        if (isset($translations[$locale]) && is_array($translations[$locale])) {
            return $translations[$locale];
        }
        $short = explode('_', $locale)[0] ?? '';
        if ($short !== '' && isset($translations[$short]) && is_array($translations[$short])) {
            return $translations[$short];
        }

        return null;
    }

    /**
     * @param  mixed  $raw
     * @return array<string, array{primary_text?: string|null, headline?: string|null, description?: string|null}>
     */
    public static function normalizeTranslationsInput(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $locale => $block) {
            if (! is_string($locale) || trim($locale) === '' || ! is_array($block)) {
                continue;
            }
            $loc = trim($locale);
            $out[$loc] = [
                'primary_text' => isset($block['primary_text']) ? (is_string($block['primary_text']) ? trim($block['primary_text']) : null) : null,
                'headline' => isset($block['headline']) ? (is_string($block['headline']) ? trim($block['headline']) : null) : null,
                'description' => isset($block['description']) ? (is_string($block['description']) ? trim($block['description']) : null) : null,
            ];
        }

        return $out;
    }
}
