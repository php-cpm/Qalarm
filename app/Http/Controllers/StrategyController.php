<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Exception;
use RuntimeException;

use App\Http\Requests;
use App\Smarty;
use App\Http\Controllers\Controller;


use App\Models\Qalarm\Monitor;

class StrategyController extends Controller
{
    public function checkauth(Request $request, Smarty $smarty)
    {
        $smarty->assign('name', 'chenfei');
        $monitor = new Monitor();
        {
            $monitor->username = 'chenfei60';
            $monitor->real_name = '陈飞';
            $monitor->mail = 'chenfei60@wanda.cn';
            $monitor->mobile = '13658364971';
            $monitor->status = 1;
        }
        dd($monitor->save());

        return $smarty->display('hello.tpl');
    }


    public function index(Request $request, Smarty $smarty)
    {
        $smarty->assign('name', 'chenfei');
        $smarty->assign('points', '{}');

        return $smarty->display('graph/graph.tpl');
    }
}

