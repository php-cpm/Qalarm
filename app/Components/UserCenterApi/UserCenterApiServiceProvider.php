<?php

namespace App\Components\UserCenterApi;
//namespace App\Components\UserCenterApi;

use App\Components\UserCenterApi\UserCenter as UserCenter;
use Illuminate\Support\ServiceProvider;

class UserCenterApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('UserCenterApiServer', function ($app) {
            return new UserCenter();
        });
    }
}
