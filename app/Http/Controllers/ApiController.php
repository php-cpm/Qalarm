<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use Illuminate\Support\Facades\Redis;
use Exception;
use RuntimeException;

use App\Smarty;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

use App\Components\Notice\Sms;
use App\Components\Utils\MethodUtil;
use App\Jobs\PhoenixReportJob;
use App\Jobs\NoticeBySmsJob;

use App\Models\Qalarm\Monitor;

class ApiController extends Controller
{
    const LOCAL_LOG_DIR = '/var/wd/wrs/logs/alarm/';
    const FILE_PERM     = 0755;

    public  function  publish(Request $request)
    {
        $this->validate($request, [
            'project'   => 'required',
            'module'    => 'required',
            'code'      => 'required',
            'message'   => 'required',
        ]);

        $result = $this->output($request->all());
        return response()->clientSuccess([$result]);
    }
    
    public  function  publishV2(Request $request)
    {
        $content = $request->getContent();
        $data = json_decode($content, true);
        $result = $this->outputV2($data);
        return response()->clientSuccess([$result]);
    }

    public  function sms(Request $request)
    {
        $this->validate($request, [
            'ctx_ids'               => 'required',
            'business_name'         => 'required',
            'exception_name'        => 'required',
            'exception_detail'      => 'required',
        ]);

        $alarmTime    = date('Y-m-d H:i:s', time());
        $params[] = [
            $alarmTime,
            $request->input('business_name'),
            $request->input('exception_name'),
            $request->input('exception_detail')
        ];

        $mobiles = [];
        $staffs = Monitor::whereIn('username', explode(',', $request->input('ctx_ids')))->get();
        foreach ($staffs as $staff) {
            $mobiles[] = $staff->mobile;
        }

        $mobiles = array_unique($mobiles);

        $data = [];
        $data['params']      = $params;
        $data['template_id'] = Sms::SMS_TEMPLATE_ALARM_ID;

        foreach ($mobiles as $mobile) {
            $data['mobiles']     = [$mobile];
            $jobData = MethodUtil::assemblyJobData($data);
            $job  = new NoticeBySmsJob($jobData);
            $this->dispatch($job);
        }

        return response()->clientSuccess([count($mobiles)]);
    }

    public  function  phoenixReport(Request $request)
    {
        $metrics = $request->input('metrics', '');
        if (empty($metrics)) {
            return response()->clientWdSuccess([]);
        }

        $data     = ['metrics' => $metrics];
        $jobData = MethodUtil::assemblyJobData($data);
        $job  = new PhoenixReportJob($jobData);
        $this->dispatch($job);

        // // module : speed, conn_error, res_timeout, js_error, page_timeout
        return response()->clientWdSuccess([]);
    }

    public  function  phoenixJobs(Request $request)
    {
        $data = [];
        $limit = 20;
        $index = 0;

        // 只取5分钟以内的数据
        while ($limit > $index) {
            $item = Redis::rpop(env('PHOENIX_JOBS_QUEUE_NAME'));
            if (is_null($item)) {
                break;
            }
            $job =  json_decode($item, true);
            if ((time() - $job['time']) > 60 * 5) {
                continue;
            }
            $data[] = $job;
            ++$index;
        }

        return response()->clientWdSuccess($data);
    }

    public function hostHeartbeat(Request $request)
    {
        $hosts = Redis::hgetall(env('QALARM_HOST_HEARTBEAT_REDIS_LIST_KEY'));

        $all = [];
        foreach ($hosts as $host => $timestamp) {
            $all[$host]  = date('Y-m-d H:i:s', $timestamp);
        }
        arsort($all);

        return response()->clientWdSuccess(['count' => count($all), 'detail' => $all]);
    }



    public  static function output($data)
    {/*{{{*/
        $msg = json_encode($data);
        $log_file = self::LOCAL_LOG_DIR . 'alarm.log';
        if (!is_file($log_file)) {
            touch($log_file);
            chmod($log_file, self::FILE_PERM);
        }
        file_put_contents($log_file, $msg ."\n", FILE_APPEND|LOCK_EX);

        return true;
    }/*}}}*/
    
    public  static function outputV2($data)
    {/*{{{*/
        $msg = $data['body'];
        $log_file = self::LOCAL_LOG_DIR . 'alarm.log';
        if (!is_file($log_file)) {
            touch($log_file);
            chmod($log_file, self::FILE_PERM);
        }

        file_put_contents($log_file, $msg ."\n", FILE_APPEND|LOCK_EX);

        return true;
    }/*}}}*/
}

