<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    protected $language;  // 将 $language 属性定义为 protected
    public function __construct(Request $request)
    {
        $this->language = $request->getPreferredLanguage(['en', 'zh']);
        App::setLocale($this->language);
    }

    public function fb_debug(Request $request)
    {
        $on = $request->input('on', false);
        Cache::put('fb_debug', $on);
    }
}
