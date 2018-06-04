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
use App\Components\Utils\LogUtil;
use Predis\Connection\ConnectionException;


use \Kafka\Consumer;
use \Exception;
use Illuminate\Support\Facades\Redis;

class PageFFanIndeGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '常态监控的页面';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    const MAX_SIZE    = 102400;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        $projects = [
            'www.ffan.com' => [
                ['module' => 'new/index', 'url' => 'http://www.ffan.com/new/index.html'],
                ['module' => 'new/brand', 'url' => 'http://www.ffan.com/new/html/brand/brandInto.html'],
                ['module' => 'new/business ', 'url' => 'http://www.ffan.com/new/html/business/business.html'],
                ['module' => 'new/merchant', 'url' => 'http://www.ffan.com/new/html/merchant/merchant.html'],
                ['module' => 'new/about', 'url' => 'http://www.ffan.com/new/html/about/aboutFfan.html'],
                ['module' => 'new/download', 'url' => 'http://www.ffan.com/personal/html/appDown.html'],
                ['module' => 'new/personal', 'url' => 'http://www.ffan.com/personal/index'],
            ],
        ];

        foreach ($projects as $project => $modules) {
            foreach ($modules as $module) {
                var_dump($module);
                $this->push($project, $module);
            }
        }
    }/*}}}*/

    private function push($project, $url)
    {
        $data = [
            'project' => $project,
            'url'     => $url['url'],
            'module'  => $url['module'],
            'md5'     => md5($url['url']),
            'priority' => 3,
            'time'    =>  time(),
        ];

        Redis::lpush(env('PHOENIX_JOBS_QUEUE_NAME'), json_encode($data));
    }
}
