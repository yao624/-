<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogFailedJob
{
    public function handle(JobFailed $event)
    {
        Log::debug("job failed event");
    }
}
