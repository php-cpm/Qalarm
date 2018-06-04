<?php

namespace App\Http\Controllers;

use App\Models\Qalarm\Module;
use App\Models\Qalarm\Strategy;
use Illuminate\Http\Request;
use DB;
use Exception;
use RuntimeException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Qalarm\Project;
use App\Models\Qalarm\Message;
use App\Models\Qalarm\Alarm;
use App\Models\Qalarm\Monitor;
use App\Components\Utils\Paginator;

class ProjectController extends Controller
{
    public function update(Request $request)
    {
        $this->validate($request, [
            'project'   => 'required',
            'manager'         => 'required',
            'strategy'       => 'required',
            'status'         => 'required'
        ]);

        $project = Project::where('name', $request->input('project'))->first();

        if (is_null($project)) {
            $project = new Project();
        }
        {
            $project->name = $request->input('project');
            $project->manager = $request->input('manager');
            $project->monitor = join(',', $request->input('monitor'));
            $project->strategy_id = $request->input('strategy');
            $project->status = $request->input('status');
            $project->test_graph_status = $request->input('testGraphStatus');
            $project->test_alarm_status = $request->input('testAlarmStatus');
        }
        $result = $project->save();
        return response()->clientSuccess(['id' => $project->id], '添加成功');
    }

    public function index(Request $request)
    {
        $projects = Project::all();
        $callee = 'export';
        $projects = $projects->map(function ($item, $key) use ($callee) {
            return call_user_func([$item, $callee]);
        });
        return response()->clientSuccess(['results' => $projects]);
    }

    public function option(Request $request)
    {
        $projects = Project::all();
        $callee = 'export';
        $projects = $projects->map(function($item, $key) use ($callee) {
            return call_user_func([$item, $callee]);
        });
        $monitors = Monitor::orderBy('username')->get();
        $rawStrategys = Strategy::orderBy('param3')->get();
        $strategys = [];
        foreach ($rawStrategys as $s) {
            $tmp['id'] = $s->id;
            $tmp['desc'] = sprintf("连续(%s)次(%s)分钟内(%s)次 生效时间:%s点->%s点", $s->param1, $s->param2, $s->param3, $s->valid_start, $s->valid_end);
            $strategys[] = $tmp;
        }

        return response()->clientSuccess([
            'monitors' =>  $monitors,
            'strategys'=> $strategys
        ]);
    }

    public function alarmHistory(Request $request)
    {
        $this->validate($request, [
            'project'   => 'required',
        ]);

        $query = Alarm::where('project', $request->input('project'))->orderBy('time', 'desc');

       // if (!emtpy($request->input('module', ''))) {
       // }

        $paginator = new Paginator($request);
        $scripts   = $paginator->runQuery($query);

        return $this->responseList($paginator, $scripts);
    }

    public function subModule(Request $request)
    {
        $this->validate($request, [
            'project_name' => 'required',
        ]);

        $projectInstance = Project::where('name', $request->input('project_name'))->first();
        if (is_null($projectInstance)) {
            return true;
        }

        $query = Module::where('project_id', $projectInstance->id)->groupBy('module');
        $collections = $query->get();

        return $this->responseListNoPage($collections);
    }

    public function updateSubModuel(Request $request)
    {
        $this->validate($request, [
            'project_id'        => 'required',
            'module'         => 'required',
        ]);

        $subModule = Module::where('project_id', $request->input('project_id'))
            ->where('module', $request->input('module'))
            ->first();

        if (is_null($subModule)) {
            $subModule = new Module();
        }

        $subModule->project_id = $request->input('project_id');
        $subModule->module = $request->input('module');
        if (!empty($request->input('monitor'))) {
            $subModule->monitors = join(',', $request->input('monitor'));
        } else {
            $subModule->monitors = "";
        }
        $subModule->strategy_id = $request->input('strategy');
        $subModule->status = $request->input('status');
        $subModule->save();

        return response()->clientSuccess(['id' => $subModule->id], '修改成功');
    }

    public function messageHistory(Request $request)
    {
        $this->validate($request, [
            'project'   => 'required',
        ]);

        $query = Message::where('project', $request->input('project'))->orderBy('time', 'desc');
        if (!empty($request->input('module', ''))) {
            $query->where('module', $request->input('module'));
        }

        $paginator = new Paginator($request);
        $scripts   = $paginator->runQuery($query);

        return $this->responseList($paginator, $scripts);
    }

    protected function responseList($paginator, $collection, $callee='export')
    {
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }

    protected function responseListNoPage($collection, $callee='export')
    {
        return response()->clientSuccess([
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }
}

