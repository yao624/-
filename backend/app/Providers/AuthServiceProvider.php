<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\CronJob;
use App\Models\FbAdAccount;
use App\Models\Rule;
use App\Policies\CronJobPolicy;
use App\Policies\FbAdAccountPolicy;
use App\Policies\RulePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        FbAdAccount::class => FbAdAccountPolicy::class,
        Rule::class => RulePolicy::class,
        CronJob::class => CronJobPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
