<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

use App\Components\Alarm\AlarmHandler;
use App\Components\Notice\Sms;
use App\Components\Utils\LogUtil;

class MonitorUnavailableHost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature ='alarm:unavailable_host';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '监控收集机器';

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
    {
        // 取得标准维护的机器列表

        $path = base_path() . '/operation';
        $files = scandir($path);

        $all = [];
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $content = file_get_contents($path . '/' . $file);
            $itemHosts = explode("\n", $content);
            foreach ($itemHosts as $host) {
                $host = trim($host);
                $host = preg_replace('/\s(?=)/', '',$host);
                if (!empty($host)) {
                    $all[] = $host;
                }
            }
        }

        $all = array_unique($all);
        $all = array_merge([], $all);

        // 纪录一份全量的到storage下面
        file_put_contents(storage_path('app') . '/allhost', join("\n", $all));

        // 得到在线的机器
        $rawHosts = Redis::hgetall(env('QALARM_HOST_HEARTBEAT_REDIS_LIST_KEY'));

        $online = [];
        $dead   = [];
        foreach ($rawHosts as $host => $timestamp) {
            // 如果一小时没有更新心跳，认为机器挂了
            if ((time() - $timestamp) > 3600) {
                $dead[]  = $host;
            } else {
                $online[] = $host;
            }
        }

        $notMonitorHost = [];
        foreach ($all as $host) {
            if (in_array($host, $online) || in_array($host, $dead)) {
                continue;
            }
            $notMonitorHost[] = $host;
        }

        $alarm = new AlarmHandler();
        $params = $alarm->getSmsParams('qalarm', 'monitor_unavailable_host', count($dead) + count($notMonitorHost), '收集链路不通，请检查');
        $result = Sms::send(env('SUPER_MAN'), $params);
        LogUtil::info('monitro_unavailable_host send sms', ['params' =>  $params, 'result' => $result]);

        // 纪录到storage下， 以备查看
        file_put_contents(storage_path('app') . '/deadhost', join("\n", $dead)); 
        file_put_contents(storage_path('app') . '/notmonitorhost', join("\n", $notMonitorHost)); 

    }
}
