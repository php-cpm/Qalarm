<?php
namespace App\Components\JobDone;

use Illuminate\Support\ServiceProvider;

class JobDoneServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['jobdone' => 'App\Components\JobDone\JobDone'], function ($app) {
            return new JobDone();
        });
    }

    public function provides()
    {
        return ['jobdone', 'App\Components\JobDone\JobDone'];
    }
}
