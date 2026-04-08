<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class MetaAdCreationSchemaGuard
{
    /**
     * @return array<int, string>
     */
    public static function missingForTemplates(): array
    {
        return self::missingColumns('meta_ad_creation_templates', [
            'id', 'user_id', 'fb_ad_account_id', 'name', 'form_data', 'meta_counts', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function missingForDrafts(): array
    {
        return self::missingColumns('meta_ad_creation_drafts', [
            'id', 'user_id', 'fb_ad_account_id', 'tag', 'name', 'form_data', 'meta_counts', 'current_step', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function missingForRecords(): array
    {
        return self::missingColumns('meta_ad_creation_records', [
            'id', 'user_id', 'fb_ad_account_id', 'draft_id', 'template_id', 'region_group_id', 'creative_group_id',
            'ad_log_id', 'form_data_snapshot', 'fb_campaign_id', 'fb_adset_ids', 'fb_ad_ids', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function missingForMappings(): array
    {
        $m1 = self::missingColumns('meta_ad_creation_record_adsets', [
            'id', 'record_id', 'fb_campaign_id', 'fb_adset_id', 'targeting_package_index', 'targeting_package_name',
            'region_group_id', 'region_snapshot', 'creative_group_id', 'creative_binding_rule',
            'adset_name_snapshot', 'adset_status_snapshot', 'created_at', 'updated_at',
        ]);
        $m2 = self::missingColumns('meta_ad_creation_record_ads', [
            'id', 'record_id', 'record_adset_id', 'fb_ad_id', 'creative_slot_id',
            'material_id', 'post_id', 'creative_snapshot', 'created_at', 'updated_at',
        ]);
        return array_values(array_merge($m1, $m2));
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private static function missingColumns(string $table, array $columns): array
    {
        if (!Schema::hasTable($table)) {
            return ["table:$table"];
        }
        $missing = [];
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                $missing[] = "column:$table.$column";
            }
        }
        return $missing;
    }
}

