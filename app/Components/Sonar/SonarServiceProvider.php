<?php
namespace App\Components\Sonar;

use Illuminate\Support\ServiceProvider;

class SonarServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['sonar' => 'App\Components\Sonar\Sonar'], function ($app) {
            return new Sonar();
        });
    }

    public function provides()
    {
        return ['sonar', 'App\Components\Sonar\Sonar'];
    }
}
