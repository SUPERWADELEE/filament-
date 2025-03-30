<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\User;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // 註冊角色權限
        Gate::define('isDoctor', function (User $user) {
            return $user->role === 'doctor';
        });

        Gate::define('isPatient', function (User $user) {
            return $user->role === 'patient';
        });

        // 註冊策略
        Gate::policy(Event::class, EventPolicy::class);
    }
}
