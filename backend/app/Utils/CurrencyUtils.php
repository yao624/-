<?php

namespace App\Utils;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyUtils
{
    private static $apiUrl = 'https://openexchangerates.org/api/latest.json';

    public static function convert($amount, $from, $to = 'USD', $precision = 0, $decimals = 2)
    {
        // 确保$amount是一个浮点数，并根据精度进行调整
        $amount = floatval($amount) / (10 ** $precision);

        if ($from == $to) {
            return number_format($amount, $decimals, '.', '');
        }

        $rateFrom = Cache::get("exchange_rate_USD_{$from}");
        $rateTo = Cache::get("exchange_rate_USD_{$to}");

        // 如果缓存中没有汇率，那么从API中获取
        if (!$rateFrom || !$rateTo) {
            $response = Http::get(self::$apiUrl, [
                'app_id' => env('OPEN_EXCHANGE_RATES_API_KEY')
            ]);

            if ($response->successful()) {
                $rates = $response->json()['rates'];
                $rateFrom = $rates[$from];
                $rateTo = $rates[$to];

                // 将新的汇率存入缓存，缓存时间设为2小时
                Cache::put("exchange_rate_USD_{$from}", $rateFrom, now()->addHours(7));
                Cache::put("exchange_rate_USD_{$to}", $rateTo, now()->addHours(7));
            } else {
                throw new \Exception('Failed to convert currency');
            }
        }

        // 将结果转换为字符串，并保留两位小数
        return number_format($amount * ($rateTo / $rateFrom), $decimals, '.', '');
    }

    public static function convertToInt($amount, $from, $to = 'USD', $precision = 0)
    {
        // 确保$amount是一个整数，并根据精度进行调整
        $amount = intval($amount) / (10 ** $precision);

        if ($from == $to) {
            return intval(round($amount));
        }

        $rateFrom = Cache::get("exchange_rate_USD_{$from}");
        $rateTo = Cache::get("exchange_rate_USD_{$to}");

        // 如果缓存中没有汇率，那么从API中获取
        if (!$rateFrom || !$rateTo) {
            $response = Http::get(self::$apiUrl, [
                'app_id' => env('OPEN_EXCHANGE_RATES_API_KEY')
            ]);

            if ($response->successful()) {
                $rates = $response->json()['rates'];
                $rateFrom = $rates[$from];
                $rateTo = $rates[$to];

                // 将新的汇率存入缓存，缓存时间设为2小时
                Cache::put("exchange_rate_USD_{$from}", $rateFrom, now()->addHours(2));
                Cache::put("exchange_rate_USD_{$to}", $rateTo, now()->addHours(2));
            } else {
                throw new \Exception('Failed to convert currency');
            }
        }

        // 将结果转换为整数
        return intval(round($amount * ($rateTo / $rateFrom)));
    }

    public static function convertToFloat($amount, $from, $to = 'USD')
    {
        // 确保$amount是一个浮点数
        $amount = floatval($amount);

        if ($from == $to) {
            return round($amount, 2);
        }

        $rateFrom = Cache::get("exchange_rate_USD_{$from}");
        $rateTo = Cache::get("exchange_rate_USD_{$to}");

        // 如果缓存中没有汇率，那么从API中获取
        if (!$rateFrom || !$rateTo) {
            $response = Http::get(self::$apiUrl, [
                'app_id' => env('OPEN_EXCHANGE_RATES_API_KEY')
            ]);

            if ($response->successful()) {
                $rates = $response->json()['rates'];
                $rateFrom = $rates[$from];
                $rateTo = $rates[$to];

                // 将新的汇率存入缓存，缓存时间设为2小时
                Cache::put("exchange_rate_USD_{$from}", $rateFrom, now()->addHours(2));
                Cache::put("exchange_rate_USD_{$to}", $rateTo, now()->addHours(2));
            } else {
                throw new \Exception('Failed to convert currency');
            }
        }

        // 将结果保留两位小数并返回
        return round($amount * ($rateTo / $rateFrom), 2);
    }
    public static function convertAndFormat($value, $from, $to = 'USD', $precision = 0, $decimals = 2)
    {
        if ($value === null) {
            return $value;
        }

        $converted = self::convert($value, $from, $to, $precision);

        return number_format(floatval($converted), $decimals, '.', '');
    }

