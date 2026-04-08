<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CardUtils
{
    public static $base_url = 'https://api.airwallex.com';
    private static function airwallex_auth()
    {
        $client_id = 'xxxxx';
        $api_key = 'xxxxxx';
        $endpoint = 'https://api.airwallex.com';

        $url = "{$endpoint}/api/v1/authentication/login";
        $resp = Http::withHeaders([
            'x-client-id' => $client_id,
            'x-api-key' => $api_key
        ])->post($url);

        if ($resp->successful()) {
            return $resp->json('token');
        } else {
            Log::warning("failed to auth for airwallex");
            Log::warning($resp->body());
            return '';
        }

    }

    public static function get_token()
    {
        $token = Cache::remember('TOKEN', 1500, function () {
            $token = static::airwallex_auth();
            if ($token) {
                return $token;
            } else {
                throw new Exception("failed to auth airwallex");
            }
        });
        return $token;
    }

    public static function generate_request_id($function) {
        $timestamp = date('YmdHis');
        $randomStr = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
        return $function . '-' . $timestamp . '-' . $randomStr;
    }

    public static function mask_number($cardNumber, $showFirst = 4, $showLast = 4) {
        $maskLength = strlen($cardNumber) - ($showFirst + $showLast);
        $mask = str_repeat('*', $maskLength);
        $firstPart = substr($cardNumber, 0, $showFirst);
        $lastPart = substr($cardNumber, -1 * $showLast);

        return $firstPart . $mask . $lastPart;
    }
}
