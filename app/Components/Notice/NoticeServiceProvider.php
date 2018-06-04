<?php
namespace App\Components\Notice;

use Illuminate\Support\ServiceProvider;

class NoticeServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(['notice' => 'App\Components\Notice\Notice'], function ($app) {
            return new Notice();
        });
    }

    public function provides()
    {
        return ['notice', 'App\Components\Notice\Notice'];
    }
}
