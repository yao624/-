<?php
/**
 * OpenAI 模型微调工具
 * 使用方法: php scripts/fine_tune_model.php <命令> [参数]
 * 
 * 需要先设置环境变量: export OPENAI_API_KEY=sk-xxxxx
 * 或创建 .env 文件: OPENAI_API_KEY=sk-xxxxx
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client as HttpClient;

class OpenAIFineTuner
{
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1';
    private $httpClient;

    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey ?? $this->getApiKey();
        if (!$this->apiKey) {
            throw new Exception("未设置 OPENAI_API_KEY，请设置环境变量或 .env 文件");
        }

        $this->httpClient = new HttpClient([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function getApiKey()
    {
        // 从环境变量获取
        $key = getenv('OPENAI_API_KEY');
        if ($key) return $key;

        // 从 .env 文件获取
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'OPENAI_API_KEY=') === 0) {
                    return substr($line, strlen('OPENAI_API_KEY='));
                }
            }
        }

        return null;
    }

    /**
     * 上传训练文件
     */
    public function uploadFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("文件不存在: {$filePath}");
        }

        echo "📤 正在上传文件: {$filePath}\n";

        $response = $this->httpClient->post('/files', [
            'multipart' => [
                [
                    'name' => 'purpose',
                    'contents' => 'fine-tune',
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'), 
                    'filename' => basename($filePath),
                ],
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['error'])) {
            throw new Exception("上传失败: " . $result['error']['message']);
        }

        echo "✅ 文件上传成功！\n";
        echo "   文件ID: {$result['id']}\n";
        echo "   文件名: {$result['filename']}\n";
        echo "   文件大小: " . number_format($result['bytes'] / 1024, 2) . " KB\n";

        return $result['id'];
    }

    /**
     * 创建微调任务
     */
    public function createFineTuneJob($fileId, $options = [])
    {
        $defaultOptions = [
            'model' => 'gpt-3.5-turbo',
            'suffix' => 'ad-expert',
            'n_epochs' => 3,
            'batch_size' => null,
            'learning_rate_multiplier' => null,
        ];

        $options = array_merge($defaultOptions, $options);

        echo "🚀 正在创建微调任务...\n";
        echo "   模型: {$options['model']}\n";
        echo "   后缀: {$options['suffix']}\n";
        echo "   训练轮数: {$options['n_epochs']}\n";

        $data = [
            'training_file' => $fileId,
            'model' => $options['model'],
            'suffix' => $options['suffix'],
        ];

        if ($options['n_epochs']) {
            $data['hyperparameters'] = ['n_epochs' => $options['n_epochs']];
        }

        $response = $this->httpClient->post('/fine_tuning/jobs', [
            'json' => $data,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['error'])) {
            throw new Exception("创建失败: " . $result['error']['message']);
        }

        echo "✅ 微调任务创建成功！\n";
        echo "   任务ID: {$result['id']}\n";
        echo "   状态: {$result['status']}\n";

        return $result;
    }

    /**
     * 查询微调任务状态
     */
    public function getFineTuneStatus($fineTuneId)
    {
        $response = $this->httpClient->get("/fine_tuning/jobs/{$fineTuneId}");

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['error'])) {
            throw new Exception("查询失败: " . $result['error']['message']);
        }

        echo "📊 微调任务状态\n";
        echo "   任务ID: {$result['id']}\n";
        echo "   状态: {$result['status']}\n";
        echo "   模型: {$result['model']}\n";

        if (isset($result['fine_tuned_model']) && $result['fine_tuned_model']) {
            echo "   ✅ 微调模型ID: {$result['fine_tuned_model']}\n";
        }

        if (isset($result['trained_tokens'])) {
            echo "   训练Token数: " . number_format($result['trained_tokens']) . "\n";
        }

        if (isset($result['training_file'])) {
            echo "   训练文件ID: {$result['training_file']}\n";
        }

        if (isset($result['error'])) {
            echo "   ❌ 错误: " . json_encode($result['error'], JSON_UNESCAPED_UNICODE) . "\n";
        }

        return $result;
    }

    /**
     * 列出所有微调任务
     */
    public function listFineTunes($limit = 10)
    {
        $response = $this->httpClient->get('/fine_tuning/jobs', [
            'query' => ['limit' => $limit],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['error'])) {
            throw new Exception("查询失败: " . $result['error']['message']);
        }

        $jobs = $result['data'] ?? [];
        echo "📋 共有 " . count($jobs) . " 个微调任务：\n\n";

        foreach ($jobs as $job) {
            echo "   ID: {$job['id']}\n";
            echo "   状态: {$job['status']}\n";
            echo "   模型: {$job['model']}\n";
            if (isset($job['fine_tuned_model']) && $job['fine_tuned_model']) {
                echo "   ✅ 微调模型: {$job['fine_tuned_model']}\n";
            }
            echo "   创建时间: " . date('Y-m-d H:i:s', $job['created_at']) . "\n";
            echo "\n";
        }

        return $jobs;
    }

    /**
     * 取消微调任务
     */
    public function cancelFineTune($fineTuneId)
    {
        $response = $this->httpClient->post("/fine_tuning/jobs/{$fineTuneId}/cancel");

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['error'])) {
            throw new Exception("取消失败: " . $result['error']['message']);
        }

        echo "✅ 任务已取消\n";
        return $result;
    }

    /**
     * 等待微调完成（轮询）
     */
    public function waitForCompletion($fineTuneId, $interval = 30)
    {
        echo "⏳ 等待微调完成（每 {$interval} 秒检查一次）...\n\n";

        while (true) {
            $status = $this->getFineTuneStatus($fineTuneId);
            echo "\n";

            if ($status['status'] === 'succeeded') {
                echo "🎉 微调完成！\n";
                if (isset($status['fine_tuned_model'])) {
                    echo "📝 请保存以下模型ID用于调用：\n";
                    echo "   {$status['fine_tuned_model']}\n";
                }
                return $status['fine_tuned_model'] ?? null;
            } elseif (in_array($status['status'], ['failed', 'cancelled'])) {
                echo "❌ 微调失败或已取消\n";
                return null;
            }

            sleep($interval);
        }
    }
}