    public static $currencyConfig = [
        "AED" => [
            "iso" => "AED",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "UAE Dirham",
        ],
        "AFN" => [
            "iso" => "AFN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Afghan Afghani",
        ],
        "ALL" => [
            "iso" => "ALL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Albanian Lek",
        ],
        "AMD" => [
            "iso" => "AMD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Armenian Dram",
        ],
        "ANG" => [
            "iso" => "ANG",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Netherlands Antillean Guilder",
        ],
        "AOA" => [
            "iso" => "AOA",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Angolan Kwanza",
        ],
        "ARS" => [
            "iso" => "ARS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Argentine Peso",
        ],
        "AUD" => [
            "iso" => "AUD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Australian Dollar",
        ],
        "AWG" => [
            "iso" => "AWG",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Aruban Florin",
        ],
        "AZN" => [
            "iso" => "AZN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Azerbaijani Manat",
        ],
        "BAM" => [
            "iso" => "BAM",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bosnian and Herzegovinian Convertible Mark",
        ],
        "BBD" => [
            "iso" => "BBD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Barbadian Dollar",
        ],
        "BDT" => [
            "iso" => "BDT",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bangladeshi Taka",
        ],
        "BGN" => [
            "iso" => "BGN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bulgarian Lev",
        ],
        "BHD" => [
            "iso" => "BHD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bahraini Dinar",
        ],
        "BIF" => [
            "iso" => "BIF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Burundian Franc",
        ],
        "BMD" => [
            "iso" => "BMD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bermudian Dollar",
        ],
        "BND" => [
            "iso" => "BND",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Brunei Dollar",
        ],
        "BOB" => [
            "iso" => "BOB",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bolivian Boliviano",
        ],
        "BRL" => [
            "iso" => "BRL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Brazilian Real",
        ],
        "BSD" => [
            "iso" => "BSD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bahamian Dollar",
        ],
        "BTN" => [
            "iso" => "BTN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Bhutanese Ngultrum",
        ],
        "BWP" => [
            "iso" => "BWP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Botswana Pula",
        ],
        "BYN" => [
            "iso" => "BYN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Belarusian Ruble",
        ],
        "BZD" => [
            "iso" => "BZD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Belize Dollar",
        ],
        "CAD" => [
            "iso" => "CAD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Canadian Dollar",
        ],
        "CDF" => [
            "iso" => "CDF",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Congolese Franc",
        ],
        "CHF" => [
            "iso" => "CHF",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Swiss Franc",
        ],
        "CLF" => [
            "iso" => "CLF",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Chilean unit of account",
        ],
        "CLP" => [
            "iso" => "CLP",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Chilean Peso",
        ],
        "CNY" => [
            "iso" => "CNY",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Chinese Yuan",
        ],
        "COP" => [
            "iso" => "COP",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Colombian Peso",
        ],
        "CRC" => [
            "iso" => "CRC",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Costa Rican Colón",
        ],
        "CVE" => [
            "iso" => "CVE",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Cape Verdean Escudo",
        ],
        "CZK" => [
            "iso" => "CZK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Czech Koruna",
        ],
        "DJF" => [
            "iso" => "DJF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Djiboutian Franc",
        ],
        "DKK" => [
            "iso" => "DKK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Danish Krone",
        ],
        "DOP" => [
            "iso" => "DOP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Dominican Peso",
        ],
        "DZD" => [
            "iso" => "DZD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Algerian Dinar",
        ],
        "EGP" => [
            "iso" => "EGP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Egyptian Pound",
        ],
        "ERN" => [
            "iso" => "ERN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Eritrean Nakfa",
        ],
        "ETB" => [
            "iso" => "ETB",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Ethiopian Birr",
        ],
        "EUR" => [
            "iso" => "EUR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Euro",
        ],
        "FBZ" => [
            "iso" => "FBZ",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "credits",
        ],
        "FJD" => [
            "iso" => "FJD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Fijian Dollar",
        ],
        "FKP" => [
            "iso" => "FKP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Falkland Islands Pound",
        ],
        "GBP" => [
            "iso" => "GBP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "British Pound Sterling",
        ],
        "GEL" => [
            "iso" => "GEL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Georgian Lari",
        ],
        "GHS" => [
            "iso" => "GHS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Ghanaian Cedi",
        ],
        "GIP" => [
            "iso" => "GIP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Gibraltar Pound",
        ],
        "GMD" => [
            "iso" => "GMD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Gambian Dalasi",
        ],
        "GNF" => [
            "iso" => "GNF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Guinean Franc",
        ],
        "GTQ" => [
            "iso" => "GTQ",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Guatemalan Quetzal",
        ],
        "GYD" => [
            "iso" => "GYD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Guyanese Dollar",
        ],
        "HKD" => [
            "iso" => "HKD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Hong Kong Dollar",
        ],
        "HNL" => [
            "iso" => "HNL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Honduran Lempira",
        ],
        "HRK" => [
            "iso" => "HRK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Croatian Kuna",
        ],
        "HTG" => [
            "iso" => "HTG",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Haitian Gourde",
        ],
        "HUF" => [
            "iso" => "HUF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Hungarian Forint",
        ],
        "IDR" => [
            "iso" => "IDR",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Indonesian Rupiah",
        ],
        "ILS" => [
            "iso" => "ILS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Israeli New Shekel",
        ],
        "INR" => [
            "iso" => "INR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Indian Rupee",
        ],
        "IQD" => [
            "iso" => "IQD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Iraqi Dinar",
        ],
        "ISK" => [
            "iso" => "ISK",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Icelandic Krona",
        ],
        "JMD" => [
            "iso" => "JMD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Jamaican Dollar",
        ],
        "JOD" => [
            "iso" => "JOD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Jordanian Dinar",
        ],
        "JPY" => [
            "iso" => "JPY",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Japanese Yen",
        ],
        "KES" => [
            "iso" => "KES",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Kenyan Shilling",
        ],
        "KGS" => [
            "iso" => "KGS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Kyrgyzstani Som",
        ],
        "KHR" => [
            "iso" => "KHR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Cambodian Riel",
        ],
        "KMF" => [
            "iso" => "KMF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Comorian Franc",
        ],
        "KRW" => [
            "iso" => "KRW",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Korean Won",
        ],
        "KWD" => [
            "iso" => "KWD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Kuwaiti Dinar",
        ],
        "KYD" => [
            "iso" => "KYD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Cayman Islands Dollar",
        ],
        "KZT" => [
            "iso" => "KZT",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Kazakhstani Tenge",
        ],
        "LAK" => [
            "iso" => "LAK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Laotian Kip",
        ],
        "LBP" => [
            "iso" => "LBP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Lebanese Pound",
        ],
        "LKR" => [
            "iso" => "LKR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Sri Lankan Rupee",
        ],
        "LRD" => [
            "iso" => "LRD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Liberian Dollar",
        ],
        "LSL" => [
            "iso" => "LSL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Lesotho Loti",
        ],
        "LTL" => [
            "iso" => "LTL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Lithuanian litas",
        ],
        "LVL" => [
            "iso" => "LVL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Latvian lats",
        ],
        "LYD" => [
            "iso" => "LYD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Libyan Dinar",
        ],
        "MAD" => [
            "iso" => "MAD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Moroccan Dirham",
        ],
        "MDL" => [
            "iso" => "MDL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Moldovan Leu",
        ],
        "MGA" => [
            "iso" => "MGA",
            "format" => "{symbol}{amount}",
            "offset" => 5,
            "name" => "Malagasy Ariary",
        ],
        "MKD" => [
            "iso" => "MKD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Macedonian Denar",
        ],
        "MMK" => [
            "iso" => "MMK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Burmese Kyat",
        ],
        "MNT" => [
            "iso" => "MNT",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Mongolian Tugrik",
        ],
        "MOP" => [
            "iso" => "MOP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Macau Patacas",
        ],
        "MRO" => [
            "iso" => "MRO",
            "format" => "{symbol}{amount}",
            "offset" => 5,
            "name" => "Mauritanian Ouguiya",
        ],
        "MUR" => [
            "iso" => "MUR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Mauritian Rupee",
        ],
        "MVR" => [
            "iso" => "MVR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Maldivian Rufiyaa",
        ],
        "MWK" => [
            "iso" => "MWK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Malawian Kwacha",
        ],
        "MXN" => [
            "iso" => "MXN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Mexican Peso",
        ],
        "MYR" => [
            "iso" => "MYR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Malaysian Ringgit",
        ],
        "MZN" => [
            "iso" => "MZN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Mozambican Metical",
        ],
        "NAD" => [
            "iso" => "NAD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Namibian Dollar",
        ],
        "NGN" => [
            "iso" => "NGN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Nigerian Naira",
        ],
        "NIO" => [
            "iso" => "NIO",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Nicaraguan Cordoba",
        ],
        "NOK" => [
            "iso" => "NOK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Norwegian Krone",
        ],
        "NPR" => [
            "iso" => "NPR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Nepalese Rupee",
        ],
        "NZD" => [
            "iso" => "NZD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "New Zealand Dollar",
        ],
        "OMR" => [
            "iso" => "OMR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Omani Rial",
        ],
        "PAB" => [
            "iso" => "PAB",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Panamanian Balboas",
        ],
        "PEN" => [
            "iso" => "PEN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Peruvian Nuevo Sol",
        ],
        "PGK" => [
            "iso" => "PGK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Papua New Guinean Kina",
        ],
        "PHP" => [
            "iso" => "PHP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Philippine Peso",
        ],
        "PKR" => [
            "iso" => "PKR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Pakistani Rupee",
        ],
        "PLN" => [
            "iso" => "PLN",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Polish Zloty",
        ],
        "PYG" => [
            "iso" => "PYG",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Paraguayan Guarani",
        ],
        "QAR" => [
            "iso" => "QAR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Qatari Rials",
        ],
        "RON" => [
            "iso" => "RON",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Romanian Leu",
        ],
        "RSD" => [
            "iso" => "RSD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Serbian Dinar",
        ],
        "RUB" => [
            "iso" => "RUB",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Russian Rouble",
        ],
        "RWF" => [
            "iso" => "RWF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Rwandan Franc",
        ],
        "SAR" => [
            "iso" => "SAR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Saudi Arabian Riyal",
        ],
        "SBD" => [
            "iso" => "SBD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Solomon Islands Dollar",
        ],
        "SCR" => [
            "iso" => "SCR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Seychellois Rupee",
        ],
        "SEK" => [
            "iso" => "SEK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Swedish Krona",
        ],
        "SGD" => [
            "iso" => "SGD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Singapore Dollar",
        ],
        "SHP" => [
            "iso" => "SHP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Saint Helena Pound",
        ],
        "SKK" => [
            "iso" => "SKK",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Slovak koruna",
        ],
        "SLE" => [
            "iso" => "SLE",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Sierra Leonean Leone",
        ],
        "SLL" => [
            "iso" => "SLL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Sierra Leonean Old Leone",
        ],
        "SOS" => [
            "iso" => "SOS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Somali Shilling",
        ],
        "SRD" => [
            "iso" => "SRD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Surinamese Dollar",
        ],
        "SSP" => [
            "iso" => "SSP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "South Sudanese Pound",
        ],
        "STD" => [
            "iso" => "STD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "São Tomé and Príncipe Dobra",
        ],
        "SVC" => [
            "iso" => "SVC",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Salvadoran Colón",
        ],
        "SZL" => [
            "iso" => "SZL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Swazi Lilangeni",
        ],
        "THB" => [
            "iso" => "THB",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Thai Baht",
        ],
        "TJS" => [
            "iso" => "TJS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Tajikistani Somoni",
        ],
        "TMT" => [
            "iso" => "TMT",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Turkmenistani Manat",
        ],
        "TND" => [
            "iso" => "TND",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Tunisian Dinar",
        ],
        "TOP" => [
            "iso" => "TOP",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Tongan Paʻanga",
        ],
        "TRY" => [
            "iso" => "TRY",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Turkish Lira",
        ],
        "TTD" => [
            "iso" => "TTD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Trinidad and Tobago Dollar",
        ],
        "TWD" => [
            "iso" => "TWD",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Taiwan Dollar",
        ],
        "TZS" => [
            "iso" => "TZS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Tanzanian Shilling",
        ],
        "UAH" => [
            "iso" => "UAH",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Ukrainian Hryvnia",
        ],
        "UGX" => [
            "iso" => "UGX",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Ugandan Shilling",
        ],
        "USD" => [
            "iso" => "USD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "US Dollar",
        ],
        "UYU" => [
            "iso" => "UYU",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Uruguayan peso",
        ],
        "UZS" => [
            "iso" => "UZS",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Uzbekistani Som",
        ],
        "VEF" => [
            "iso" => "VEF",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Venezuelan Bolivar",
        ],
        "VES" => [
            "iso" => "VES",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Venezuelan sovereign bolivar",
        ],
        "VND" => [
            "iso" => "VND",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Vietnamese Dong",
        ],
        "VUV" => [
            "iso" => "VUV",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Vanuatu Vatu",
        ],
        "WST" => [
            "iso" => "WST",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Samoan Tala",
        ],
        "XAF" => [
            "iso" => "XAF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "Central African Franc",
        ],
        "XCD" => [
            "iso" => "XCD",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "East Caribbean Dollar",
        ],
        "XOF" => [
            "iso" => "XOF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "West African Franc",
        ],
        "XPF" => [
            "iso" => "XPF",
            "format" => "{symbol}{amount}",
            "offset" => 1,
            "name" => "CFP Franc",
        ],
        "YER" => [
            "iso" => "YER",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Yemeni Rial",
        ],
        "ZAR" => [
            "iso" => "ZAR",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "South African Rand",
        ],
        "ZMW" => [
            "iso" => "ZMW",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Zambian Kwacha",
        ],
        "ZWL" => [
            "iso" => "ZWL",
            "format" => "{symbol}{amount}",
            "offset" => 100,
            "name" => "Zimbabwean Dollar",
        ],
    ];
}
