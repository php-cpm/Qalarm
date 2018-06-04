<?php
namespace App\Components\RealtimePush;

use Illuminate\Support\ServiceProvider;

class RealtimePushServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['realtimePush' => 'App\Components\RealtimePush\RealtimePush'], function ($app) {
            return new RealtimePush();
        });
    }

    public function provides()
    {
        return ['realtimePush', 'App\Components\RealtimePush\RealtimePush'];
    }
}
