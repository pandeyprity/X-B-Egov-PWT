<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\Response;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
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

        /**Define for Super Admin Role */
        Gate::define('isSuperAdmin', function ($user) {
            return $user->user_type == 'Admin'
                ? Response::allow()
                : Response::deny('You Must be a Super Administrator');
        });
         // admin gate
         Gate::define('isAdmin', function (User $user) {
            return $user->user_type == 'Admin'
                ? Response::allow()
                : Response::deny('You Must be a Administrator');
        });

        /* define a manager user role */
        Gate::define('isCitizen', function (User $user) {
            return $user->user_type == 'Citizen'
                ? Response::allow()
                : Response::deny('You Must be a Citizen');
        });

        /* define a user role */
        Gate::define('isEmployee', function ($user) {
            return $user->user_type == 'Employee'
                ? Response::allow()
                : Response::deny('You Must be a Employ');
        });
        /**/
        Gate::define('isPsudo', function (User $user) {
            return $user->user_type == 'Psudo'
                ? Response::allow()
                : Response::deny('You Must be a psudo');
        });
    }
}
