<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Telegram
{
    private static $TOKEN = '6245245805:AAGlwolnKV_8J1kNayFsaPjTt0QZfxpi6CY';
//    private static $CHAT_ID = '-1001773594961';
    private static $CHAT_ID = '-4941370878';

    public static function sendMessage($msg)
    {
        $token = self::$TOKEN;
        $chat_id = env('CHAT_ID', self::$CHAT_ID);

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        try {
            $resp = Http::post($url, [
                'chat_id' => $chat_id,
                'text' => $msg
            ]);
        } catch (\Exception $e) {
            Log::debug("failed to send tg message");
            Log::debug($e->getMessage());
        }

    }
}
