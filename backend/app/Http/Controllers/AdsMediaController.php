<?php

namespace App\Http\Controllers;

use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbPage;
use App\Utils\FbUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AdsMediaController extends Controller
{
    /**
     * 获取广告媒体文件URL
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMediaUrl(Request $request)
    {
        // 验证请求参数
        $request->validate([
            'hash' => 'required|string',
            'id' => 'required|string',
            'type' => 'required|in:video,image',
            'page' => 'required_if:type,video|string' // 当type为video时page参数必需
        ]);

        $hash = $request->input('hash');
        $sourceId = $request->input('id');
        $type = $request->input('type');
        $pageSourceId = $request->input('page');

        try {
            // 根据source_id查询FbAdAccount
            $fbAdAccount = FbAdAccount::where('source_id', $sourceId)->first();

            if (!$fbAdAccount) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到对应的广告账户'
                ], 404);
            }

            // 根据类型构建不同的API请求
            if ($type === 'image') {
                // 查询对应的FbApiToken (token_type = 1)
                $fbApiToken = $fbAdAccount->apiTokens()
                    ->where('token_type', 1)
                    ->where('active', true)
                    ->first();

                if (!$fbApiToken) {
                    return response()->json([
                        'success' => false,
                        'message' => '未找到有效的API Token'
                    ], 404);
                }

                $url = $this->getImageUrl($hash, $fbAdAccount->source_id, $fbApiToken->token);
            } else {
                // 对于视频，需要获取page_token
                $pageToken = $this->getPageToken($pageSourceId);

                if (!$pageToken) {
                    return response()->json([
                        'success' => false,
                        'message' => '未找到有效的Page Token'
                    ], 404);
                }

                $url = $this->getVideoUrl($hash, $pageToken);
            }

            if ($url) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'url' => $url,
                        'hash' => $hash,
                        'type' => $type
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '未找到对应的媒体文件'
                ], 404);
            }

        } catch (Exception $e) {
            Log::error('AdsMedia getMediaUrl error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '获取媒体文件失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取Page Token
     *
     * @param string $pageSourceId
     * @return string|null
     */
    private function getPageToken($pageSourceId)
    {
        try {
            // 根据source_id查询FbPage
            $fbPage = FbPage::where('source_id', $pageSourceId)->first();

            if (!$fbPage || !$fbPage->tokens) {
                Log::warning("未找到FbPage或tokens为空: {$pageSourceId}");
                return null;
            }

            // 找出所有owner_type为"bm"的token
            $bmTokens = [];
            foreach ($fbPage->tokens as $tokenData) {
                if (isset($tokenData['owner_type']) && $tokenData['owner_type'] === 'bm') {
                    $bmTokens[] = $tokenData;
                }
            }

            if (empty($bmTokens)) {
                Log::warning("未找到owner_type为bm的token: {$pageSourceId}");
                return null;
            }

            // 依次检查每个bm token对应的FbApiToken是否有效
            foreach ($bmTokens as $bmToken) {
                if (!isset($bmToken['owner_id'])) {
                    continue; // 跳过没有owner_id的token
                }

                // 根据owner_id查询FbApiToken
                $fbApiToken = FbApiToken::where('id', $bmToken['owner_id'])
                    ->where('token_type', 1)
                    ->where('active', true)
                    ->first();

                if ($fbApiToken) {
                    // 找到有效的FbApiToken，返回对应的token
                    Log::info("找到有效的FbApiToken: {$bmToken['owner_id']} for page: {$pageSourceId}");
                    return $bmToken['token'];
                }
            }

            Log::warning("所有owner_type为bm的token都无效: {$pageSourceId}");
            return null;

        } catch (Exception $e) {
            Log::error('AdsMedia getPageToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取图片URL
     *
     * @param string $hash
     * @param string $source_id
     * @param string $token
     * @return string|null
     */
    private function getImageUrl($hash, $source_id, $token)
    {
        try {
            // 构建Facebook Ad Image API的URL - 使用系统统一的API版本
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/act_" . $source_id . "/adimages";

            $query = [
                'fields' => 'url',
                'access_token' => $token,
                'hashes' => [$hash]
            ];

            // 使用FbUtils发送请求
            $response = FbUtils::makeRequest(null, $endpoint, $query, 'GET', null, '', $token);

            if ($response['success'] && isset($response['data'][0]['url'])) {
                return $response['data'][0]['url'];
            }

            return null;

        } catch (Exception $e) {
            Log::error('AdsMedia getImageUrl error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取视频URL
     *
     * @param string $hash
     * @param string $pageToken
     * @return string|null
     */
    private function getVideoUrl($hash, $pageToken)
    {
        try {
            // 构建Facebook Video API的URL
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/{$hash}";

            $query = [
                'fields' => 'source',
                'access_token' => $pageToken
            ];

            // 使用FbUtils发送请求
            $response = FbUtils::makeRequest(null, $endpoint, $query, 'GET', null, '', $pageToken);

            if ($response['success'] && isset($response['source'])) {
                return $response['source'];
            }

            return null;

        } catch (Exception $e) {
            Log::error('AdsMedia getVideoUrl error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取FB广告视频的缩略图
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVideoThumbnail(Request $request)
    {
        // 验证请求参数
        $request->validate([
            'ad_account' => 'required|string',
            'video_id' => 'required|string'
        ]);

        $adAccountSourceId = $request->input('ad_account');
        $videoId = $request->input('video_id');

        try {
            // 根据source_id查询FbAdAccount
            $fbAdAccount = FbAdAccount::where('source_id', $adAccountSourceId)->first();

            if (!$fbAdAccount) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到对应的广告账户'
                ], 404);
            }

            // 查询对应的FbApiToken (token_type = 1, active = true)
            $fbApiToken = $fbAdAccount->apiTokens()
                ->where('token_type', 1)
                ->where('active', true)
                ->first();

            if (!$fbApiToken) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到有效的API Token'
                ], 404);
            }

            // 构建Facebook Video Thumbnail API的URL
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/{$videoId}/thumbnails";

            $query = [
                'access_token' => $fbApiToken->token
            ];

            // 使用FbUtils发送请求
            $response = FbUtils::makeRequest(null, $endpoint, $query, 'GET', null, '', $fbApiToken->token);

            if ($response['success'] && isset($response['data']) && !empty($response['data'])) {
                // 返回所有缩略图，将uri字段改为url
                $thumbnails = [];
                foreach ($response['data'] as $thumbnail) {
                    $thumbnails[] = [
                        'url' => $thumbnail['uri'], // 将uri改为url
                        'is_preferred' => $thumbnail['is_preferred'],
                        'height' => $thumbnail['height'] ?? null,
                        'width' => $thumbnail['width'] ?? null,
                        'scale' => $thumbnail['scale'] ?? null,
                        'id' => $thumbnail['id'] ?? null
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $thumbnails
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '未找到视频缩略图'
                ], 404);
            }

        } catch (Exception $e) {
            Log::error('AdsMedia getVideoThumbnail error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '获取视频缩略图失败: ' . $e->getMessage()
            ], 500);
        }
    }
}