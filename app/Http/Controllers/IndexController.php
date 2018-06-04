<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use View;

class IndexController extends Controller
{
    public function index()
    {
        return View::make('angular');
    }

    public function harview(Request $request)
    {
        $id = $request->input('id', '');
        $redisWaterfallKey = 'phoenix_waterfall_hashlist';
        $log = Redis::hget($redisWaterfallKey, $id);
        if (is_null($log)) {
            $log = '{}';
        }
        return View::make('harview')->with('log',$log);
    }

    public function harviewFile(Request $request)
    {
        $file = public_path('1.har');
//        $headers = array(
//            'Content-Type: application/pdf',
//        );

        return response()->download($file, 'filename.pdf');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function pong()
    {
        return response()->clientSuccess([
            'pong' => microtime(true),
            ]);
    }

    public function users()
    {
        return response()->clientSuccess(
            [
                '0'=>['name'=>'chenfei', 'age'=>27, 'sex'=>1],
                '1'=>['name'=>'chenfei', 'age'=>27, 'sex'=>1],
                '2'=>['name'=>'chenfei', 'age'=>27, 'sex'=>1],
            ]
        );
    }

}
