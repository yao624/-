<?php
/**
 * 检查内部访问是否被 Cloudflare 阻止
 * 使用方法: php scripts/check_internal_access.php
 */

class InternalAccessChecker
{
    private $domain;
    private $testPaths;

    public function __construct($domain = null)
    {
        $this->domain = $domain ?? $this->getDomainFromEnv();
        $this->testPaths = [
            '/api/health',
            '/api/internal/test',
            '/login_up.php',
        ];
    }

    private function getDomainFromEnv()
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('/APP_URL=(.+)/', $line, $matches)) {
                    $url = trim($matches[1]);
                    return parse_url($url, PHP_URL_HOST);
                }
            }
        }
        return null;
    }

    /**
     * 获取当前服务器的公网 IP
     */
    public function getCurrentIP()
    {
        echo "🔍 正在获取当前服务器 IP...\n";
        
        $services = [
            'https://api.ipify.org',
            'https://ifconfig.me',
            'https://icanhazip.com',
        ];

        foreach ($services as $service) {
            try {
                $ip = trim(@file_get_contents($service));
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                    echo "✅ 当前服务器 IP: {$ip}\n\n";
                    return $ip;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        echo "❌ 无法获取 IP 地址\n";
        return null;
    }

    /**
     * 检查 IP 是否为私有 IP
     */
    public function isPrivateIP($ip)
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * 测试路径是否可访问
     */
    public function testPath($path)
    {
        if (!$this->domain) {
            echo "❌ 未设置域名，请提供域名参数\n";
            return false;
        }

        $url = "https://{$this->domain}{$path}";
        echo "测试: {$url}\n";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // 设置 User-Agent，模拟正常请求
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; InternalCheck/1.0)');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        $error = curl_error($ch);
        curl_close($ch);

        echo "   HTTP 状态码: {$httpCode}\n";

        // 检查是否被 Cloudflare 拦截
        if ($httpCode === 403) {
            echo "   ❌ 被拦截 (403 Forbidden)\n";
            
            // 检查是否是 Cloudflare 的拦截页面
            if (strpos($body, 'cf-error-details') !== false || 
                strpos($body, 'cloudflare') !== false ||
                strpos($headers, 'cf-ray') !== false) {
                echo "   ⚠️  这是 Cloudflare 的拦截页面\n";
            }
            
            return false;
        } elseif ($httpCode === 429) {
            echo "   ⚠️  请求过于频繁 (429 Too Many Requests)\n";
            return false;
        } elseif ($httpCode === 200 || $httpCode === 404) {
            // 404 也算正常，说明没有被拦截，只是路径不存在
            echo "   ✅ 可以访问（HTTP {$httpCode}）\n";
            return true;
        } else {
            echo "   ⚠️  返回状态码: {$httpCode}\n";
            if ($error) {
                echo "   错误: {$error}\n";
            }
            return $httpCode < 400;
        }
    }

    /**
     * 运行完整检查
     */
    public function runCheck()
    {
        echo "═══════════════════════════════════════\n";
        echo "  Cloudflare 内部访问检查工具\n";
        echo "═══════════════════════════════════════\n\n";

        // 1. 获取当前 IP
        $currentIP = $this->getCurrentIP();
        if ($currentIP) {
            if ($this->isPrivateIP($currentIP)) {
                echo "⚠️  注意: 当前 IP 是私有 IP，可能无法从公网访问\n";
                echo "   建议: 使用服务器的公网 IP 进行测试\n\n";
            }
        }

        // 2. 显示域名
        if ($this->domain) {
            echo "🌐 测试域名: {$this->domain}\n\n";
        } else {
            echo "❌ 未设置域名\n";
            echo "   使用方法: php check_internal_access.php <域名>\n";
            echo "   或在 .env 文件中设置 APP_URL\n\n";
            return;
        }

        // 3. 测试路径
        echo "📋 开始测试路径...\n\n";
        $results = [];
        
        foreach ($this->testPaths as $path) {
            $results[$path] = $this->testPath($path);
            echo "\n";
        }

        // 4. 总结
        echo "═══════════════════════════════════════\n";
        echo "  检查结果总结\n";
        echo "═══════════════════════════════════════\n\n";

        $blockedCount = 0;
        foreach ($results as $path => $accessible) {
            if (!$accessible) {
                $blockedCount++;
                echo "❌ {$path} - 被拦截或无法访问\n";
            } else {
                echo "✅ {$path} - 可以访问\n";
            }
        }

        echo "\n";

        if ($blockedCount > 0) {
            echo "⚠️  发现 {$blockedCount} 个路径被拦截\n\n";
            echo "🔧 解决方案：\n";
            echo "1. 获取当前服务器 IP: {$currentIP}\n";
            echo "2. 进入 Cloudflare Dashboard\n";
            echo "3. Security → WAF → Tools → IP Access Rules\n";
            echo "4. 创建规则：IP = {$currentIP}, Action = Allow\n";
            echo "5. 或者查看文档: Cloudflare内部访问被阻止解决方案.md\n";
        } else {
            echo "✅ 所有路径都可以正常访问\n";
        }

        echo "\n";
    }

    /**
     * 生成 Cloudflare 规则配置建议
     */
    public function generateRuleSuggestion($ip)
    {
        echo "\n📝 Cloudflare 规则配置建议：\n\n";
        echo "方案 1: IP Access Rule（推荐）\n";
        echo "─────────────────────────────────────\n";
        echo "路径: Security → WAF → Tools → IP Access Rules\n";
        echo "配置:\n";
        echo "  - IP 地址: {$ip}\n";
        echo "  - 操作: Allow（允许）\n";
        echo "  - 说明: 允许内部服务器访问\n\n";

        echo "方案 2: Custom Rule\n";
        echo "─────────────────────────────────────\n";
        echo "路径: Security → WAF → Custom Rules\n";
        echo "表达式:\n";
        echo "  (ip.src eq {$ip})\n";
        echo "操作: Allow\n";
        echo "优先级: 高\n\n";
    }
}

// 命令行使用
if (php_sapi_name() === 'cli') {
    $domain = $argv[1] ?? null;
    
    $checker = new InternalAccessChecker($domain);
    $checker->runCheck();
    
    $currentIP = $checker->getCurrentIP();
    if ($currentIP && !$checker->isPrivateIP($currentIP)) {
        $checker->generateRuleSuggestion($currentIP);
    }
}

