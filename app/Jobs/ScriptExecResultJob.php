<?php
namespace App\Jobs;

use Log;
use Redis;


use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Util\TimeUtil;
use App\Components\Utils\MethodUtil;

use App\Components\JobDone\JobDone;
use App\Models\Gaea\OpsScriptsExec;
class ScriptExecResultJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {/*{{{*/
        $scriptExec = $this->object;
        $result = app('jobdone')->doJob(JobDone::API_RET_QUERY, ['jid' => $scriptExec['jid']], $output);

        if ($result == false) {
            return false;
        }

        $result = $output;
        $statistics = ['succ' => 0, 'fail' => 0];

        foreach ($result['return'] as $cube) {
            if ($cube['ret'] == JobDone::NODE_JOB_FINISHED) {
                foreach ($cube['hosts'] as $host) {
                    ++$statistics['succ'];
                }
                continue;
            }
            if ($cube['ret'] == JobDone::NODE_JOB_RUNNING) {
                continue;
            }
            
            foreach ($cube['hosts'] as $host) {
                ++$statistics['fail'];
            }
        }

        LogUtil::info('', [$result, $statistics]);
        // 把任务重新放回队列
        if ($result['status'] == JobDone::JOB_RUNNING) {

            $tries = 0;
            if (isset($scriptExec['tries'])) {
                $tries = $scriptExec['tries'];

            } else {
                $scriptExec['succ'] = 0;
                $scriptExec['fail'] = 0;
            }
            ++$tries;

            $data = ['jid' => $scriptExec['jid'], 'tries' =>$tries];
            $data = array_merge($data, $statistics);

            $jobData = MethodUtil::assemblyJobData($data);
            $job = (new ScriptExecResultJob($jobData))->delay(pow(2, $tries));
            $this->dispatch($job);  

            // 如果结果没有变化，则不进行下面的数据更新，直接退出
            if (($statistics['succ'] == $scriptExec['succ']) && ($statistics['fail'] == $scriptExec['fail'])) {
                return true;
            }
        }

        $exec = OpsScriptsExec::where('jid', $scriptExec['jid'])->first();
        $exec->status   = $result['status'];
        $exec->success  = $statistics['succ'];
        $exec->failed   = $statistics['fail'];

        $exec->save();

        return true;
    }/*}}}*/
}
