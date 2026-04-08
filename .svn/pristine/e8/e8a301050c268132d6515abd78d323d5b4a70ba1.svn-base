<?php

/**
 * Facebook Webhook Callback Handler
 *
 * Handles both the verification request (GET) and event notifications (POST).
 */

// --- 配置区域 ---

// 1. Verify Tokens
// 根据 Business Manager ID 和 App ID 存储你的 Verify Token
// 格式: $verifyTokens['你的_BM_ID']['你的_APP_ID'] = '你在Facebook后台设置的Token';
$verifyTokens = [
    '747644474882777' => [ // 示例 Business Manager ID
        '736997169071746' => 'jmq2g2qXSnPkhLQ92JHlU8wNk67SKuUfZTq5qIeCmVtKtRQpaAsnL', // 示例 App ID 1
        '333333333333333' => 'YOUR_CUSTOM_VERIFY_TOKEN_2', // 示例 App ID 2
    ],
    '444444444444444' => [ // 另一个示例 Business Manager ID
        '555555555555555' => 'ANOTHER_VERIFY_TOKEN', // 示例 App ID
    ],
    // 在这里添加你自己的 ID 和 Token
    '1444932969483343' => [
        '1132468578548278' => 'jmq2g2qXSnPkhLQ92JHlU8wNk67SKuUfZTq5qIeCmVtKtRQpaAsnL',
    ],
    '1884103919197233' => [
        '1142009271125696' => 'nWCzC8pj0w95ylB96pvAJwnz2gBj'
    ],
    '939387804912128' => [
        '1664232260911517' => 'Dagq1dZEO1JsvUgIR0Uo8teUowHo'
    ]
];

// 2. App Secrets
// 用于验证 POST 请求的签名，确保请求来自 Facebook
// 格式: $appSecrets['你的_APP_ID'] = '你的应用密钥';
$appSecrets = [
    '736997169071746' => '0f1770394033ca01f5cd75cad0c9e694',
    '333333333333333' => 'YOUR_APP_SECRET_2',
    '555555555555555' => 'ANOTHER_APP_SECRET',
    // 在这里添加你自己的 App ID 和 App Secret
    '1132468578548278' => 'eba0b7bc83564a6925923849cd6bcb0e',
    '1142009271125696' => 'e3ef3c0a7b076f7207ded61ec33c2f73',
    '1664232260911517' => '11900de5265e7da91971d7379c33aa4b'
];

// 3. 日志文件路径
// 确保这个文件对于 PHP 进程是可写的
$logFile = 'webhook.log';

// --- 逻辑处理 ---

// 辅助函数：记录日志
function log_message($message) {
    global $logFile;
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND);
}

// 辅助函数：转发数据到我们的API接口
function forwardToWebhookAPI($data) {
    $apiUrl = 'https://adsmanager.smartadx.io/api/v2/fb-webhook';
    $webhookKey = '9bPx4gu973VFJtizfc4CRlw71b1yDSFws';

    $postData = [
        'key' => $webhookKey,
        'data' => $data
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_message("API Call Error: " . $error);
        return [
            'success' => false,
            'error' => $error,
            'http_code' => null
        ];
    }

    $responseData = json_decode($response, true);

    if ($httpCode !== 200) {
        log_message("API Call Failed: HTTP $httpCode - " . $response);
        return [
            'success' => false,
            'http_code' => $httpCode,
            'response' => $responseData ?: $response
        ];
    }

    log_message("API Call Success: HTTP $httpCode");
    return [
        'success' => true,
        'http_code' => $httpCode,
        'response' => $responseData
    ];
}

// 从 URL 获取 bm_id 和 app_id (通过 .htaccess 重写)
$bmId = $_GET['bm_id'] ?? null;
$appId = $_GET['app_id'] ?? null;

if (!$bmId || !$appId) {
    log_message("Error: Business Manager ID or App ID not provided in URL.");
    http_response_code(400); // Bad Request
    echo 'Error: Missing IDs in URL.';
    exit;
}

log_message("Request received for BM_ID: $bmId, APP_ID: $appId");

// 获取请求方法
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 根据请求方法处理
if ($requestMethod === 'GET') {
    /**
     * 处理 Webhook 验证请求 (Step 1 in Facebook Docs)
     */
    log_message("Handling GET verification request...");

    $mode = $_GET['hub_mode'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? null;
    $verifyToken = $_GET['hub_verify_token'] ?? null;

    // 从配置中获取期望的 verify token
    $expectedToken = $verifyTokens[$bmId][$appId] ?? null;

    if ($mode === 'subscribe' && $verifyToken === $expectedToken) {
        log_message("Verification successful. Responding with challenge token.");
        http_response_code(200);
        echo $challenge;
    } else {
        log_message("Verification failed. Mode: $mode, Received Token: $verifyToken, Expected Token: $expectedToken");
        http_response_code(403); // Forbidden
        echo 'Forbidden';
    }

} elseif ($requestMethod === 'POST') {
    /**
     * 处理 Webhook 事件通知
     */
    log_message("Handling POST event notification...");

    // 1. 验证签名 (非常重要，确保请求来自 Facebook)
    $appSecret = $appSecrets[$appId] ?? null;
    if (!$appSecret) {
        log_message("Security Error: App Secret not found for App ID: $appId");
        http_response_code(200);
        exit;
    }

    $signatureHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (empty($signatureHeader)) {
        log_message("Security Error: X-Hub-Signature-256 header not found.");
        http_response_code(200);
        exit;
    }

    list($algo, $hash) = explode('=', $signatureHeader, 2);
    $payload = file_get_contents('php://input');
    $expectedHash = hash_hmac('sha256', $payload, $appSecret);
    log_message("Signature: $signatureHeader, expected hash: $expectedHash");

    if (!hash_equals($hash, $expectedHash)) {
        log_message("Security Error: Signature verification failed. Received Hash: $hash, Expected Hash: $expectedHash");
        http_response_code(200);
        exit;
    }

    log_message("Signature verified successfully.");

    // 2. 处理数据
    $data = json_decode($payload, true);

    // 将接收到的数据完整记录到日志中，方便调试
    log_message("Payload received: " . json_encode($data, JSON_PRETTY_PRINT));

    // 3. 转发数据到我们的API接口
    $apiResponse = forwardToWebhookAPI($data);
    log_message("API Response: " . json_encode($apiResponse, JSON_PRETTY_PRINT));

    // 4. 响应 Facebook
    // 必须尽快响应 200 OK，否则 Facebook 会认为推送失败并重试。
    http_response_code(200);
    echo 'EVENT_RECEIVED';

} else {
    // 不支持的请求方法
    log_message("Unsupported request method: $requestMethod");
    http_response_code(405); // Method Not Allowed
    echo 'Method Not Allowed';
}

?>
