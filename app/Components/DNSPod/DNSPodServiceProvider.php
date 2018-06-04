<?php
namespace App\Components\DNSPod;

use Illuminate\Support\ServiceProvider;

class DNSPodServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['dnspod' => 'App\Components\DNSPod\DNSPod'], function ($app) {
            return new DNSPod();
        });
    }

    public function provides()
    {
        return ['dnspod', 'App\Components\DNSPod\DNSPod'];
    }
}
