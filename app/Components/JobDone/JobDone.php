<?php
namespace App\Components\JobDone;

use Log;
use Redis;

use Exception;
use RuntimeException;
use App\Exceptions\ApiException;

use App\Components\Utils\CurlUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\Constants;
use App\Components\Utils\LogUtil;
use App\Components\Utils\ErrorCodes;

use App\Models\Gaea\OpsScripts;
use App\Models\Gaea\OpsScriptsExec;

class JobDone
{
    // 指定延缓提供者加载
    protected $defer = true;

    // 声明常量 /*{{{*/
    const SECRET = 'ab37655347122540857b44950fca0ba3';
    const FROM   = 'gaea';
    
    const JOBDONE_ENV_TEST       = 'http://test.jobdone.corp.ttyongche.com/';
    const JOBDONE_ENV_PRODUCTION = 'http://jobdone.corp.ttyongche.com/';

    const DEFAULT_TIMEOUT       = 180;
    const WRAPPER_SCRIPT        = 'cd /home/t/system/gaea-client;perl saltTask.pl';
    const COMMAND_SEP  ='_';

    // API 种类
    const API_CMD_EXEC          =  1;
    const API_RET_QUERY         =  2;
    const API_GROUP_EXEC        =  3;
    const API_GROUP_QUERY       =  4;
    const API_JOB_CONTROL       =  5;
    const API_CHECK_STATUS      =  6;
    const API_DOWN_HOST         =  7;

    // 调用方式
    const EXEC_ASYNC            = 0;
    const EXEC_SYNC             = 1;

    // 机器状态
    const HOST_DOWN             = 0;
    const HOST_UP               = 1;

    // JOB_STATUS={'running':'1','finished':'2', 'canceled':'3','unknown':'20'} 
    // 任务状态
    const JOB_RUNNING           = 1;
    const JOB_FINISHED          = 2;
    const JOB_CANCELED          = 3;
    const JOB_TIMEOUT           = 4;
    const JOB_UNKNOWN           = 20;

    public static $jobStatusDesc = [
        self::JOB_RUNNING   => '执行中',
        self::JOB_FINISHED  => '完成',
        self::JOB_CANCELED  => '取消',
        self::JOB_TIMEOUT   => '超时',
        self::JOB_UNKNOWN   => '未知',
    ];

    // NODE_STATUS={'job_executing':'1', 'job_done':'2','job_cancelled':'3','job_send':'4','job_recv':'5','job_killed':'6','job_timeout':'7','unknown':'20'}   
    // 每台机器的执行状态
    const NODE_JOB_RUNNING      = 1;
    const NODE_JOB_FINISHED     = 2;
    const NODE_JOB_CANCELED     = 3;
    const NODE_JOB_SEND         = 4;
    const NODE_JOB_RECV         = 5;
    const NODE_JOB_KILLED       = 6;
    const NODE_JOB_TIMEOUT      = 7;
    const NODE_JOB_UNKNOWN      = 20;

    public static $nodeJobStatusDesc = [
        self::NODE_JOB_RUNNING     => '执行中',
        self::NODE_JOB_FINISHED    => '完成',
        self::NODE_JOB_CANCELED    => '取消',
        self::NODE_JOB_SEND        => '已发送',
        self::NODE_JOB_RECV        => '已接收',
        self::NODE_JOB_KILLED      => '已杀死',
        self::NODE_JOB_TIMEOUT     => '超时',
        self::NODE_JOB_UNKNOWN     => '未知',
    ];

    const COMMAND_GAEA          = 1;
    /*}}}*/

