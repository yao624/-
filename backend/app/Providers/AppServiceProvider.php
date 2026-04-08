<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Queue\CustomFailedJobProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 旧部署若未合并含 mysql_main 的 config/database.php，此处从 mysql 复制并补全连接，避免「mysql_main not configured」
        $this->app->booting(function () {
            $connections = config('database.connections', []);
            if (isset($connections['mysql_main']) || !isset($connections['mysql'])) {
                return;
            }
            $main = $connections['mysql'];
            $mainDb = config('tenant.main_database');
            if ($mainDb !== null && $mainDb !== '') {
                $main['database'] = $mainDb;
            }
            config(['database.connections.mysql_main' => $main]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        // 使用自定义的失败作业提供者
        $this->app->singleton('queue.failer', function ($app) {
            return new CustomFailedJobProvider(
                $app['db'],
                $app['config']['queue.failed.database'],
                $app['config']['queue.failed.table']
            );
        });
    }
}
