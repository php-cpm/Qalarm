<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redis;
use Cookie;
use Exception;
use RuntimeException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\JobDone\JobDone;
use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;

use App\Models\RedisModel;
use App\Models\Gaea\OpsHost;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\OpsScripts;
use App\Models\Gaea\OpsScriptsExec;

use App\Jobs\ScriptExecResultJob;

use App\Components\JobDone\StorageAndSyncScript;

class ScriptController extends Controller
{


    /**
        * @brief fetchScripts 获取脚本列表
        * @param $request
        * @return 
     */
    public function fetchScripts(Request $request)
    {/*{{{*/
        $type = $request->input('type', '');
        $name = $request->input('name', '');

        $query = OpsScripts::orderBy('created_at', 'desc');

        if (!empty($type)) {
            $query->where('type', $type);
        }
        if (!empty($name)) {
            $query->where('scriptname', 'LIKE', "%$name%");
        }

        $paginator = new Paginator($request);
        $scripts   = $paginator->runQuery($query);

        return $this->responseList($paginator, $scripts);
    }/*}}}*/

    public function fetchScript(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'id'   => 'required'
        ]);

        $id = $request->input('id');
        $script = OpsScripts::where('id', $id)->first();

        return response()->clientSuccess($script);
    }/*}}}*/

    /**
        * @brief updateScript 添加/编辑脚本
        * @param $request
        * @return 
     */
    public function updateScript(Request $request)
    {/*{{{*/
        // 处理删除动作
        $action = $request->input('action', '');
        if ($action == Constants::REQUEST_TYPE_DELETE) {
            $script = OpsScripts::where('id', $request->input('id'))->first();
            $script->delete();

            return response()->clientSuccess([$script->id]); 
        }

        $this->validate($request, [
            'name'     => 'required',
            'type'     => 'required',
            'function' => 'required',
            'content'  => 'required',
            'owner'    => 'required'
        ]);


        $oldMd5 = '';
        $script = OpsScripts::where('owner', $request->input('owner'))
                            ->where('type',  $request->input('type'))
                            ->where('scriptname',  $request->input('name'))
                            ->first();

        if (is_null($script)) {
            $script = new OpsScripts();
            {
                $script->owner       = $request->input('owner');
                $script->type        = $request->input('type');
                $script->scriptname  = $request->input('name');
                $script->params      = $request->input('params');
                $script->script      = $request->input('content');
                $script->md5         = md5($request->input('content'));
                $script->remark      = $request->input('function');
                $script->admin_id    = Constants::getAdminId();
                if (empty($request->input('dir'))) {
                    $script->scriptdir  = OpsScripts::$scriptType[$script->type]['dir'];
                } else {
                    $script->scriptdir  = $request->input('dir');
                }
                $script->scriptcode  = sprintf('%s%s%s', $script->owner, $script->type, md5($script->scriptname));
            }
        } else {
            $oldMd5              = $script->md5;

            $script->params      = $request->input('params');
            $script->script      = $request->input('content');
            $script->remark      = $request->input('function');
            $script->md5         = md5($request->input('content'));
            $script->admin_id    = Constants::getAdminId();
        }

        // 保存修改，生成文件，并同步到存储机器
        DB::connection('gaea')->beginTransaction();
        try {
            $script->save();
            if ($oldMd5 != $script->md5) {
                $ret = StorageAndSyncScript::storageAndSync($script);
                if ($ret == false) {
                    throw new \Exception();
                }
            }
            DB::connection('gaea')->commit();
        } catch(\Exception $e) {
            DB::connection('gaea')->rollback();
            return response()->clientError(ErrorCodes::ERR_FAILURE, '文件存储或同步失败,请重试');
        }

        return response()->clientSuccess([]);

    }/*}}}*/

    public function fetchScriptTypes(Request $request)
    {/*{{{*/
        $types = [];
        foreach (OpsScripts::$scriptType as $typeCode => $type) {
            $types[] = ['id' =>$typeCode, 'name' => $type['name']];
        }
        return response()->clientSuccess($types);
    }/*}}}*/
    
    /*脚本执行相关*/
    public function fetchScriptsExecs(Request $request)
    {/*{{{*/
        $query = OpsScriptsExec::orderBy('created_at', 'desc');

        $nickname = $request->input('exec_name');
        if (!empty($nickname)) {
            $admin = AdminUser::where('nickname', $nickname)->first();
            if (!is_null($admin)) {
                $ids = [$admin->id];
            }
            $superman = Constants::getSuperMan();
            $ids[] = $superman->id;

            $query->whereIn('admin_id', $ids);
        }
        
        $paginator = new Paginator($request);
        $execHistory = $paginator->runQuery($query);

        return $this->responseList($paginator, $execHistory);
    }/*}}}*/
    
    public function fetchScriptExec(Request $request)
    {/*{{{*/
    }/*}}}*/

    public function updateScriptExec(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'script_id'  => 'required',
            'hostnames'  => 'required',
        ]);

        $validHostnames      = [];
        $invalidHostnames    = [];
        $rawHostnames = explode("\n", $request->input('hostnames'));

        $allHosts = [];
        // 取得所有在线的机器
        $hosts = OpsHost::orderBy('server_name', 'desc')->where('status', OpsHost::HOST_UP)->get();
        foreach ($hosts as $host) {
            if (!empty($host->ip)) {
                $ips = explode('|', $host->ip);
                foreach ($ips as $ip) {
                    $allHosts[$ip] = $host->server_name;
                }
            }
        }
        // 去掉空白字符，把ip转换成hostname
        foreach ($rawHostnames as $host) {
            $host = trim($host);
            $hostname = $this->findHost($host, $allHosts);
            if ($hostname != null) {
                $validHostnames[] = $hostname;
            } else {
                $invalidHostnames[] = $host;
            }
        }


        // 先查询运维平台库中是否存在机器名，然后查找jobdone系统是否存活
        $result = app('jobdone')->doJob(JobDone::API_CHECK_STATUS, [
            'tgt'   => join(',', $validHostnames)
         ], $output);

        $result = $output;
        if (!empty($result['down'])) {
            $invalidHostnames = array_merge($invalidHostnames, $result['down']);
            $validHostnames   = array_diff($validHostnames, $result['down']);
        }
        
        // 主机名排重
        $newValidHostnames = array_unique($validHostnames);
        $repeat = false;
        if (count($validHostnames) != count($newValidHostnames)) {
            $repeat = true;
        }
        
        $ret = [
            'invalid' => join("\n", $invalidHostnames),
            'valid'   => join("\n", (($repeat==true)?$newValidHostnames:$validHostnames))
        ];

        // 如果有非法的主机名，则返回
        if (!empty($invalidHostnames || $repeat == true)) {
            $ret['check'] = false;
            return response()->clientSuccess($ret);
        }

        // 解析参数
        // 多个参数用 | 分隔
        $rawParams = $request->input('params', '');
        $params = [];
        if (!empty($rawParams)) {
            $params = $this->parseParams($rawParams);
        }

        // 开始试执行，先执行一台机器
        $script = OpsScripts::where('id', $request->input('script_id'))->first();
        // FIXME 参数
        $cmd    = app('jobdone')->fetchJobDoneCommand($script, $params);
        $result = app('jobdone')->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => $validHostnames[0],
            'cmd'   => $cmd['cmd']
         ], $output);

        if ($result == false) {
            $ret['check'] = true;
            return response()->clientError(ErrorCodes::ERR_FAILURE, 'jobdone 调用失败');
        }

        $result = $output;

        $scriptExec = new OpsScriptsExec();
        {
            $scriptExec->script_id    = $request->input('script_id');
            $scriptExec->params       = $request->input('params');
            $scriptExec->hostnames    = join(',', $validHostnames);
            $scriptExec->host_number  = count($validHostnames);
            $scriptExec->admin_id     = Constants::getAdminId();
            $scriptExec->jid          = $result['jid'];

            // 如果只有一台，则无需灰度执行
            if ($scriptExec->host_number == 1) {
                $scriptExec->is_try  = OpsScriptsExec::ALL_EXEC;
            } else {
                $scriptExec->is_try  = OpsScriptsExec::TRY_EXEC;
            }
        }
        $scriptExec->save();

        $jobData = MethodUtil::assemblyJobData(['jid' => $scriptExec->jid]);
        $job = (new ScriptExecResultJob($jobData))->delay(1);
        $this->dispatch($job);

        $ret['check'] = true;
        return response()->clientSuccess($ret);

    }/*}}}*/

    /*执行和重做*/
    public function goonScriptExec(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'action' => 'required|in:exec,redo,cancel',
            'exec_id'=> 'required'
        ]);

        $action = $request->input('action');
        $execId = $request->input('exec_id');

        $scriptExec = OpsScriptsExec::where('id', $execId)->first();
        $script = OpsScripts::where('id', $scriptExec->script_id)->first();

        // 解析参数
        $rawParams = $scriptExec->params;
        $params = $this->parseParams($rawParams);

        // FIXME 参数
        $cmd    = app('jobdone')->fetchJobDoneCommand($script, $params);
        $result = app('jobdone')->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => $scriptExec->hostnames,
            'cmd'   => $cmd['cmd']
        ], $output);

        if ($result == false) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, $output);
        }

        $result = $output;

        // 如果为exec则重写当前记录, 如果为redo则生成新任务记录
        if ($action == 'exec') {
            $scriptExec->jid          = $result['jid'];
            $scriptExec->success      = 0;
            $scriptExec->failed       = 0;
            $scriptExec->status       = JobDone::JOB_RUNNING;

            // 重置首次执行标志
            $scriptExec->is_try       = OpsScriptsExec::ALL_EXEC;

            $scriptExec->save();
        } else if ($action == 'redo') {
            $newOne = new OpsScriptsExec();
            {
                $newOne->script_id    = $scriptExec->script_id;
                $newOne->params       = $scriptExec->params;
                $newOne->hostnames    = $scriptExec->hostnames;
                $newOne->host_number  = $scriptExec->host_number;
                $newOne->admin_id     = Constants::getAdminId();
                $newOne->jid          = $result['jid'];
                $newOne->success      = 0;
                $newOne->failed       = 0;
                $newOne->status       = JobDone::JOB_RUNNING;
                $newOne->is_try       = OpsScriptsExec::ALL_EXEC;
            }
            $newOne->save();
        }

        $jobData = MethodUtil::assemblyJobData(['jid' => $result['jid']]);
        $job = (new ScriptExecResultJob($jobData))->delay(1);
        $this->dispatch($job);

        return response()->clientSuccess([]);
    }/*}}}*/

    public function fetchExecResult(Request $request) 
    {/*{{{*/
        $this->validate($request, [
            'id'   => 'required',
            'type' => 'required|in:succ,fail,all'
        ]);

        $scriptExec = OpsScriptsExec::where('id', $request->input('id'))->first();
        if (is_null($scriptExec)) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '任务不存在');
        }

        $result = app('jobdone')->doJob(JobDone::API_RET_QUERY, ['jid' => $scriptExec['jid']], $output);
        $type = $request->input('type');
        if ($result == false) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, $output);
        }

        $result = $output;


        
        // 设置状态描述和机器列表展示样式,给返回值添加字段
        foreach ($result['return'] as $key => $cube) {
            $result['return'][$key]['desc'] = JobDone::$nodeJobStatusDesc[$cube['ret']];
            $result['return'][$key]['display_hosts'] = join("\n", $cube['hosts']);
        }
        
        LogUtil::info('', [$result]);

        $statistics = ['all' => $result['return'], 'succ' => [], 'fail' => []];
        $countStatistics = ['succ' => 0, 'fail' => 0];

        foreach ($result['return'] as $cube) {
            if ($cube['ret'] == JobDone::NODE_JOB_FINISHED) {
                $statistics['succ'][] = $cube;
                foreach ($cube['hosts'] as $h) {
                    ++$countStatistics['succ'];
                }
                continue;
            }
            if ($cube['ret'] == JobDone::NODE_JOB_RUNNING) {
                continue;
            }

            $statistics['fail'][] = $cube;
            foreach ($cube['hosts'] as $h) {
                ++$countStatistics['fail'];
            }
        }
        
        // 主动查询时，同时check任务状态
        if ($result['status'] != $scriptExec->status 
        || $countStatistics['succ'] != $scriptExec->success
        || $countStatistics['fail'] != $scriptExec->failed) {
            $scriptExec->success = $countStatistics['succ'];
            $scriptExec->failed  = $countStatistics['fail'];
            $scriptExec->status  = $result['status'];
            $scriptExec->save();
        }

        // 执行结果按ret排序
        foreach ($statistics[$type] as $key => $value) {
            $tmp[$key] = $value['ret'];
        }

        if (!empty($tmp)) {
            array_multisort($tmp, SORT_ASC, $statistics[$type]);
        }

        return response()->clientSuccess($statistics[$type]);
    }/*}}}*/

    /**
        * @brief findHost 使用主机名或ip查询对于主机名
        * @param $host
        * @param $allHosts
        * @return hostname | null
     */
    private function findHost($host, $allHosts = []) 
    {/*{{{*/
        foreach ($allHosts as $ip => $hostname) {
            if ($host == $ip) {
                return $hostname;
            }
            if ($host == $hostname) {
                return $hostname;
            }
        }

        return null;
    }/*}}}*/
    
    // 多个参数用 | 分隔
    private function parseParams($rawParams) 
    {/*{{{*/
        $params = [];
        if (!empty($rawParams)) {
            $collection = explode('|', $rawParams);
            foreach ($collection as $c) {
                if (empty($c)) continue;
                list($name, $value) = explode('=', $c);
                $params[$name] = $value;
            }
        }

        return $params;
    }/*}}}*/

    protected function responseList($paginator, $collection, $callee='export')
    {/*{{{*/
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }/*}}}*/
}
