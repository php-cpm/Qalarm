<?php

namespace App\Components\AccountCenterApi;

use App\Components\AccountCenterApi\AccountCenter as AccountCenter;
use Illuminate\Support\ServiceProvider;

class AccountCenterApiServiceProvider extends ServiceProvider
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
        $this->app->singleton('AccountCenterApiServer', function ($app) {
            return new AccountCenter();
        });
    }
}
