<?php

namespace App\Utils;

class DevUtils
{
    public static function exists($modelClass, $value, $property=null)
    {
        if ($property) {
            $key = $property;
        } else {
            $key = 'id';
        }
        return $modelClass::where($key, $value)->exists();
    }
}
