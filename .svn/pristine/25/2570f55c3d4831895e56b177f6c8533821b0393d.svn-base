<?php

namespace App\Console\Commands;

use App\Models\RequestLog;
use Illuminate\Console\Command;

class CleanupRequestLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-logs:cleanup {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old request logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Days must be a positive integer');
            return 1;
        }

        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up request logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $deletedCount = RequestLog::where('requested_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$deletedCount} request log records.");

        return 0;
    }
}
