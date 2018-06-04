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

class PageMonitorKafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:kafka';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '通过kafka消费H5日志';

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
        while (true) {
            try {
                $consumer = \Kafka\Consumer::getInstance(env('KAFKA_PAGE_CLUSTER_ZK'), 3000);
                $consumer->setGroup(env('KAFKA_PAGE_GROUP'));
                $consumer->setMaxBytes(self::MAX_SIZE);
                $consumer->setTopic(env('KAFKA_PAGE_TOPIC'));

                $result = $consumer->fetch();
                foreach ($result as $topicName => $partition) {
                    foreach ($partition as $partId => $messageSet) {
                        foreach ($messageSet as $message) {
                            try {
                                $this->consumeFunc($message->getMessage());
                            } catch (Exception $e) {
                                throw $e;
                                // LogUtil::error($e->getMessage(), []);

                            }
                        }
                    }
                }
            } catch (ConnectionException $e) {
                LogUtil::error('Merge service error', [$e->getMessage()]);
                sleep(1);
            }catch (\Exception $e) {
                LogUtil::error($e->getMessage(), []);
                // sleep a while
                sleep(1);
            }
        }
    }/*}}}*/

    public function consumeFunc($message)
    {/*{{{*/

// file_put_contents(storage_path('logs/pageoutoftime.log'), $message. "\n", FILE_APPEND);
// return;
        $json    = json_decode($message, true);
        $payload = $json['body'];
        $list = explode(' ', $payload);
        if (count($list) <= 12) {
            return true;
        }

        $host = $list[5];
        $url  = $list[7];
        $urls = [];
        $referer = $list[12];
        $url = "http://{$host}{$url}";


        $url = $this->parseUrl($url);
        if ($url != null) {
            $urls[] = $url;
        }
        $referer = $this->parseUrl($referer);
        if ($referer != null) {
            $urls[] = $referer;
        }

        if (count($urls) == 0) {
            return;
        }
        foreach ($urls as $u) {
            file_put_contents(storage_path('logs/pageraw.log'), $u['url'] . "\n", FILE_APPEND);
            if (!$this->isExistedLately($u['url'])) {
                file_put_contents(storage_path('logs/pagequeue.log'), $u['url'] . "\n", FILE_APPEND);
                $this->push('h5.ffan.com', $u);
            }
        }
    }/*}}}*/

    private  function isExistedLately($url) {
        $md5sum = md5($url);
        $redisKey = 'phoenix_lately_urls_hashlist';
        $timestamp = Redis::hget($redisKey, $md5sum);

        // not exist
        if (is_null($timestamp)) {
            Redis::hset($redisKey, $md5sum, time());
            return false;
        }

        // 5分钟内重复链接不再监控
        if ((time() - $timestamp) < 60 * 5) {
            return true;
        }

        // 超过5分钟后的重复链接
        Redis::hset($redisKey, $md5sum, time());
        return false;
    }


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

    private function parseUrl($url)
    {
        $Urls = [
            'app/goods/detail',
            'app/coupon',
            'app/mycoupon',
            'app/goods',
            'app/activity',
            'app/merchant',
            'app/getgcoupon',
        ];

        $url = str_replace('\\', '', $url);
        $url = trim($url, '"&\\');

        if (strpos($url, 'qalarm') !==  false) {
            return null;
        }

        $url .= '&__trace=qalarm';
        if (strpos($url, 'ajax') !==  false) {
            return null;
        }



        foreach ($Urls as $u) {
            if (strpos($url, $u) !== false) {
                return ['url' => $url, 'module' => $u];
            }
        }

        return null;
    }
}
