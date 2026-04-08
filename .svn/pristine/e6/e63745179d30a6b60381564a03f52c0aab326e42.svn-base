<?php

namespace App\Jobs\FBComponents;

use App\Models\Material;
use App\Utils\FbUtils;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadAdMaterial
{
    public function handle($context, Closure $next)
    {
        Log::debug("create ad image job");

        if ($context['postId']) {
            Log::debug("no need upload creative");
            return $next($context);
        }
        if ($context['productSetId']) {
            Log::debug("no need upload creative for catalog ads");
            return $next($context);
        }

        $materialId = $context['materialId'];
        if (!$context['postId']) {
            $material = Material::query()->findOrFail($materialId);
        } else {
            $material = null;
        }

        $fbAdAccountSourceId = $context['fbAdAccountSourceId'];
        $fbAccount = $context['fbAccount'];
        $apiToken = $context['apiToken'];

        $file_abs_path = Storage::disk('public')->path($material->filepath);

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccountSourceId}/adimages";
        $query = null;
        $body = [
            'file_path' => $file_abs_path,
            'file_name' => $material->filename
        ];

        $file_type = pathinfo($material->filename, PATHINFO_EXTENSION);

        if ($file_type != 'mp4') {
            Log::debug("create adimage:");
            Log::debug($body);
            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $body, 'create_adimage', $apiToken);
            // 获取素材 id, 上传素材，返回
            if ($resp['success']) {
                $context['creativeHash'] = $resp['images'][$material->filename]['hash'];
                $context['creativeType'] = 'image';
            } else {
                throw new \Exception('Failed to upload image');
            }
        } else {
            Log::debug("create advideo:");
            Log::debug($body);
            $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccountSourceId}/advideos";

            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $body, 'create_adimage', $apiToken);
            // 获取素材 id, 上传素材，返回
            if ($resp['success']) {
                $video_id = $resp['id'];
                $thumbnail_url = '';
                $retry_time = 6;
                $current = 1;
                $delay = 10;

                while ($current <= $retry_time) {
                    sleep($delay);
                    $endpoint = "https://graph.facebook.com/{$video_id}";
                    $query = [
                        'fields' => 'thumbnails'
                    ];
                    $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', null, null, $apiToken);
                    if ($resp['success']) {
                        if ($resp['thumbnails']) {
                            $dataList = collect($resp['thumbnails']['data']);
                            foreach ($dataList as $item) {
                                $is_preferred = $item['is_preferred'];
                                if ($is_preferred) {
                                    $thumbnail_url = $item['uri'];
                                    $current = $retry_time;
                                    break;
                                }
                            }
                        }
                    }
                    $current = $current+1;
                }

                $context['thumbnail_url'] = $thumbnail_url;
                $context['creativeType'] = 'video';
                $context['videoId'] = $resp['id'];

            } else {
                throw new \Exception('Failed to upload video');
            }
        }



        return $next($context);
    }
}
