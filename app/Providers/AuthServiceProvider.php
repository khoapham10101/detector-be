<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
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
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user) {

            if ($user && $user->roles) {
                foreach ($user->roles as $role) {
                    if (!$role->permissions) continue;

                    foreach ($role->permissions as $permssion) {
                        // Permission exists will pass middleware
                        Gate::define($permssion->action, function (){
                            return true;
                        });
                    }
                }
            }
        });
    }
}