// 命令行使用
if (php_sapi_name() === 'cli') {
    try {
        $tuner = new OpenAIFineTuner();
        $command = $argv[1] ?? 'help';

        switch ($command) {
            case 'upload':
                $filePath = $argv[2] ?? null;
                if (!$filePath) {
                    echo "用法: php fine_tune_model.php upload <训练文件.jsonl>\n";
                    exit(1);
                }
                $fileId = $tuner->uploadFile($filePath);
                echo "\n💡 提示: 使用以下命令创建微调任务:\n";
                echo "   php fine_tune_model.php create {$fileId}\n";
                break;

            case 'create':
                $fileId = $argv[2] ?? null;
                if (!$fileId) {
                    echo "用法: php fine_tune_model.php create <文件ID> [模型] [后缀]\n";
                    echo "示例: php fine_tune_model.php create file-xxxxx gpt-3.5-turbo ad-expert\n";
                    exit(1);
                }
                $model = $argv[3] ?? 'gpt-3.5-turbo';
                $suffix = $argv[4] ?? 'ad-expert';
                $result = $tuner->createFineTuneJob($fileId, [
                    'model' => $model,
                    'suffix' => $suffix,
                ]);
                echo "\n💡 提示: 使用以下命令查询状态:\n";
                echo "   php fine_tune_model.php status {$result['id']}\n";
                echo "   或等待完成:\n";
                echo "   php fine_tune_model.php wait {$result['id']}\n";
                break;

            case 'status':
                $jobId = $argv[2] ?? null;
                if (!$jobId) {
                    echo "用法: php fine_tune_model.php status <任务ID>\n";
                    exit(1);
                }
                $tuner->getFineTuneStatus($jobId);
                break;

            case 'list':
                $limit = isset($argv[2]) ? (int)$argv[2] : 10;
                $tuner->listFineTunes($limit);
                break;

            case 'wait':
                $jobId = $argv[2] ?? null;
                if (!$jobId) {
                    echo "用法: php fine_tune_model.php wait <任务ID> [检查间隔秒数]\n";
                    exit(1);
                }
                $interval = isset($argv[3]) ? (int)$argv[3] : 30;
                $tuner->waitForCompletion($jobId, $interval);
                break;

            case 'cancel':
                $jobId = $argv[2] ?? null;
                if (!$jobId) {
                    echo "用法: php fine_tune_model.php cancel <任务ID>\n";
                    exit(1);
                }
                $tuner->cancelFineTune($jobId);
                break;

            default:
                echo "OpenAI 模型微调工具\n\n";
                echo "命令:\n";
                echo "  upload <文件>              - 上传训练文件\n";
                echo "  create <文件ID> [模型] [后缀] - 创建微调任务\n";
                echo "  status <任务ID>             - 查询任务状态\n";
                echo "  list [数量]                 - 列出所有任务\n";
                echo "  wait <任务ID> [间隔]        - 等待任务完成\n";
                echo "  cancel <任务ID>             - 取消任务\n\n";
                echo "完整流程示例:\n";
                echo "  1. php fine_tune_model.php upload training_data.jsonl\n";
                echo "  2. php fine_tune_model.php create file-xxxxx\n";
                echo "  3. php fine_tune_model.php wait ftjob-xxxxx\n\n";
                echo "环境变量:\n";
                echo "  设置 OPENAI_API_KEY 环境变量或在 .env 文件中配置\n";
                break;
        }
    } catch (Exception $e) {
        echo "❌ 错误: " . $e->getMessage() . "\n";
        exit(1);
    }
}

