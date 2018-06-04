<?php
namespace App\Components\Nginx;

use Illuminate\Support\ServiceProvider;

class NginxServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['nginx1' => 'App\Components\Nginx\Nginx'], function ($app) {
            return new Nginx();
        });
    }

    public function provides()
    {
        return ['nginx1', 'App\Components\Nginx\Nginx'];
    }
}
