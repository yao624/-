<?php

namespace App\Jobs;

use App\Models\Copywriting;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbPage;
use App\Models\Link;
use App\Models\Material;
use App\Utils\FbUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FacebookCreateAd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $fbAccountID;
    private $fbAdAccountID;
    private $adsetID;
    private $item;
    private $timeout = 500;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAccountID, $fbAdAccountID, $adsetID, $item)
    {
        $this->fbAccountID = $fbAccountID;
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adsetID = $adsetID;
        $this->item = $item;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("create fb ad");

        $fbAdAccount = FbAdAccount::query()->findOrFail($this->fbAdAccountID);
        $fbAccount = FbAccount::query()->findOrFail($this->fbAccountID);
        $fbPage = FbPage::query()->findOrFail($this->item['page_id']);

        // 上传图片 获取 hash
        $materialId = $this->item['material_id'];
        $material = Material::query()->findOrFail($materialId);

        $file_abs_path = Storage::disk('public')->path($material->filepath);

        # TODO: 处理图片和视频
        $file_type = pathinfo($material->filename, PATHINFO_EXTENSION);

        $version = FbUtils::$API_Version;
        $link = Link::query()->findOrFail($this->item['link_id']);
        $copywriting = Copywriting::query()->findOrFail($this->item['copywriting_id']);

        if ($file_type == "mp4") {


            $page_id = $fbPage['source_id'];
            $page_token = $this->get_page_token($fbAccount, $page_id);
            if (!$page_token) {
                Log::warning("failed to get page token, page id: {$page_id}, account id: {$this->fbAccountID}");
                return;
            }

            $endpoint_upload_video = "https://graph-video.facebook.com/{$version}/{$page_id}/videos";
            $body_upload_video = [
                'file_path' => $file_abs_path,
                'file_name' => $material->filename,
                'page_token' => $page_token
            ];
            Log::info("-- uploading video: {$material->filename}");
            $resp_for_video_upload = FbUtils::makeRequest($fbAccount, $endpoint_upload_video, null, 'POST', $body_upload_video, 'upload_video');
            Log::info($resp_for_video_upload);
            if ($resp_for_video_upload->has('id')) {
                sleep(10);
                # 获取视频的 thumbnail, 类似 https://graph.facebook.com/v11.0/1086354212654329?fields=thumbnails
                $video_id = $resp_for_video_upload['id'];
                $endpoint_thumbnail = "https://graph.facebook.com/{$video_id}";
                $query = [
                    'fields' => 'thumbnails'
                ];
                Log::info('-- get thumbnails --');
                $resp_for_thumbnails = FbUtils::makeRequest($fbAccount, $endpoint_thumbnail, $query);
                Log::debug($resp_for_thumbnails);
                if ($resp_for_thumbnails->has('thumbnails')) {
                    $thumbnails_list = $resp_for_thumbnails->get('thumbnails')['data'];
                    $thumbnail_url = '';
                    foreach ($thumbnails_list as $thumb) {
                        $is_preferred = $thumb['is_preferred'];
                        if ($is_preferred) {
                            $thumbnail_url = $thumb['uri'];
                            break;
                        }
                    }
                    if ($thumbnail_url) {
                        $endpoint_adcreatives = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/adcreatives";
                        $payload = [
                            'name' => 'Video Creative',
                            'object_story_spec' => json_encode([
                                "page_id" => $fbPage->source_id,
                                "video_data" => [
                                    "video_id" => $video_id,
                                    "image_url" => $thumbnail_url,
                                    "call_to_action" => [
                                        'type' => 'LEARN_MORE',
                                        'value' => json_encode([
                                            'link' => $link->link
                                        ])
                                    ],
                                    'message' => $copywriting['primary_text'],
                                    'title' => $copywriting['headline'],
                                    'link_description' => $copywriting['description']
                                ]
                            ])
                        ];

                        Log::debug("creating ad creative");
                        Log::debug($payload);
                        $resp = FbUtils::makeRequest($fbAccount, $endpoint_adcreatives, null, 'POST', $payload, 'create_adcreatives');
                        Log::debug($resp);
                        if ($resp->has('id')) {
                            $creative_id = $resp['id'];
                            $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/ads";
                            $payload = [
                                'name' => $this->processName($this->item['ad_name_tpl']),
                                'adset_id' => $this->adsetID,
                                'creative' => json_encode([
                                    "creative_id" => $creative_id
                                ]),
                                'status' => 'PAUSED',
                            ];
                            Log::debug("creating ad");
                            Log::debug($payload);
                            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload, 'create_ad');
                            Log::debug($resp);
                        } else {
                            Log::warning("failed to create adcreative");
                            Log::debug($resp);
                        }
                    } else {
                        Log::warning("failed to get preferred thumbnail: {$material->filename}");
                    }
                } else {
                    Log::warning("failed to get video thumbnails: {$material->filename}");
                    Log::warning($resp_for_thumbnails);
                }

            } else {
                Log::warning("failed to upload video: {$material->filename}");
            }
        } else {

            $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/adimages";
            $query = null;
            $body = [
                'file_path' => $file_abs_path,
                'file_name' => $material->filename
            ];
            Log::debug("create adimage:");
            Log::debug($body);
            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $body, 'create_adimage');
            Log::debug($resp);
            if (collect($resp)->has('images')) {
                $hash = $resp['images'][$material->filename]['hash'];
                // 创建 AdCreative, 包含文案

                # creative: https://developers.facebook.com/docs/marketing-api/reference/ad-creative/
                $payload = [
                    'name' => 'Sample Creative',
                    'object_story_spec' => json_encode([
                        "page_id" => $fbPage->source_id,
                        "link_data" => [
                            "image_hash" => $hash,
                            "link" => $link->link,
                            "call_to_action" => [
                                'type' => 'LEARN_MORE'
                            ],
                            'message' => $copywriting['primary_text'],
                            'name' => $copywriting['headline'],
                            'description' => $copywriting['description']
                        ]
                    ])
                ];

                $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/adcreatives";
                Log::debug("creating ad creative");
                Log::debug($payload);
                $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload, 'create_adcreatives');
                Log::debug($resp);
                if (collect($resp)->has('id')) {
                    $creative_id = $resp['id'];
                    $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/ads";
                    $payload = [
                        'name' => $this->processName($this->item['ad_name_tpl']),
                        'adset_id' => $this->adsetID,
                        'creative' => json_encode([
                            "creative_id" => $creative_id
                        ]),
                        'status' => 'PAUSED',
                    ];
                    Log::debug("creating ad");
                    Log::debug($payload);
                    $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload, 'create_ad');
                    Log::debug($resp);
                } else {
                    Log::warning("failed to create adcreative");
                    Log::debug($resp);
                }
            }

        }


    }

    function processName($name) {
        // 替换 {{datetime}} 宏
        $name = str_replace('{{date}}', date('Y-m-d'), $name);

        // 替换 {{random}} 宏
        $randomString = substr(str_shuffle(md5(time())), 0, 4);
        $name = str_replace('{{random}}', $randomString, $name);

        return $name;
    }

    private function get_page_token($fbAccosunt, mixed $page_id)
    {
        $endpoint = "https://graph.facebook.com/{$page_id}";
        $query = [
            'fields' => 'access_token'
        ];
        $resp = FbUtils::makeRequest($fbAccosunt, $endpoint, $query);
        if ($resp->has('access_token')) {
            return $resp['access_token'];
        } else {
            return '';
        }
    }

    public function tags(): array
    {
        return [
            'FB-Create',
            'FB-Create-Adset',
            "{$this->fbAdAccountID}",
            "A-{$this->adsetID}"
        ];
    }
}
