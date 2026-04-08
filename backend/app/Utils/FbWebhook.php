<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

class FbWebhook
{
    /**
     * Campaign状态映射
     *
     * 将Facebook webhook中的status_name映射为系统中使用的状态值
     */
    public static function mapCampaignStatus(string $statusName): string
    {
        $statusMap = [
            // Campaign状态映射
            'CAMPAIGN_PAUSED' => 'PAUSED',
            'CAMPAIGN_ACTIVE' => 'ACTIVE',
            'CAMPAIGN_ARCHIVED' => 'ARCHIVED',
            'CAMPAIGN_DELETED' => 'DELETED',
            'CAMPAIGN_PENDING_REVIEW' => 'PENDING_REVIEW',
            'CAMPAIGN_DISAPPROVED' => 'DISAPPROVED',
            'CAMPAIGN_IN_REVIEW' => 'IN_REVIEW',
            'CAMPAIGN_PENDING_BILLING_INFO' => 'PENDING_BILLING_INFO',
            'CAMPAIGN_CAMPAIGN_GROUP_PAUSED' => 'CAMPAIGN_GROUP_PAUSED',
        ];

        $mappedStatus = $statusMap[$statusName] ?? null;

        if ($mappedStatus === null) {
            Log::warning('Unknown campaign status name in webhook', [
                'status_name' => $statusName,
                'available_mappings' => array_keys($statusMap)
            ]);

            // 返回原始状态名作为fallback
            return $statusName;
        }

        return $mappedStatus;
    }

    /**
     * Ad状态映射
     *
     * 将Facebook webhook中的status_name映射为系统中使用的状态值
     */
    public static function mapAdStatus(string $statusName): string
    {
        $statusMap = [
            // Ad状态映射
            'PENDING_REVIEW' => 'PENDING_REVIEW',
            'DISAPPROVED' => 'DISAPPROVED',
            'PREAPPROVED' => 'PREAPPROVED',
            'PENDING_BILLING_INFO' => 'PENDING_BILLING_INFO',
            'CAMPAIGN_PAUSED' => 'CAMPAIGN_PAUSED',
            'ADSET_PAUSED' => 'ADSET_PAUSED',
            'AD_PAUSED' => 'PAUSED',
            'ACTIVE' => 'ACTIVE',
            'ARCHIVED' => 'ARCHIVED',
            'DELETED' => 'DELETED',
            'IN_REVIEW' => 'IN_REVIEW',
            'WITH_ISSUES' => 'WITH_ISSUES',
        ];

        $mappedStatus = $statusMap[$statusName] ?? null;

        if ($mappedStatus === null) {
            Log::warning('Unknown ad status name in webhook', [
                'status_name' => $statusName,
                'available_mappings' => array_keys($statusMap)
            ]);

            // 返回原始状态名作为fallback
            return $statusName;
        }

        return $mappedStatus;
    }

    /**
     * 验证webhook数据结构
     */
    public static function validateWebhookStructure(array $data): array
    {
        $errors = [];

        // 检查必要的顶级字段
        if (!isset($data['object'])) {
            $errors[] = 'Missing required field: object';
        }

        if (!isset($data['entry'])) {
            $errors[] = 'Missing required field: entry';
        } elseif (!is_array($data['entry'])) {
            $errors[] = 'Field "entry" must be an array';
        }

        // 检查entry结构
        if (isset($data['entry']) && is_array($data['entry'])) {
            foreach ($data['entry'] as $index => $entry) {
                if (!is_array($entry)) {
                    $errors[] = "Entry at index {$index} must be an array";
                    continue;
                }

                if (!isset($entry['id'])) {
                    $errors[] = "Entry at index {$index} missing required field: id";
                }

                if (!isset($entry['changes'])) {
                    $errors[] = "Entry at index {$index} missing required field: changes";
                } elseif (!is_array($entry['changes'])) {
                    $errors[] = "Entry at index {$index} field 'changes' must be an array";
                }
            }
        }

        return $errors;
    }

    /**
     * 提取webhook中的所有状态变化
     */
    public static function extractStatusChanges(array $webhookData): array
    {
        $changes = [];

        if (!isset($webhookData['entry']) || !is_array($webhookData['entry'])) {
            return $changes;
        }

        foreach ($webhookData['entry'] as $entry) {
            if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                if (!isset($change['field']) || $change['field'] !== 'in_process_ad_objects') {
                    continue;
                }

                if (!isset($change['value']) || !is_array($change['value'])) {
                    continue;
                }

                $value = $change['value'];
                if (isset($value['id'], $value['level'], $value['status_name'])) {
                    $changes[] = [
                        'object_id' => $value['id'],
                        'level' => $value['level'],
                        'status_name' => $value['status_name'],
                        'entry_id' => $entry['id'] ?? null,
                        'timestamp' => $entry['time'] ?? null,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Adset状态映射
     *
     * 将Facebook webhook中的status_name映射为系统中使用的状态值
     */
    public static function mapAdsetStatus(string $statusName): string
    {
        $statusMap = [
            // Adset状态映射
            'ADSET_PAUSED' => 'PAUSED',
            'ADSET_ACTIVE' => 'ACTIVE',
            'ADSET_ARCHIVED' => 'ARCHIVED',
            'ADSET_DELETED' => 'DELETED',
            'PENDING_REVIEW' => 'PENDING_REVIEW',
            'DISAPPROVED' => 'DISAPPROVED',
            'IN_REVIEW' => 'IN_REVIEW',
            'PENDING_BILLING_INFO' => 'PENDING_BILLING_INFO',
            'CAMPAIGN_PAUSED' => 'CAMPAIGN_PAUSED',
            'WITH_ISSUES' => 'WITH_ISSUES',
        ];

        $mappedStatus = $statusMap[$statusName] ?? null;

        if ($mappedStatus === null) {
            Log::warning('Unknown adset status name in webhook', [
                'status_name' => $statusName,
                'available_mappings' => array_keys($statusMap)
            ]);

            // 返回原始状态名作为fallback
            return $statusName;
        }

        return $mappedStatus;
    }

    /**
     * 获取支持的对象级别
     */
    public static function getSupportedLevels(): array
    {
        return ['CAMPAIGN', 'AD', 'AD_SET'];
    }

    /**
     * 检查是否是支持的对象级别
     */
    public static function isSupportedLevel(string $level): bool
    {
        return in_array(strtoupper($level), self::getSupportedLevels());
    }

    /**
     * 格式化webhook数据用于日志记录
     */
    public static function formatForLogging(array $webhookData, int $maxDepth = 3): string
    {
        try {
            return json_encode($webhookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return 'Failed to format webhook data: ' . $e->getMessage();
        }
    }

    /**
     * 生成webhook处理摘要
     */
    public static function generateProcessingSummary(array $webhookData): array
    {
        $changes = self::extractStatusChanges($webhookData);

        $summary = [
            'total_entries' => isset($webhookData['entry']) ? count($webhookData['entry']) : 0,
            'total_changes' => count($changes),
            'object_type' => $webhookData['object'] ?? 'unknown',
            'changes_by_level' => [],
            'changes_by_status' => [],
        ];

        foreach ($changes as $change) {
            // 按级别统计
            $level = strtoupper($change['level']);
            if (!isset($summary['changes_by_level'][$level])) {
                $summary['changes_by_level'][$level] = 0;
            }
            $summary['changes_by_level'][$level]++;

            // 按状态统计
            $status = $change['status_name'];
            if (!isset($summary['changes_by_status'][$status])) {
                $summary['changes_by_status'][$status] = 0;
            }
            $summary['changes_by_status'][$status]++;
        }

        return $summary;
    }
}