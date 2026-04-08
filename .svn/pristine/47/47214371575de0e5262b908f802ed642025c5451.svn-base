<?php

namespace App\Jobs\FBComponents;

use App\Enums\ConversionLocationType;
use App\Enums\ObjectiveType;
use App\Models\FbCatalogProductSet;
use App\Models\FbPage;
use App\Models\FbPixel;
use App\Models\Material;
use App\Utils\FbUtils;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdCreative
{
    public function handle($context, Closure $next)
    {
        // 获取素材 id, 上传素材，返回
        $fbAdAccountSourceId = $context['fbAdAccountSourceId'];
        $fbAccount = $context['fbAccount'];
        $apiToken = $context['apiToken'];
        $operatorType = $context['operatorType'];
        $operatorId = $context['operatorId'];

        $creativeType = $context['creativeType'] ?? 'post';
        if ($creativeType === 'image') {
            $creativeHash = $context['creativeHash'] ?? '';
        } elseif ($creativeType === 'video') {
            $videoId = $context['videoId'];
            $thumbnail_url = $context['thumbnail_url'];
        }

        $pageId = $context['fbPageSourceId'];
        $postId = $context['postId'] ?? '';
        $productSetId = $context['productSetId'] ?? '';
        $requirePbiaId = $context['requirePbiaId'] ?? false;
        $link = $context['link'];
        $primaryText = $context['primary_text'];
        $description = $context['description'];
        $headline = $context['headline'];

        $objective = $context['objective'];
        $conversionLocation = $context['conversion_location'];
        $cta = $context['cta'];

        $urlTags = $context['urlTags'];
        $pixelId = $context['pixel_id'];

        // 替换 url prarameters 中的 pixel id
        if ($pixelId) {
            $pixelObj = FbPixel::query()->where('id', $pixelId)->firstOrFail();
            if ($pixelObj) {
                $pixel = $pixelObj['pixel'];
                $urlTags = str_replace('{{pixel}}', $pixel, $urlTags);
            }
        }

        // 通过原帖创建广告
        if ($postId) {
            $payload = [
                'name' => 'my creative',
                'object_story_id' => "{$pageId}_{$postId}",
                'url_tags' => $urlTags,
            ];
        } elseif ($productSetId) {
            $object_story_spec = [
                'page_id' => $pageId,
                'template_data' => [
                    'link' => $link,
                    'name' => '{{product.name}}',
                    'message' => '{{product.description}}',
                    'call_to_action' => [
                        'type' => $cta
                    ],
                    'format_option' => "single_video",
                    'multi_share_end_card' => false,
                    'show_multiple_images' => false,
                ]
            ];
            if ($requirePbiaId) {
                Log::debug("require pbia id");
                $page = FbPage::query()->where('source_id', $pageId)->firstOrFail();
                $tokens = collect($page->tokens);
                $operator_page_token = $tokens->where('owner_id', $operatorId)->pluck('token')->first();
                if (!$operator_page_token) {
                    throw new \Exception('catalog ad have ig placement, but operator do not have page token');
                }
//                Log::debug("owner id: {$operatorId}, page token: {$operator_page_token}");
                $pbiaId = $this->get_or_create_pbia_id($operator_page_token, $pageId);
                if (!$pbiaId) {
                    throw new \Exception('can not get pbia id');
                }
                $object_story_spec['instagram_user_id'] = $pbiaId;
            }
            $productSet = FbCatalogProductSet::query()->where('id', $productSetId)->firstOrFail();
            $source_id = $productSet->source_id;
            $payload = [
                'product_set_id' => $source_id,
                'object_story_spec' => json_encode($object_story_spec),
                'url_tags' => $urlTags,
//                'applink_treatment' => 'web_only',
                'template_url_spec' => json_encode([
                    'web' => [
                        'url' => '{{product.url}}'
                    ]
                ])
            ];
        } else {
            $story = [
                'page_id' => $pageId,
            ];

            if ($creativeType === 'image') {
                $story['link_data'] = [
                    'link' => $link,
                    'message' => $primaryText,
                    'name' => $headline,
                    'description' => $description,
                    "call_to_action" => [
                        'type' => $cta
                    ],
                    'image_hash' => $creativeHash
                ];
            } elseif ($creativeType === 'video') {
                $story['video_data'] = [
                    "video_id" => $videoId,
                    "image_url" => $thumbnail_url,
                    "call_to_action" => [
                        'type' => $cta,
                        'value' => json_encode([
                            'link' => $link
                        ])
                    ],
                    'message' => $primaryText,
                    'title' => $headline,
                    'link_description' => $description
                ];
            }

            // lead 表单广告的 link 只能是这个链接
            if ($objective === ObjectiveType::Leads->value) {
                if ($conversionLocation === ConversionLocationType::InstantForms->value) {
                    $link = 'https://fb.me/';
                    $story['link_data']['link'] = $link;
                    $story['link_data']['call_to_action']['value'] = [
                        'lead_gen_form_id' => $context['formSourceId']
                    ];
                }
            }

            $payload = [
                'name' => 'Creative',
                'object_story_spec' => json_encode($story),
                'url_tags' => $urlTags,
//                'degrees_of_freedom_spec' => json_encode( [
//                    'creative_features_spec' => [
//                        'standard_enhancements' => [
//                            'enroll_status' => 'OPT_IN'
//                        ]
//                    ]
//                ])
            ];
        }

        $version = FbUtils::$API_Version;
//        $version = 'v21.0';
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccountSourceId}/adcreatives";
        Log::debug("creating ad creative");
        Log::debug($payload);
        $resp = FbUtils::makeRequest($fbAccount, $endpoint, null, 'POST', $payload, 'create_adcreatives', $apiToken);
        Log::debug($resp);

        // 获取 creative id, 上传素材，返回
        if ($resp['success']) {
            $context['creativeId'] = $resp['id'];
        } else {
            throw new \Exception('Failed to create creative');
        }

        return $next($context);
    }

    private function get_or_create_pbia_id(string $page_token, $page_source_id)
    {
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$page_source_id}/page_backed_instagram_accounts";
        Log::debug("get pbia id");
        $resp = FbUtils::makeRequest(null, $endpoint, null, 'GET', null, 'pbia', $page_token);
        if ($resp['success']) {
            $data = collect($resp['data']?? []);
            if ($data->isNotEmpty()) {
                Log::debug("get pbia id");
                $firstItem = $data->first();
                $pbia_id = data_get($firstItem, 'id');
                return $pbia_id;
            } else {
                // 没有 pbia id, 需要创建
                Log::debug('no pbia id, will create it');
                $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', null, '', $page_token);
                if ($resp['success']) {
                    Log::debug('create pbia id ok');
                    $created_pbia_id = data_get($resp, 'id');
                    return $created_pbia_id;
                }
            }
        }
        return null;
    }
}
