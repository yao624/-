<?php

namespace App\Utils;

use App\Enums\EnumCatalogRole;
use App\Enums\EnumCatalogTasks;
use DateTimeZone;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FbUtils
{
    public static $API_Version = 'v23.0';
    public static $FbAccountStatusMap = [
        1 => 'ACTIVE',
        2 => 'DISABLED',
        3 => 'UNSETTLED',
        7 => 'PENDING_RISK_REVIEW',
        8 => 'PENDING_SETTLEMENT',
        9 => 'IN_GRACE_PERIOD',
        100 => 'PENDING_CLOSURE',
        101 => 'CLOSED',
        201 => 'ANY_ACTIVE',
        202 => 'ANY_CLOSED',
    ];

    public static $FbAdAccountDisableReasonMap = [
        0 => 'NONE',
        1 => 'ADS_INTEGRITY_POLICY',
        2 => 'ADS_IP_REVIEW',
        3 => 'RISK_PAYMENT',
        4 => 'GRAY_ACCOUNT_SHUT_DOWN',
        5 => 'ADS_AFC_REVIEW',
        6 => 'BUSINESS_INTEGRITY_RAR',
        7 => 'PERMANENT_CLOSE',
        8 => 'UNUSED_RESELLER_ACCOUNT',
        9 => 'UNUSED_ACCOUNT',
        10 => 'UMBRELLA_AD_ACCOUNT',
        11 => 'BUSINESS_MANAGER_INTEGRITY_POLICY',
        12 => 'MISREPRESENTED_AD_ACCOUNT',
        13 => 'AOAB_DESHARE_LEGAL_ENTITY',
        14 => 'CTX_THREAD_REVIEW',
        15 => 'COMPROMISED_AD_ACCOUNT',
    ];

    public static function makeRequest($fbAccount, $endpoint, $query, $method='GET', $body=null, $func='', $api_token = null, $dry_run=false)
    {
        // 首先检查 token 是否正常，不正常的话，就返回
        // 如果 token 正常，就调用 /me 接口
        Log::debug("FBUtils endpoint: {$endpoint}");

        // api_token 为空，表示普通的获取方式
        if ($api_token == null) {
            if (!$fbAccount->token_valid) {
                Log::debug("account token is not valid");
                return collect([
                    'success' => false
                ]);
            }
        }

        $accessToken = '';
        if ($api_token) {
            $accessToken = $api_token;
        } else {
            $accessToken = $fbAccount->token;
        }

        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';
        if ($fbAccount) {
            $user_agent = $fbAccount->useragent;
        }

        $headers = [
            'User-Agent' => $user_agent,
            'Referer' => 'https://adsmanager.facebook.com',
            'Origin' => 'https://adsmanager.facebook.com/'
        ];

        $queryParams = [];
        if (!$query) {
            // 如果 fields 为空，并且 GET 请求，则从 url 中获取查询参数。这是 paging 的 next url
            if ($method == 'GET') {
                $parsedUrl = parse_url($endpoint);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    Log::info("paging next request");
                }

//                Log::info($queryParams);
            }
        } else {
            $queryParams= $query;
            if ($api_token) {
                $queryParams['access_token'] = $api_token;
            } else {
                $queryParams['access_token'] = $fbAccount->token;
            }
        }

        if ($api_token) {
//            $proxyStr = 'socks5://user35:kvwUekvAOLSd@198.7.60.238:10134';
            $proxyStr = null;
        } else {
            $proxy = $fbAccount->proxy;
            $proxyStr = "{$proxy->protocol}://{$proxy->username}:{$proxy->password}@{$proxy->host}:{$proxy->port}";
        }

        if ($fbAccount) {
            $proxy = $fbAccount->proxy;
            $proxyStr = "{$proxy->protocol}://{$proxy->username}:{$proxy->password}@{$proxy->host}:{$proxy->port}";

            $cookiesString = $fbAccount->cookies;
            // 解码 JSON 字符串为数组
            $cookiesArray = json_decode($cookiesString, true);
            // 检查解码是否成功
            if (json_last_error() !== JSON_ERROR_NONE) {
                // 处理 JSON 解码错误
                Log::error("json error: " . json_last_error());
                Log::error("json error msg: " . json_last_error_msg());
                Log::debug($cookiesString);
                throw new \Exception('Invalid JSON format for cookies.');
            }

            // 准备 cookies 用于 HTTP 请求
            $cookiesForRequest = [];
            foreach ($cookiesArray as $cookie) {
                $cookiesForRequest[$cookie['name']] = $cookie['value'];
            }
        }

        if ($dry_run) {
            return [
                'success' => true
            ];
        }

        $baseHttp = Http::withHeaders($headers)->timeout(300)->connectTimeout(300);
        if ($fbAccount) {
            $httpClient = $baseHttp->withCookies($cookiesForRequest, $cookie['domain'])
                ->withHeaders($headers)
                ->withOptions([
                'proxy' => $proxyStr,
                'debug' => false
            ]);
        } else {
            $httpClient = $baseHttp->withOptions([
                'proxy' => $proxyStr,
                'debug' => false
            ]);
        }

//        Log::debug('query:');
//        Log::debug($queryParams);
        try {
            // 使用 HTTP 客户端发起请求并附带 cookies
            if ($method == 'GET') {
//                if ($func === 'get-page-post') {
//                    if ($fbAccount) {
//                        $queryParams['access_token'] = $api_token;
//                    }
//                }
                if (in_array($func, ['get-page-post', 'fetch-page-forms', 'pbia'])) {
                    $queryParams['access_token'] = $api_token;
                }
                $response = $httpClient->get($endpoint, $queryParams);
            } elseif ($method == 'POST') {
                Log::debug("FBUtils post body");
                Log::debug($body);
                if ($func == '') {
//                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    if ($api_token) {
                        $body['access_token'] = $api_token;
                    } else {
                        $body['access_token'] = $fbAccount->token;
                    }
                    $response = $httpClient->withHeaders([
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post($endpoint, $body);
                } elseif ($func == 'create_adimage') {
                    $file_path = $body['file_path'];
                    $file_name = $body['file_name'];

                    // 打开文件作为资源
                    $fileStream = fopen($file_path, 'r');

                    if ($fileStream === false) {
                        // 处理文件无法打开的错误
                        throw new \Exception("Could not open file: " . $file_path);
                    }

                    try {
                        $response = $httpClient->attach(
                            'filename', $fileStream, $file_name
                        )->post($endpoint, [
                            'access_token' => $accessToken,
                        ]);
                    } finally {
                        // 确保文件流被关闭，释放资源
                        fclose($fileStream);
                    }

//                    $response = $httpClient->attach(
//                        'filename', file_get_contents($file_path), $file_name
//                    )->post($endpoint, [
//                        'access_token' => $accessToken,
//                    ]);
                } elseif ($func == 'upload_video') {
                    $file_path = $body['file_path'];
                    $file_name = $body['file_name'];
                    // 支持两种模式：page_token（上传到page）或 accessToken（上传到ad account media library）
                    $token = isset($body['page_token']) ? $body['page_token'] : $accessToken;
                    $response = $httpClient->attach(
                        'source', file_get_contents($file_path), $file_name
                    )->timeout(3600)->connectTimeout(3600)->post($endpoint, [
                        'access_token' => $token,
                    ]);
                } elseif (in_array($func, ['create_adcreatives', 'create_ad'])) {
                    $body['access_token'] = $accessToken;
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    $response = $httpClient->withHeaders([
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post($endpoint, $body);
                } elseif (in_array($func, ['update_adset_status', 'update_ad_item_status', 'copy_fb_items'])) {
                    if ($api_token) {
                        $body['access_token'] = $api_token;
                    } else {
                        $body['access_token'] = $fbAccount->token;
                    }
                    $response = $httpClient->asForm()->post($endpoint, $body);
                }
            } elseif ($method == 'DELETE') {
                Log::debug("FBUtils delete requ body");
                Log::debug($body);
                if ($func == '') {
//                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    if ($api_token) {
                        $body['access_token'] = $api_token;
                    } else {
                        $body['access_token'] = $fbAccount->token;
                    }
                    $response = $httpClient->withHeaders([
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->delete($endpoint, $body);
                }
            }

            $statusCode = $response->status();
            Log::info("status code: {$statusCode}");
            $on_debug = Cache::get('fb_debug');
            if ($on_debug) {
                $data = $response->json();
                Log::debug("resp data: " . json_encode($data));
            }

            $data = new Collection($response->json());

            if ($data->has('error')) {
                # TODO: 通知刷新浏览器
                Log::warning("error response");
                Log::warning($data);

                if ($fbAccount) {
                    Telegram::sendMessage("{$fbAccount->id} Token 可能过期了");
                } else {
                    $sanitizedUrl = FbUtils::sanitize_url($endpoint);
                    Telegram::sendMessage("FB API 返回错误");
                    Telegram::sendMessage("url: {$sanitizedUrl}");
                    Telegram::sendMessage("data: {$data}");
                }
                $error = collect($data->get('error'));
                $error_code = $error['code'];
                if (in_array($error_code, [102, 190])) {
                    if ($fbAccount) {
                        $fbAccount->token_valid = false;
                        $fbAccount->save();
                    }
                    Log::warning("token is expired, code: {$error_code}");
                } elseif ($error_code == 100) {
                    if ($error->has('error_subcode')) {
                        $subcode = $error['error_subcode'];
                        if ($subcode == 33) {
                            Log::warning("账号权限可能丢失，需要立即处理");
                        }
                    }
                }
                else {
                    if ($error->has('error_subcode')) {
                        $subcode = $error['error_subcode'];
                        if (in_array($subcode, [463, 467])) {
                            if ($fbAccount) {
                                $fbAccount->token_valid = false;
                                $fbAccount->save();
                            }
                            Log::warning("token is expired, sub code: {$error_code}");
                        }
                    }
                }
//                throw new Exception("E: {$fbAccount->id}:token may expired, detail: {$data->get('error')}");
                if ($fbAccount) {
                    throw new Exception("E: {$fbAccount->id}:token may expired, detail: " . json_encode($data->get('error')));
                } else {
                    throw new Exception("E: token may expired, detail: " . json_encode($data->get('error')));
                }
            }

            if ($fbAccount) {
                // 获取响应头中的新 Cookie（如果有）
                $newCookies = $response->cookies()->toArray();

                // 转换所有 cookie 属性名称为小写
                $normalizedCookies = array_map(function ($cookie) {
                    $normalizedCookie = [];
                    foreach ($cookie as $attribute => $value) {
                        // 将每个属性名称转换为小写
                        $normalizedCookie[strtolower($attribute)] = $value;
                    }
                    return $normalizedCookie;
                }, $newCookies);

                // 将新的 Cookie 保存在一个字段中
                $newCookiesJson = json_encode($normalizedCookies);
                $fbAccount->cookies = $newCookiesJson;
                $fbAccount->save();
            }
//            Log::info('Received cookies: ' . json_encode($newCookies));

            $data['success'] = true;
            return $data;

        } catch (\Exception $e) {
            Log::error($e);
            Log::error('Error while sending HTTP request: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getLastNDays($days, $timezone){
        $dates = [];
        $now = Carbon::now(new DateTimeZone($timezone));

        for ($i = 0; $i < $days; $i++) {
            $dates[] = $now->subDays($i)->format('Y-m-d');
        }

        return array_reverse($dates);
    }

    public static function getRangeDate($date_start, $date_stop) {
        $dateStart = Carbon::createFromFormat('Y-m-d', $date_start);
        $dateStop = Carbon::createFromFormat('Y-m-d', $date_stop);

        $dateList = [];

        for ($date = $dateStart; $date->lte($dateStop); $date->addDay()) {
            $dateList[] = $date->format('Y-m-d');
        }

        return $dateList;
    }

    public static function sanitize_url($url)
    {
        // 解析 URL
        $parsedUrl = parse_url($url);

        // 初始化一个空的字符串用于重新构建 URL
        $sanitizedUrl = '';

        // 如果有查询参数
        if (isset($parsedUrl['query'])) {
            // 解析查询参数
            parse_str($parsedUrl['query'], $queryParams);

            // 如果存在 access_token 参数
            if (isset($queryParams['access_token'])) {
                // 替换它的值
                $queryParams['access_token'] = 'REDACTED';
            }

            // 重新构建查询字符串
            $parsedUrl['query'] = http_build_query($queryParams);
        }

        // 重建 URL
        $sanitizedUrl .= isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $sanitizedUrl .= isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $sanitizedUrl .= isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $sanitizedUrl .= isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $sanitizedUrl .= isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $sanitizedUrl .= isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return $sanitizedUrl;
    }

    public static function getBusinessUserAdAccountRole($permissions)
    {
//        // 将 JSON 字符串转换为 PHP 数组
//        $permissions = json_decode($jsonPermissions, true);
//
//        // 如果 decoding 失败，返回 "Reporting only" 作为默认角色
//        if (json_last_error() !== JSON_ERROR_NONE) {
//            return "Reporting only";
//        }

        // 按照优先级检查权限
        if (in_array('MANAGE', $permissions)) {
            return 'Admin';
        } elseif (in_array('ADVERTISE', $permissions)) {
            return 'General user';
        } else {
            return 'Reporting only';
        }
    }

    public static function getUserPageRole($permissions)
    {
        //https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/pages/
        // 按照优先级检查权限
        if (in_array('MANAGE', $permissions)) {
            return 'Admin';
        } elseif (in_array('CREATE_CONTENT', $permissions)) {
            return 'Editor';
        } elseif (in_array('MODERATE', $permissions)) {
            return 'Moderator';
        } elseif (in_array('ADVERTISE', $permissions)) {
            return 'Advertiser';
        } else {
            return 'Reporting only';
        }
    }

    public static function getPageRoleFromPermittedTasks($permissions)
    {
        // https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/pages
        if (in_array('PROFILE_PLUS_MANAGE', $permissions)) {
            return 'Admin';
        } elseif (in_array('PROFILE_PLUS_CREATE_CONTENT', $permissions)) {
            return 'Editor';
        } elseif (in_array('PROFILE_PLUS_MODERATE', $permissions)) {
            return 'Moderator';
        } elseif (in_array('PROFILE_PLUS_ADVERTISE', $permissions)) {
            return 'Advertiser';
        }  elseif (in_array('PROFILE_PLUS_ANALYZE', $permissions)) {
            return 'Analyst';
        } else {
            return '';
        }
    }


    public static function getPageTasksByName($role)
    {
        $role_map = [
            'Admin' => ['MANAGE', 'CREATE_CONTENT', 'MODERATE', 'ADVERTISE', 'ANALYZE'],
            'Editor' => ['CREATE_CONTENT', 'MODERATE', 'ADVERTISE', 'ANALYZE'],
            'Moderator' => ['MODERATE', 'ADVERTISE', 'ANALYZE'],
            'Advertiser' => ['ADVERTISE', 'ANALYZE'],
            'Analyst' => ['ANALYZE']
        ];

        $fallBack = ['ADVERTISE', 'ANALYZE'];

        return collect($role_map)->get($role, $fallBack);

    }

    public static function getBmAdAccountRoleByTasks($permissions)
    {
        //https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/pages/
        // 按照优先级检查权限
        if (in_array('MANAGE', $permissions)) {
            return 'Admin';
        } elseif (in_array('ADVERTISE', $permissions)) {
            return 'General user';
        }  elseif (in_array('ANALYZE', $permissions)) {
            return 'Reporting only';
        } else {
            return '';
        }
    }

    public static function getBmAdAccountTasksByName($role)
    {
        $role_map = [
            'Admin' => ['MANAGE', 'ADVERTISE', 'ANALYZE'],
            'General user' => ['ADVERTISE', 'ANALYZE'],
            'Reporting only' => ['ANALYZE'],
        ];

        $fallBack = ['ADVERTISE', 'ANALYZE'];

        return collect($role_map)->get($role, $fallBack);

    }

    public static function getCatalogRoleByTasks($permissions)
    {
        //https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/pages/
        // 按照优先级检查权限
        if (in_array('MANAGE', $permissions)) {
            return EnumCatalogRole::Admin->value;
        } elseif (in_array('ADVERTISE', $permissions)) {
            return EnumCatalogRole::GeneralUser->value;
        } else {
            return '';
        }
    }

    public static function getCatalogTasksByRole($role)
    {
        if ($role === EnumCatalogRole::Admin->value) {
            return EnumCatalogTasks::Admin->tasks();
        } elseif ($role === EnumCatalogRole::GeneralUser->value) {
            return EnumCatalogTasks::GeneralUser->tasks();
        }
    }

}
