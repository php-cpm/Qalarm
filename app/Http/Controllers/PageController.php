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

class PageController extends Controller
{
    private  function getProperColorValue($rank)
    {
        $s = $rank / 100;
        if ($s < 1) return 1;
        if ($s > 80) return 80;
        return (int)$s;
    }
    public function index(Request $requestd)
    {
        $redisListKey   = env('PHOENIX_METRIC_HASH_NAME');
        $redisMetricKey = env('PHOENIX_METRIC_QUEUE_NAME');

        $projects = Redis::hkeys($redisListKey);
        $data = [];
        foreach ($projects as $project) {
            $key = $redisMetricKey.'_'.$project;
            $list = Redis::hgetall($key);
            foreach ($list as $key => $speed) {
                $data[$project][$key] = $speed;
            }
        }

        $points = [];
        foreach ($data as $project => $speeds) {
            $item = [
                'id' => $project,
                'name' => $project
            ];
            $points[] = $item;
            foreach ($speeds as $url => $speed) {
                $child['parent'] = $project;
                $child['name']   = sprintf("%s", $url);
                $child['colorValue']  = $this->getProperColorValue($speed);
                $child['value']  = $this->getProperColorValue($speed);
                $child['value2']  = (int) $speed;
                $points[] = $child;
            }
        }
        sort($points);

        return response()->clientSuccess(['points' => $points]);
    }

    public function pagelist(Request $request)
    {
        $this->validate($request, [
            'project'   => 'required',
            'module'    => 'required',
        ]);

        $project = $request->input('project');
        $module  = $request->input('module');

        $redisMetricKey = env('PHOENIX_METRIC_QUEUE_NAME');
        $lkey = $redisMetricKey.'_'.$project .'_'.$module;


        $returns = [];
        $list = Redis::lrange($lkey, 0, 10);
        foreach ($list as $raw) {
            $returns[] = json_decode($raw, true);
        }

        return response()->clientSuccess(['results' => $returns]);
    }

}

