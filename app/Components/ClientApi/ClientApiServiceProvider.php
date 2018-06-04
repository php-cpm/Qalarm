<?php
namespace App\Components\ClientApi;

use App\Components\ClientApi\Request as ClientRequest;
use App\Components\ClientApi\Response as ClientResponse;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class ClientApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('App\Components\ClientApi\Request', function ($app) {
            return new ClientRequest();
        });
    }

    public function boot(ResponseFactory $factory)
    {
        // Register response macro
        $factory->macro('clientSuccess', function ($data=[], $errmsg = '') {
            $newData = array();
            $newData['errno'] = 0;
            $newData['errmsg'] = $errmsg;
            $newData['data'] = $data;
            return new ClientResponse($newData);
        });

        // 万达数据格式
        $factory->macro('clientWdSuccess', function ($data=[], $errmsg = '') {
            $newData = array();
            $newData['status'] = 200;
            $newData['message'] = $errmsg;
            $newData['data'] = $data;
            return new ClientResponse($newData);
        });

        $factory->macro('clientError', function ($code, $errmsg, $status=200) {
            $data = [
                'errno'  => $code,
                'errmsg' => $errmsg,
                'data'   => [],
            ];

            return new ClientResponse($data, $status);
        });
    }
}
