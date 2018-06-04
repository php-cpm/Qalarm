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
use Illuminate\Support\Facades\Redis;

class GeneratePageJobs extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动产生页面job';

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
        while (true) {
            $this->testFunc(); 
            sleep(3);
        }
    }/*}}}*/

    public  function testFunc()
    {/*{{{*/
        $projects = [
            'ffan_index',
            'ffan_h5',
            'ffan_act',
            'ffan_film',
            'ffan_story',
            'ffan_api',
            'ffan_app',
        ];

        $urls = [
            'https://www.baidu.com',
            'http://www.ffan.com/new/index.html',
            'http://www.ffan.com/new/html/brand/brandInto.html',
            'http://mail.163.com/',
            'http://h5.ffan.com//app/activity?aid=39182&cityId=110100&plazaId=1000772',
            'http://h5.ffan.com//app/coupon?cid=20160722104654&cityId=110100&plazaId=1100079&',
        ];

        $count = mt_rand(1, 3);
        for ($i=0; $i<$count; ++$i) {
            $url = $urls[mt_rand(0, count($urls) - 1)];
            $project = $projects[mt_rand(0, count($projects) - 1)];
            $md5 = md5($url);
            $data = [
                'project' => $project,
                'url' => $url,
                'md5' => $md5,
                'priority' => 3,
                'id' => $i
            ];
            Redis::lpush(env('PHOENIX_JOBS_QUEUE_NAME'), json_encode($data));
        }
    }/*}}}*/
}
