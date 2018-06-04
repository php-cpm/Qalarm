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

class LocalFileConsumer extends Command
{
    use DispatchesJobs;

    const LOCAL_LOG = '/var/wd/wrs/logs/alarm/alarm.log';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alarm:file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '读取本地文件日志消费';

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
        $file = fopen(self::LOCAL_LOG, "r");
        fseek($file, -1, SEEK_END);
        $high = ftell($file);


        while (true) {
            $file = fopen(self::LOCAL_LOG, "r");
            fseek($file, $high);
            while (!feof($file)) {
                $line = fgets($file);
                if ($line != false) {
                    var_dump($line);
                    $payload = json_decode($line, true);

                    try{
                        if ($payload != null) {
                            $handler = new AlarmHandler();

                            $handler->handle($payload);
                        }
                    } catch (ConnectionException $e) {
                        LogUtil::error('Merge service error', [$e->getMessage()]);
                        sleep(1);
                    }catch (\Exception $e) {
                        LogUtil::error($e->getMessage(), []);
                        sleep(1);
                    }
                }
            }
            $high = ftell($file);
            fclose($file);
        }



    }/*}}}*/
}
