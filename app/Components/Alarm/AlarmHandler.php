<?php

namespace App\Components\Alarm;


use App\Models\Qalarm\Project;
use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Components\Alarm\RedisCounter;
use App\Components\Alarm\Alertor;
use App\Components\Alarm\MysqlAlertor;
use App\Components\Notice\Sms;
use App\Jobs\NoticeBySmsJob;
use App\Jobs\RecordMessageJob;
use App\Jobs\RecordAalrmJob;
use App\Components\Utils\HttpUtil;
use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class AlarmHandler
{
    use DispatchesJobs;

    const ENV_SIT     = 'sit';
    const ENV_TEST    = 'test';
    const ENV_PRE     = 'pre';
    const ENV_PROD    = 'prod';

    const COUNTER_INC = 'inc';
    const COUNTER_SET = 'set';

    const DATA_COUNT  = 'c';
    const DATA_MESSAGE= 'm';

    const MESSAGE_MAX_OUT_OF_TIME = 60;  // 60秒之前的数据全部删除   

    public function handle($payload)
    {/*{{{*/
        $timestamp = isset($payload['t']) ? $payload['t'] : $payload['time'];
        if ((time() - $timestamp) > self::MESSAGE_MAX_OUT_OF_TIME) {
            file_put_contents(storage_path('logs/outoftime.log'), json_encode($payload) . "\n", FILE_APPEND);
            return true;
        }

        if (!isset($payload['project']) || !isset($payload['module'])) {
            file_put_contents(storage_path('logs/unknowdata'), json_encode($payload) . "\n", FILE_APPEND);
            return true;
        }

        file_put_contents(storage_path('logs/properdata'), json_encode($payload) . "\n", FILE_APPEND);

        $project = $payload['project'];
        $module = $payload['module'];
        $code = $payload['code'];
        $env = $payload['env'];

        // 模块加上环境
        $module .= '-' . $env;

        // message 为array时转换成string
        if (is_array($payload['message'])) {
            $payload['message'] = json_encode($payload['message']);
        }

        $item = sprintf('%s|%s|%s', $module, $code, $env);
        $count = 1;


        // 如果是消息来自测试环境, 判断是否要报警和展示图表
        $is_alarm = true;
        $is_graph = true;
        if ($env != 'prod') {
            $projectModel = Project::where('name', $project)->first();
            if (!is_null($projectModel)) {
                if (empty($projectModel->test_graph_status)) {
                    $is_graph = false;
                }
                if (empty($projectModel->test_alarm_status)) {
                    $is_alarm = false;
                }
            }
        }


        if ($is_graph) {
            // 更新图表上的数值
            Redis::hincrby(env('QUEUE_ALL_ERROR_NAME'), "$project:$module", $count);
        }

        if ($is_graph == false && $is_alarm == false) {
            return true;
        }

        // 纪录到redis和mysql中, 线上环境都需要纪录，测试环境如果需要图表展示就纪录
        $this->handleMessage($payload);

        // 如果测试环境不需要报警,则退出
        if ($is_alarm == false) {
            return true;
        }
        
        if ($project === 'qalarm') {
            $this->handleQalarmMessage($payload, $item);
            return true;
        }


        // 判断项目级报警是否关闭
        $projectModel = Project::where('name', $project)->first();
        if (!is_null($projectModel)) {
            if (empty($projectModel->status)) {
                return true;
            }
        }

        $strategys = MysqlAlertor::getStrategys($project, $item);

        // 子模块关闭了报警
        if (count($strategys) == 0) {
            return true;
        }

        list($repeatConfig, $x, $thresholdConfig) = MysqlAlertor::getLimitValues(end($strategys));
        $redisCounter = new RedisCounter($project, $item, $strategys);
        
        list($repeat, $newCount) = $redisCounter->get($count);

        if ($repeat >= $repeatConfig && $newCount >= $thresholdConfig) {
            // get message from redis then alarm
            if ($this->canAlarm($project, $module, $code, $env)) {
                $message = '';
                if (isset($payload['message'])) {
                    $message = $payload['message'];
                }
                $this->makeSmsJobs(MysqlAlertor::getMobiles($strategys), $this->getSmsParams($project, $item, $newCount, $message));
                $this->recordAlarm($project, MysqlAlertor::getReceivors($strategys), $this->getSmsContent($project, $item, $newCount, $message));
            }
            $result = $redisCounter->set(true);
        } else {
            $redisCounter->set();
        }

        return true;
    }/*}}}*/

    private function handleQalarmMessage($payload, $item)
    {
        MemoryCounter::setKey($payload['project'], $item);
        $newCount = MemoryCounter::get(1);

        if ($newCount > 10) {
            $params = $this->getSmsParams($payload['project'], $item, $newCount, $payload['message']);

            if (!env('ALARM_USE_SMS', true)) {
                LogUtil::info('Don not use sms, log it', ['mobiles' => env('SUPER_MAN'), 'params' => $params]);
                return true;
            }

            if ($this->canAlarm($payload['project'], $payload['module'], $payload['code'], $payload['env'])) {
                $result = Sms::send(env('SUPER_MAN'), $params);
                LogUtil::info('send sms', ['params' =>  $params, 'result' => $result]);
            }
            MemoryCounter::set();
        }

        return true;
    }

    /**
        * @brief canAlarm 判断是否需要报警，同一类型的报警1分钟内只报警一次
        *
        * @param $project
        * @param $module
        * @param $code
        * @param $env
        *
        * @return 
     */
    private function canAlarm($project, $module, $code, $env)
    {/*{{{*/
        $key = join('|', ['alarm', $project, $module, $code, $env]);
        $existed = Redis::get($key);
        if ($existed !== null) {
            file_put_contents(storage_path('logs/notneedalarm.log'), $key."\n", FILE_APPEND);
            return false;
        } else {
            Redis::set($key, '1');
            Redis::expire($key, 60);
            return true;
        }
    }/*}}}*/

    private function handleMessage($payload)
    {/*{{{*/
        $data['payload']     = $payload;
        $jobData = MethodUtil::assemblyJobData($data);
        $job  = new RecordMessageJob($jobData);
        $this->dispatch($job);
    }/*}}}*/

    private  function recordAlarm($project, $receivors, $message)
    {
        $data     = ['project' => $project, 'receivors' => $receivors, 'message' => $message];
        $jobData = MethodUtil::assemblyJobData($data);
        $job  = new RecordAalrmJob($jobData);
        $this->dispatch($job);
    }
    private function makeSmsJobs($mobiles, $params)
    {/*{{{*/
        $data = [];
        $data['params']      = $params;
        $data['template_id'] = Sms::SMS_TEMPLATE_ALARM_ID;



        if (!env('ALARM_USE_SMS', true)) {
            LogUtil::info('Don not use sms, log it', ['mobiles' => $mobiles, 'params' => $params]);
            return true;
        }

        foreach ($mobiles as $mobile) {
            $data['mobiles']     = [$mobile];
            $jobData = MethodUtil::assemblyJobData($data);
            $job  = new NoticeBySmsJob($jobData);
            $this->dispatch($job);
        }
    }/*}}}*/
    private function getSmsContent($project, $item, $count, $message)
    {
        $alarmTime    = date('Y-m-d H:i:s', time());
        $alarmProject = $project . '|' . $item;
        $alarmCount   = $count;
        $alarmMessage = $message;

        $testMessage  =  sprintf("[%s][%s]出现[%s]次异常,异常内容:[%s]。", $alarmTime, $alarmProject, $alarmCount, $alarmMessage);

        return $testMessage;
    }

    
    public function getSmsParams($project, $item, $count, $message)
    {/*{{{*/
        $alarmTime    = date('H:i:s', time());
        $alarmProject = $project . '|' . $item;
        $alarmCount   = $count;
        $alarmMessage = $message;

        $testMessage  =  sprintf("[%s][%s]出现[%s]次异常,异常内容:[%s]。", $alarmTime, $alarmProject, $alarmCount, $alarmMessage);

        // 参数总和不超过 195 个字符
        $limitCount = 186 - 1;
        $diff = strlen($testMessage) - $limitCount;

        if ($diff >= 0) {
            $alarmMessage = mb_substr($alarmMessage, 0, strlen($alarmMessage) - $diff - 3);
            $alarmMessage .= ',详见Qalarm';
        }

        $params[] = [
            '[' . $alarmTime .']',
            '[' . $alarmProject . ']',
            '[' . $alarmCount . ']次',
            $alarmMessage
        ];

        return $params;
    }/*}}}*/
}
