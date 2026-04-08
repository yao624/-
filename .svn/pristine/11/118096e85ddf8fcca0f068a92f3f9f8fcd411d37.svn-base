<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 显式覆盖 api 前缀下所有版本（部分环境下 path 匹配更稳）
    'paths' => ['api/*', 'api/v2/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    // 显式包含前端实际会带的请求头，避免浏览器预检 OPTIONS 时被拦截：
    // Response to preflight request doesn't pass access control check
    'allowed_headers' => [
        'Authorization',
        'Lang',
        'Accept-Language',
        'Content-Type',
        'X-Requested-With',
        'Accept',
        'Origin',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
