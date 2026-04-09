<?php

namespace App\Http\Resources;

use App\Services\Locale\CopywritingTranslationResolver;
use App\Services\Locale\CountryToMetaLocales;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopywritingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $preferredLocales = $this->parsePreferredLocales($request);
        $resolved = CopywritingTranslationResolver::resolveTexts(
            $this->translations,
            $preferredLocales,
            [
                'primary_text' => $this->primary_text,
                'headline' => $this->headline,
                'description' => $this->description,
            ],
        );
        $includeTranslations = filter_var($request->input('with_translations', false), FILTER_VALIDATE_BOOLEAN);

        $row = [
            'id' => $this->id,
            'primary_text' => $resolved['primary_text'],
            'headline' => $resolved['headline'],
            'description' => $resolved['description'],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_owner' => $this->user_id === $user->id,
            'notes' => $this->notes,
            'tags' => TagResource::collection($this->tags),
        ];

        if ($preferredLocales !== []) {
            $row['resolved_locale'] = $resolved['resolved_locale'];
            $row['requested_locales'] = $preferredLocales;
        }
        if ($includeTranslations) {
            $row['translations'] = $this->translations ?? [];
        }

        return $row;
    }

    /**
     * @return list<string>
     */
    private function parsePreferredLocales(Request $request): array
    {
        if ($request->filled('resolve_locales')) {
            $raw = preg_split('/[\s,]+/', (string) $request->input('resolve_locales'), -1, PREG_SPLIT_NO_EMPTY);

            return array_values(array_unique(array_map(static fn (string $s) => trim($s), $raw)));
        }

        if ($request->filled('country_codes')) {
            return CountryToMetaLocales::expand((string) $request->input('country_codes'));
        }

        return [];
    }
}
