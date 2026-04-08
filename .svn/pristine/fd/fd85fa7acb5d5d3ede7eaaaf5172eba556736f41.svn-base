<?php
/**
 * 准备微调训练数据
 * 使用方法: php scripts/prepare_training_data.php
 */

require __DIR__ . '/../vendor/autoload.php';

class TrainingDataPreparer
{
    /**
     * 从数组准备训练数据
     */
    public function prepareFromArray($knowledgeBase, $systemPrompt = null)
    {
        $defaultSystemPrompt = $systemPrompt ?? '你是一个专业的广告投放助手，擅长Facebook、Google等平台的广告策略和优化。';
        
        $trainingData = [];
        foreach ($knowledgeBase as $item) {
            $messages = [
                ['role' => 'system', 'content' => $defaultSystemPrompt]
            ];
            
            if (isset($item['question']) && isset($item['answer'])) {
                $messages[] = ['role' => 'user', 'content' => $item['question']];
                $messages[] = ['role' => 'assistant', 'content' => $item['answer']];
            } elseif (isset($item['messages'])) {
                // 如果已经是消息格式，直接使用
                $messages = $item['messages'];
            }
            
            $trainingData[] = ['messages' => $messages];
        }

        return $trainingData;
    }

    /**
     * 从CSV文件读取数据
     */
    public function prepareFromCSV($csvFile, $systemPrompt = null)
    {
        if (!file_exists($csvFile)) {
            throw new Exception("文件不存在: {$csvFile}");
        }

        $data = [];
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }

