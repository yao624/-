<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Horizon 不是本地必需启动项。
        // 这里不手动实例化/boot HorizonApplicationServiceProvider，避免在依赖未完整时
        // 触发 ServiceProvider 构造器注入失败（例如 unresolvable $app）。
        $this->gate();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (User $user = null) {
            // return in_array($user->email, [
            //     //
            // ]);
            return true;
        });
    }
}
