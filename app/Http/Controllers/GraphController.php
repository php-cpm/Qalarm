<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use Illuminate\Support\Facades\Redis;
use Exception;
use RuntimeException;
use Carbon\Carbon;

use App\Http\Controllers\Controller;


use App\Models\Qalarm\Monitor;

class GraphController extends Controller
{
    public function checkauth(Request $request)
    {
        return response()->clientSuccess(['authorization' => 1]);
    }


    public function index(Request $requestd)
    {
        $historyProjects = Redis::hGetAll(env('QALARM_HISTORY_PROJECTS_KEY'));
        $needShow = [];

        $timestamp = time() * 1000;
        foreach ($historyProjects as $project => $lasttime) {
            if (($timestamp - $lasttime) <= 10*60*1000) {
                $needShow[] = $project;
            }
        }

        $points = [];
        foreach ($needShow as $project) {
            $projectKey = 'history_'.$project;
            $historys = Redis::lrange($projectKey, 0, 200);
            sort($historys);
            $dataitems = [];
            foreach ($historys as $item) {
                list($itemtime, $timecount) = explode(':{', $item);
                if ($itemtime > $timestamp || $itemtime < ($timestamp - 10*60*1000)) {
                   continue;
                }
                $dataitems[$itemtime] = json_decode("{".$timecount, true);
            }
            $points[$project] = $dataitems;
        }

        return response()->clientSuccess(['points' => $points]);
    }

    public  function history(Request $request)
    {
        $historyProjects = Redis::hGetAll(env('QALARM_HISTORY_PROJECTS_KEY'));
        $needShow = [];

        $timestamp = time() * 1000;
        foreach ($historyProjects as $project => $lasttime) {
            if (($timestamp - $lasttime) <= 7*24*3600*1000) { // 7 days
            $needShow[] = $project;
           }
        }

        $points = [];
        foreach ($needShow as $project) {
            $projectKey = 'ex_history_'.$project;
            $historys = Redis::lrange($projectKey, 0, 12*24*7);
            $tmpPoints = [];
            foreach ($historys as $onePoint) {
                $tmp = json_decode($onePoint, true);
                foreach ($tmp as $pointTime => $pointContent) {
                    $points[$project][$pointTime] = $pointContent;
                }
            }
        }

        return response()->clientSuccess(['points' => $points]);
    }
    public  function detail(Request $request)
    {
        $this->validate($request, [
            'project_name' => 'required'
        ]);

        $projectName = $request->input('project_name');
        $projectKey  = 'history_'.$projectName;
        $timestamp = time() * 1000;

        $historys = Redis::lrange($projectKey, 0, 200);
        sort($historys);

        $dataitems = [];
        foreach ($historys as $itemtime => $item) {
            list($itemtime, $timecount) = explode(':{', $item);
            if ($itemtime > $timestamp || $itemtime < ($timestamp - 10*60*1000)) {
                continue;
            }
            $dataitems[$itemtime] = json_decode( "{". $timecount, true);
        }

        return response()->clientSuccess(['pname' => $projectName, 'points' => $dataitems]);
    }

    public  function message(Request $request)
    {
        $this->validate($request, [
            'project' => 'required',
            'module'  => 'required'
        ]);

        $projectName = $request->input('project');
        $moduleName  = $request->input('module');
        $currentPage = $request->input('page', 1);
        $limit = 200;
        $redisKey = join('|', ['module', $projectName, $moduleName]);

        $totalCount = Redis::llen($redisKey);
        $totalPage  = $totalCount / $limit + 1;
        $moduleData = Redis::lrange($redisKey, ($currentPage-1)*$limit, $currentPage*$limit);
        $data = [];
        foreach ($moduleData as $item) {
            $raw = unserialize($item);
            $data[] = [
                'code'      =>  $raw['code'],
                'module'    =>  $raw['module'],
                'time'      =>  Carbon::createFromTimestamp($raw['time'])->toDateTimeString(),
                'server_ip' =>  isset($raw['server_ip'])?$raw['server_ip']:'',
                'client_ip' =>  isset($raw['client_ip'])?$raw['client_ip']:'',
                'env'       =>  $raw['env'],
                'script'    =>  isset($raw['script'])?$raw['script']:'',
                'message'   =>  isset($raw['message'])?$raw['message']:'',
                'isJem'     => 1,
            ];

        }

        return response()->clientSuccess(['items' =>$data, 'page' => ['total' => 120, 'index' => 12]]);
    }
}