        return $this->prepareFromArray($data, $systemPrompt);
    }

    /**
     * 从JSON文件读取数据
     */
    public function prepareFromJSON($jsonFile, $systemPrompt = null)
    {
        if (!file_exists($jsonFile)) {
            throw new Exception("文件不存在: {$jsonFile}");
        }

        $data = json_decode(file_get_contents($jsonFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON格式错误: " . json_last_error_msg());
        }

        return $this->prepareFromArray($data, $systemPrompt);
    }

    /**
     * 保存为JSONL格式
     */
    public function saveAsJSONL($trainingData, $outputFile)
    {
        $handle = fopen($outputFile, 'w');
        if (!$handle) {
            throw new Exception("无法创建文件: {$outputFile}");
        }

        foreach ($trainingData as $item) {
            fwrite($handle, json_encode($item, JSON_UNESCAPED_UNICODE) . "\n");
        }
        fclose($handle);
        
        echo "✅ 已保存 {$outputFile}，共 " . count($trainingData) . " 条数据\n";
        return true;
    }

    /**
     * 验证数据格式
     */
    public function validateData($jsonlFile)
    {
        if (!file_exists($jsonlFile)) {
            throw new Exception("文件不存在: {$jsonlFile}");
        }

        $errors = [];
        $lineNum = 0;
        $validCount = 0;
        
        $handle = fopen($jsonlFile, 'r');
        while (($line = fgets($handle)) !== false) {
            $lineNum++;
            $line = trim($line);
            if (empty($line)) continue;
            
            $data = json_decode($line, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "第 {$lineNum} 行：JSON格式错误 - " . json_last_error_msg();
                continue;
            }
            
            if (!isset($data['messages'])) {
                $errors[] = "第 {$lineNum} 行：缺少 messages 字段";
                continue;
            }
            
            if (!is_array($data['messages']) || empty($data['messages'])) {
                $errors[] = "第 {$lineNum} 行：messages 必须是非空数组";
                continue;
            }
            
            foreach ($data['messages'] as $idx => $msg) {
                if (!isset($msg['role']) || !isset($msg['content'])) {
                    $errors[] = "第 {$lineNum} 行，消息 #{$idx}：缺少 role 或 content 字段";
                    break;
                }
                
                if (!in_array($msg['role'], ['system', 'user', 'assistant'])) {
                    $errors[] = "第 {$lineNum} 行，消息 #{$idx}：role 必须是 system/user/assistant";
                    break;
                }
            }
            
            $validCount++;
        }
        fclose($handle);
        
        if (empty($errors)) {
            echo "✅ 数据验证通过！共 {$validCount} 条有效数据\n";
        } else {
            echo "❌ 发现 " . count($errors) . " 个错误：\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }
        
        return empty($errors);
    }

    /**
     * 分割训练集和验证集
     */
    public function splitDataset($jsonlFile, $trainRatio = 0.9)
    {
        $lines = file($jsonlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        shuffle($lines);
        
        $total = count($lines);
        $trainCount = (int)($total * $trainRatio);
        
        $trainFile = str_replace('.jsonl', '_train.jsonl', $jsonlFile);
        $valFile = str_replace('.jsonl', '_val.jsonl', $jsonlFile);
        
        // 保存训练集
        file_put_contents($trainFile, implode("\n", array_slice($lines, 0, $trainCount)) . "\n");
        echo "✅ 训练集已保存: {$trainFile} ({$trainCount} 条)\n";
        
        // 保存验证集
        if ($trainCount < $total) {
            file_put_contents($valFile, implode("\n", array_slice($lines, $trainCount)) . "\n");
            echo "✅ 验证集已保存: {$valFile} (" . ($total - $trainCount) . " 条)\n";
        }
        
        return [$trainFile, $valFile];
    }
}

// 命令行使用
if (php_sapi_name() === 'cli') {
    $preparer = new TrainingDataPreparer();
    
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'prepare':
            // 从CSV或JSON准备数据
            $inputFile = $argv[2] ?? null;
            $outputFile = $argv[3] ?? 'training_data.jsonl';
            
            if (!$inputFile) {
                echo "用法: php prepare_training_data.php prepare <输入文件> [输出文件]\n";
                exit(1);
            }
            
            $ext = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
            
            try {
                if ($ext === 'csv') {
                    $data = $preparer->prepareFromCSV($inputFile);
                } elseif ($ext === 'json') {
                    $data = $preparer->prepareFromJSON($inputFile);
                } else {
                    throw new Exception("不支持的文件格式: {$ext}");
                }
                
                $preparer->saveAsJSONL($data, $outputFile);
                $preparer->validateData($outputFile);
            } catch (Exception $e) {
                echo "❌ 错误: " . $e->getMessage() . "\n";
                exit(1);
            }
            break;
            
        case 'validate':
            $jsonlFile = $argv[2] ?? 'training_data.jsonl';
            try {
                $preparer->validateData($jsonlFile);
            } catch (Exception $e) {
                echo "❌ 错误: " . $e->getMessage() . "\n";
                exit(1);
            }
            break;
            
        case 'split':
            $jsonlFile = $argv[2] ?? 'training_data.jsonl';
            $ratio = isset($argv[3]) ? (float)$argv[3] : 0.9;
            try {
                $preparer->splitDataset($jsonlFile, $ratio);
            } catch (Exception $e) {
                echo "❌ 错误: " . $e->getMessage() . "\n";
                exit(1);
            }
            break;
            
        case 'example':
            // 生成示例数据
            $exampleData = [
                [
                    'question' => '如何提高广告转化率？',
                    'answer' => '提高转化率的方法：1. 优化落地页加载速度 2. 使用清晰的CTA按钮 3. 减少表单字段 4. 添加信任标识 5. A/B测试不同版本'
                ],
                [
                    'question' => 'Facebook广告预算如何设置？',
                    'answer' => '预算设置建议：1. 日预算至少是目标CPA的5-10倍 2. 测试阶段使用较低预算 3. 表现好的广告逐步增加预算 4. 使用预算优化功能'
                ],
                [
                    'question' => 'CTR多少算正常？',
                    'answer' => 'CTR（点击率）的正常范围：1. Facebook广告：平均1-3% 2. Google搜索广告：平均2-5% 3. 展示广告：平均0.5-1% 4. 视频广告：平均1-2%'
                ],
            ];
            
            $data = $preparer->prepareFromArray($exampleData);
            $preparer->saveAsJSONL($data, 'example_training_data.jsonl');
            echo "✅ 示例数据已生成: example_training_data.jsonl\n";
            break;
            
        default:
            echo "GPT 训练数据准备工具\n\n";
            echo "用法:\n";
            echo "  prepare <输入文件> [输出文件]  - 从CSV/JSON准备训练数据\n";
            echo "  validate <jsonl文件>          - 验证数据格式\n";
            echo "  split <jsonl文件> [比例]      - 分割训练集和验证集\n";
            echo "  example                       - 生成示例数据\n\n";
            echo "示例:\n";
            echo "  php prepare_training_data.php prepare knowledge.csv training_data.jsonl\n";
            echo "  php prepare_training_data.php validate training_data.jsonl\n";
            echo "  php prepare_training_data.php split training_data.jsonl 0.9\n";
            break;
    }
}

