<?php

namespace App\Http\Controllers;

use App\Models\MetaAutoRuleTemplate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleTemplatesController extends BaseController
{
    use ApiResponse;

    /**
     * 获取规则模板列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $monitoringObject = (string)$request->input('monitoringObject', 'ad-group');

        $templates = MetaAutoRuleTemplate::whereJsonContains('monitoring_object', $monitoringObject)
            ->orderByRaw("CASE WHEN id = 'direct' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        return $this->success($templates);
    }

    


//    public function index(Request $request)
//    {
//        $monitoringObject = (string)$request->query('monitoringObject', 'ad-group');
//
//        $templates = [
//            'notification' => [
//                'id' => 'notification',
//                'title' => '通知提醒',
//                'description' => '当广告投放效果不好时,发送邮件通知',
//                'icon' => 'BellOutlined',
//                'color' => '#722ed1',
//            ],
//            'loss-prevention' => [
//                'id' => 'loss-prevention',
//                'title' => '广告止损',
//                'description' => '自动关停投放效果不好的广告',
//                'icon' => 'StopOutlined',
//                'color' => '#52c41a',
//            ],
//            'anti-fraud' => [
//                'id' => 'anti-fraud',
//                'title' => '广告防盗刷',
//                'description' => '自动关闭盗刷广告',
//                'icon' => 'SafetyOutlined',
//                'color' => '#52c41a',
//            ],
//            'restart' => [
//                'id' => 'restart',
//                'title' => '重启广告',
//                'description' => '重新开启投放效果达标的广告',
//                'icon' => 'ReloadOutlined',
//                'color' => '#fa8c16',
//            ],
//            'increase-bid' => [
//                'id' => 'increase-bid',
//                'title' => '提价扩量',
//                'description' => '当广告投放效果较好时,提高出价争取更多转化',
//                'icon' => 'RiseOutlined',
//                'color' => '#1890ff',
//            ],
//            'decrease-bid' => [
//                'id' => 'decrease-bid',
//                'title' => '降价控成本',
//                'description' => '当广告投放效果不好时,降低出价控制转化成本',
//                'icon' => 'FallOutlined',
//                'color' => '#ff4d4f',
//            ],
//            'increase-budget' => [
//                'id' => 'increase-budget',
//                'title' => '增加预算',
//                'description' => '当花费达到预算值并且广告效果较好时,增加预算',
//                'icon' => 'FileAddOutlined',
//                'color' => '#13c2c2',
//            ],
//            'decrease-budget' => [
//                'id' => 'decrease-budget',
//                'title' => '降低预算',
//                'description' => '当花费较大但广告效果不好时,减低预算',
//                'icon' => 'MinusOutlined',
//                'color' => '#fa8c16',
//            ],
//            'auto-tag' => [
//                'id' => 'auto-tag',
//                'title' => '自动打标签',
//                'description' => '根据目标对象的投放数据效果或属性特征,自动为其打上合适的标签',
//                'icon' => 'TagOutlined',
//                'color' => '#2f54eb',
//            ],
//            'account-expired-notification' => [
//                'id' => 'account-expired-notification',
//                'title' => '账户失效通知',
//                'description' => '当广告账户失效时,发送邮件通知',
//                'icon' => 'BellOutlined',
//                'color' => '#fa8c16',
//            ],
//        ];
//
//        $direct = [
//            'id' => 'direct',
//            'title' => '直接创建',
//            'description' => '',
//            'icon' => 'PlusOutlined',
//            'color' => '#1890ff',
//        ];
//
//        $includeDirectByObject = [
//            'ad-account' => true,
//            'campaign' => true,
//            'ad-group' => true,
//            'ad' => true,
//            'ad-material' => true,
//            'material' => true,
//        ];
//
//        $idsByObject = [
//            'ad-account' => [
//                'account-expired-notification',
//                'notification',
//                'auto-tag',
//            ],
//            'campaign' => [
//                'notification',
//                'loss-prevention',
//                'anti-fraud',
//                'restart',
//                'increase-budget',
//                'decrease-budget',
//                'auto-tag',
//            ],
//            'ad-group' => [
//                'notification',
//                'loss-prevention',
//                'anti-fraud',
//                'restart',
//                'increase-bid',
//                'decrease-bid',
//                'increase-budget',
//                'decrease-budget',
//                'auto-tag',
//            ],
//            'ad' => [
//                'notification',
//                'loss-prevention',
//                'anti-fraud',
//                'restart',
//                'auto-tag',
//            ],
//            'ad-material' => [
//                'notification',
//            ],
//            'material' => [
//                'notification',
//                'auto-tag',
//            ],
//        ];
//
//        $ids = $idsByObject[$monitoringObject] ?? $idsByObject['ad-group'];
//        $includeDirect = $includeDirectByObject[$monitoringObject] ?? true;
//
//        $result = $includeDirect ? [$direct] : [];
//        foreach ($ids as $id) {
//            if (isset($templates[$id])) {
//                $result[] = $templates[$id];
//            }
//        }
//
//        return response()->json($result);
//    }
}