    protected $jobDoneApis = [ /*{{{*/
        self::API_CMD_EXEC      => [
            'url'     => 'api/cmd_execute',
            'params'  => [
                'tgt'       => '',
                'cmd'       => '',
                'sync'      => 0,
                'serial'    => 0,
                'timeout'   => 180,
            ],
        ],
        self::API_RET_QUERY     => [
            'url'     => 'api/return_query',
            'params'  => [
                'jid'       => '',
            ],
        ],
        self::API_GROUP_EXEC    => [
            'url'     => 'api/group_run',
            'params'  => [
                'tasks'     => '',
            ],
        ],
        self::API_GROUP_QUERY   => [
            'url'     => 'api/group_query',
            'params'  => [
                'jid'       => '',
            ],
        ],
        self::API_JOB_CONTROL   => [
            'url'     => 'api/job_control',
            'params'  => [
                'jid'       => '',
                'act'       => '',
            ],
        ],
        self::API_CHECK_STATUS   => [
            'url'     => 'api/check_status',
            'params'  => [
                'tgt'       => '',
            ],
        ],
        self::API_DOWN_HOST     => [
            'url'     => 'api/down_status',
            'params'  => [
            ],
        ],

    ];/*}}}*/

    /**
        * @brief fetchJobDoneCommand 
        *
        * @param $script  OpsScripts对象 
        * @param $params 参数数组
        * @param $callstyle  调用方式,默认异步
        * @param $timeout 执行的超时时间
        *
        * @return  ['cmd' => '', 'target_host' => '']
     */
    public function fetchJobDoneCommand($script, $params = [], $callstyle = self::EXEC_ASYNC, $timeout = self::DEFAULT_TIMEOUT) 
    {/*{{{*/
        // 参数顺序按commands 中排列
        $values = [];
        $templateParams = explode(',', $script->params);
        foreach ($templateParams as $param) {
            if (isset($params[$param])) {
                $values[] = "'". $params[$param] ."'";
            } else {
                $values[] = "' '";
            }
        }
        $trueParams = join(' ', $values);

        $cmd = sprintf('%s "%s" "%s" "%s" "%s" %s %s %s', self::WRAPPER_SCRIPT,
            $script->owner,
            $script->scriptdir,
            $script->scriptname,
            base64_encode($trueParams),
            $timeout,
            $script->md5,
            $callstyle
        );
        return ['cmd' => $cmd, 'target_host' => $script->targethost];
    }/*}}}*/

    /**
     * @brief doJob  调用jobdone系统接口
     * @param $function
     * @param $params
     * @param $output
     * @return false | true
     */
    public function doJob($function, $params, &$output = [], $jobDoneUrl = '', $timeout = self::DEFAULT_TIMEOUT)
    {/*{{{*/
        $func = $this->jobDoneApis[$function];

        $url  = $jobDoneUrl; 
        if ($url == '') {
            $url  = $this->getServiceHost();
        }
        $url  .= $func['url'];

        LogUtil::info('JobDone request website', ['website' => $url]);
        $params = array_merge(['auth'  => self::SECRET], $func['params'], $params);
        
        LogUtil::info('Before JobDone request', ['params' => $params]);

        $curl = new CurlUtil($url);
        //$curl->setTimeout(30);
        $curl->setTimeout($timeout);
        $curl->setPostFields($params);
        $result = $curl->execute();
        
        LogUtil::info('After JobDone request', ['params' => $params, 'result' => $result]);

        if ($result == null || $result['http_code'] != 200) {
            // log
            $errmsg = "\n请求失败\n";
            $errmsg .= json_encode($result);
            LogUtil::error($errmsg, ['result' => $result]);
            $output = $errmsg;
            return false;
        }

        if ($result['errno'] != 0) {
            $errmsg = "\n请求失败\n";
            $errmsg .= json_encode($result);
            LogUtil::error($errmsg, ['result' => $result]);
            $output = $errmsg;
            return false;
        }

        $data = json_decode($result['data'], true);

        if ($data['code'] != 0) {
            $errmsg = 'JobDone内部错误,错误信息：'.$data['data'];
            LogUtil::error($errmsg, ['result' => $result]);
            $output = $errmsg;
            return false;
        }

        $output = $data['data'];
        return true;
    }/*}}}*/


