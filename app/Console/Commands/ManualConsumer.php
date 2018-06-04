<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Components\Notice\Sms;
use App\Components\Alarm\RedisCounter;
use App\Components\Alarm\Alertor;
use App\Components\Alarm\AlarmHandler;
use App\Jobs\NoticeBySmsJob;
use App\Components\Utils\MethodUtil;

use Client\Qalarm\Qalarm;


use \Exception;

class ManualConsumer extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alarm:manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动产生日志消费';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        // [异常时间][业务名称]出现[次数]异常，异常内容:[异常细节]

        while (true) {
            $this->testFunc(); 
            sleep(1);
        }
    }/*}}}*/

    public  function testFunc()
    {/*{{{*/
        $message = file_get_contents(storage_path('app/json'));

        $json    = json_decode($message, true);
        $payload = json_decode($json['body'], true);

        $projects = ['xapi_mop', 'shake'];
        // $projects = ['shake'];
        // $projects = ['xapi_mop', 'shake', 'balance', 'balance1', 'balance2','balance3','balance4','balance5','balance6','balance7', 'balance8', 'balance9'];
        // $modules = ['mysql'];
        $modules = ['mysql', 'redis'];
        // $modules = ['mysql', 'redis', 'gateway', 'slow'];
        $codes   = [10, 11, 12, 13, 14, 15, 16];

        // modify data
        $payload['time'] = time();
        $payload['project'] = $projects[mt_rand(0, count($projects)-1)];
        $payload['module']  = $modules[mt_rand(0, count($modules)-1)];
        $payload['code']    = $codes[mt_rand(0, count($codes)-1)];

        $message = sprintf('%s,%s,%s,%s', $payload['project'], $payload['module'], $payload['code'], $payload['env']);
        $message = 'FATAL: 10.208.20.234 [16/08/24:14:15:55] "GET /ffan/v1/member/cardContent?_realip=10.15.130.80&__uni_source=1.4&__trace_id=10.213.42.59-1472019354.996-13309-1037&cardType=2&__v=v1&bizId=2016051610368&a=getEntInfo" ERROR[] USER[] REFERER[] COOKIE[SHARE_STRING=; CITY_ID=110100; SESSIONID=deleted; uniqkey2=RZhczLgfkoQJoTrJQEabHZ75Wo+4qwsLitBeorX7DFGlhtZaxE7FGGE18czBUyzzmK7d4I8KAlNbEgeV9X0dzWroXQdTxpyBCBxZJAViKnPyUzYtf9023XXU3wIDB2ZqFJAnn2k3FqvIStxI6wHsYTj0ddblLGb/0nrm+Mo9vI71HWT7fvgSV6nfq7icgNRpjtLw; up=bup; sid=044bd2c88b8f760eb6a54c85c5f9c49b; puid=BA2373EB028A4DA5B4F9F145EC788AB9; uid=15000000000988307; psid=bb556c399b953f4406f4a0c843e098b2; SESSIONID=deleted; sid=044bd2c88b8f760eb6a54c85c5f9c49b] POST[] ts[0.0027129650115967] ##0["GET http://api.sit.ffan.com//cdaservice/v2/citys//plazas?_realip=10.15.130.80&__uni_source=1.4&__trace_id=10.213.42.59-1472019354.996-13309-1037&cardType=2&__v=v1&bizId=2016051610368&a=getEntInfo&caller=ffan_xapi&status=2&plazaType=1&fields=location%2CsmartQueue&__uni_source=4.2.1" "" 0.824 404 "后端业务处理异常"] [/var/wd/ffan_xapi/bin/php/ffan/xapi/common/utility/XapiHttpHandler.';

        Qalarm::send($payload['project'], $payload['module'], $payload['code'], $message);
    }/*}}}*/
}
