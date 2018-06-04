<?php
namespace App\Components\CiJob;

use Illuminate\Support\ServiceProvider;

class CiJobServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['cijob' => 'App\Components\CiJob\CiJob'], function ($app) {
            return new CiJob();
        });
    }

    public function provides()
    {
        return ['cijob', 'App\Components\CiJob\CiJob'];
    }
}