    /**
     * @brief launchGaeaScriptExec 执行一次自动的脚本执行任务
     * @param $script
     * @param $hosts
     * @param $params
     * @return  jid
     */
    public function launchGaeaScriptExec($script, $hosts = [], $params = [])
    {/*{{{*/
        // 生成参数串
        $paramsString = '';
        foreach ($params as $key => $value) {
            $tmp = $key.'='.$value;
            $paramsString .= $tmp;
            $paramsString .= '|';
        }

        $command = app('jobdone')->fetchJobDoneCommand($script, $params);
        $result  = app('jobdone')->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => join(',', $hosts),
            'cmd'   => $command['cmd']
        ], $output);

        if ($result == false) {
            throw new ApiException($output);
        }

        $scriptExec = new OpsScriptsExec();
        {
            $scriptExec->script_id   = $script->id;
            $scriptExec->params      = $paramsString;
            $scriptExec->hostnames   = join(',', $hosts);
            $scriptExec->host_number = count($hosts);
            $superman = Constants::getSuperMan();
            $scriptExec->admin_id    = $superman->id;
            $scriptExec->jid         = $output['jid'];
            $scriptExec->is_try      = OpsScriptsExec::ALL_EXEC;
        }
        $scriptExec->save();

        return $output['jid'];
    }/*}}}*/

    // return array
    public function applyVPN($params)
    {/*{{{*/

        $ret = ['errno' => 0, 'errmgs' => ''];
        $script = OpsScripts::where('owner', OpsScripts::OWNER_OPS)
            ->where('scriptdir', 'users')
            ->where('scriptname', 'vpnmail.sh')
            ->first();

        $command = $this->fetchJobDoneCommand($script, $params);

        $result = $this->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => $command['target_host'],
            'cmd'   => $command['cmd'],
            'sync'  => self::EXEC_SYNC,
        ], $output);

        if ($result) {
            if ($output['status'] != self::JOB_FINISHED) {
                $ret['errno']  = ErrorCodes::ERR_FAILURE;
                $ret['errmsg'] = '任务执行状态错误';
            } else {
                if ($output['return'][0]['ret'] != self::NODE_JOB_FINISHED) {
                    $ret['errno']  = ErrorCodes::ERR_FAILURE;
                    $ret['errmsg'] = '节点上执行任务失败';
                }
            }
        } else {
            $ret['errno']  = ErrorCodes::ERR_FAILURE;
            $ret['errmsg'] = $output;
        }

        return $ret;
    }/*}}}*/

    /**
     * @brief jobdoneGoGoGo 对外的统一调用接口
     *
     * @param $scriptOwner
     * @param $scriptDir
     * @param $scriptName
     * @param $tgt
     * @param $params
     * @param $callstyle
     *
     * @return 
     */
    public function jobdoneGoGoGo($scriptOwner, $scriptDir, $scriptName, $tgt, $params, $timeout = self::DEFAULT_TIMEOUT, $callstyle = self::EXEC_ASYNC, $jobDoneUrl = '')
    {/*{{{*/
        $ret = ['errno' => 0, 'errmgs' => ''];
        $script = OpsScripts::where('owner', $scriptOwner)
            ->where('scriptdir', $scriptDir)
            ->where('scriptname', $scriptName)
            ->first();

        if ($script == null) {
            $ret['errno']  = ErrorCodes::ERR_FAILURE;
            $ret['errmsg'] = '脚本不存在';
            return $ret;
        }

        $command = $this->fetchJobDoneCommand($script, $params, $callstyle, $timeout);

        // 如果target_host存在，则优先使用
        $result = $this->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => !empty($command['target_host'])?$command['target_host']:$tgt,
            'cmd'   => $command['cmd'],
            'sync'  => $callstyle
        ], $output, $jobDoneUrl, $timeout);

        if ($result) {
            $ret['errno']  = ErrorCodes::ERR_SUCCESS;
            $ret['data'] = $output;
        } else {
            $ret['errno']  = ErrorCodes::ERR_FAILURE;
            $ret['errmsg'] = $output;
        }

        return $ret;
    }/*}}}*/

    public function fetchShadowByPasswd($passwd)
    {/*{{{*/
        $params['passwd'] = $passwd;
        
        $script = OpsScripts::where('owner', OpsScripts::OWNER_OPS)
            ->where('scriptdir', 'users')
            ->where('scriptname', 'takekey.sh')
            ->first();

        $command = $this->fetchJobDoneCommand($script, $params);

        $result = $this->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => $command['target_host'],
            'cmd'   => $command['cmd'],
            'sync'  => self::EXEC_SYNC,
        ], $output);

        return $this->parseSingleReturn($result, $output);
    }/*}}}*/

    private function parseSingleReturn($result, $output)
    {/*{{{*/
        $ret = ['errno' => 0, 'errmgs' => ''];

        if ($result) {
            if ($output['status'] != self::JOB_FINISHED) {
                $ret['errno']  = ErrorCodes::ERR_FAILURE;
                $ret['errmsg'] = '任务执行状态错误';
            } else {
                if ($output['return'][0]['ret'] != self::NODE_JOB_FINISHED) {
                    $ret['errno']  = ErrorCodes::ERR_FAILURE;
                    $ret['errmsg'] = '节点上执行任务失败';
                } else {
                    list($code, $msg) = $this->parseNodeRet($output['return'][0]['output']); 
                    $ret['errmsg'] = $msg;
                }
            }
        } else {
            $ret['errno']  = ErrorCodes::ERR_FAILURE;
            $ret['errmsg'] = $output;
        }

        return $ret;
    }/*}}}*/

    private function parseNodeRet($output)
    {/*{{{*/
        $msg = '';
        $code = 0;
        $lines = explode("\n", $output);
        
        do {
            $last = array_pop($lines);
        }while(empty($last));
        list($code, $msg) = explode('|', $last);

        return [$code, $msg];
    }/*}}}*/

    private function getServiceHost()
    {/*{{{*/
        if (app()->environment('local')) {
            return self::JOBDONE_ENV_TEST;
        } else {
            return self::JOBDONE_ENV_PRODUCTION;
        }
    }/*}}}*/

    //public function deployProject($params)
    //{[>{{{<]

        ////$ret = ['errno' => 0, 'errmgs' => ''];
        ////$script = OpsScripts::where('owner', OpsScripts::OWNER_GAEA)
            ////->where('scriptdir', 'users')
            ////->where('scriptname', 'test.sh')
            ////->first();

        ////$command = $this->fetchJobDoneCommand($script, $params);
        ////dd($command);
        
        //$params = [
            //'username' => 'ceshi',
            //'project_package' => 'http://www.a.com/package.tar.gz',
            //'deploy_dir' => '/home/t/tsys/xxx',
            //'deploy_after_sh' => 'echo 123123'
        //];

        //$scriptId = 6;
        //$hostIp = 'dev01v.corp.ttyc.com';

        //$script = OpsScripts::where('id', '=', $scriptId)->first();
        //$cmd    = app('jobdone')->fetchJobDoneCommand($script, $params);
        ////dd($cmd);
        //$result = app('jobdone')->doJob(JobDone::API_CMD_EXEC, [
            //'tgt'   => $hostIp,
            //'cmd'   => $cmd['cmd'],
            //'sync'  => self::EXEC_SYNC
         //], $output);
        
        //if ($result) {
            //if ($output['status'] != self::JOB_FINISHED) {
                //$ret['errno']  = ErrorCodes::ERR_FAILURE;
                //$ret['errmsg'] = '任务执行状态错误';
            //} else {
                //if ($output['return'][0]['ret'] != self::NODE_JOB_FINISHED) {
                    //$ret['errno']  = ErrorCodes::ERR_FAILURE;
                    //$ret['errmsg'] = '节点上执行任务失败';
                //} else {
                    //$ret['errmsg'] = $output;
                //}
            //}
        //} else {
            //$ret['errno']  = ErrorCodes::ERR_FAILURE;
            //$ret['errmsg'] = $output;
        //}

        //dd($ret);
        //return $ret;
    //}[>}}}<]

}
