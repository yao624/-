<?php

namespace App\Utils;

class UserAgentUtils
{
    /**
     * 解析 User-Agent 获取浏览器、操作系统、设备信息
     */
    public static function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown';
        $browserVersion = '';

        if (preg_match('/Edg\/([\d.]+)/i', $ua, $matches)) {
            $browser = 'Edge';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $matches)) {
            $browser = 'Chrome';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $matches)) {
            $browser = 'Firefox';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Safari\/([\d.]+)/i', $ua, $matches) && !preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
            $browserVersion = $matches[1];
        } elseif (preg_match('/MSIE|Trident/i', $ua)) {
            $browser = 'IE';
        }

        $os = 'Unknown';
        $osVersion = '';

        if (preg_match('/Windows NT 10/i', $ua)) {
            $os = 'Windows';
            $osVersion = '10/11';
        } elseif (preg_match('/Windows NT 6\.3/i', $ua)) {
            $os = 'Windows';
            $osVersion = '8.1';
        } elseif (preg_match('/Windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X ([\d._]+)/i', $ua, $matches)) {
            $os = 'macOS';
            $osVersion = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/iPhone/i', $ua)) {
            $os = 'iOS';
            if (preg_match('/OS ([\d_]+)/i', $ua, $matches)) {
                $osVersion = str_replace('_', '.', $matches[1]);
            }
        } elseif (preg_match('/iPad/i', $ua)) {
            $os = 'iPadOS';
            if (preg_match('/OS ([\d_]+)/i', $ua, $matches)) {
                $osVersion = str_replace('_', '.', $matches[1]);
            }
        } elseif (preg_match('/Android ([\d.]+)/i', $ua, $matches)) {
            $os = 'Android';
            $osVersion = $matches[1];
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        $device = 'Desktop';
        if (preg_match('/iPhone/i', $ua)) {
            $device = 'iPhone';
        } elseif (preg_match('/iPad/i', $ua)) {
            $device = 'iPad';
        } elseif (preg_match('/Android/i', $ua) && preg_match('/Mobile/i', $ua)) {
            $device = 'Android Phone';
        } elseif (preg_match('/Android/i', $ua)) {
            $device = 'Android Tablet';
        } elseif (preg_match('/Mobile/i', $ua)) {
            $device = 'Mobile Phone';
        }

        return [
            'browser' => $browserVersion ? "{$browser} {$browserVersion}" : $browser,
            'os' => $osVersion ? "{$os} {$osVersion}" : $os,
            'device' => $device,
        ];
    }
}
