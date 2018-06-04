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


use \Exception;

class CheckRedisMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alarm:check_redis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查qconf中redis master的变更';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private static $redisMaster = null;


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        while (true) {
            $new = \Qconf::getHost(env('REDIS_QUEUE_QCONF'));
            // 有变动
            if (static::$redisMaster != null && static::$redisMaster != $new) {
                exec('sh /var/wd/wrs/webroot/qalarm/deploy/reload.sh');
            }
            print 'redis old host:' .static::$redisMaster . ', new host: '. $new ."\n";

            static::$redisMaster = $new;
            sleep(1);
        }
    }/*}}}*/

}