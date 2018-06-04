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
use Illuminate\Support\Facades\Redis;
use Predis\Connection\ConnectionException;


use \Kafka\Consumer;
use \Exception;

class KafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alarm:kafka';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '通过kafka消费日志';

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
                $consumer = \Kafka\Consumer::getInstance(env('KAFKA_CLUSTER_ZK'), 3000);
                $consumer->setGroup(env('KAFKA_ALARM_GROUP'));
                $consumer->setMaxBytes(self::MAX_SIZE);
                $consumer->setTopic(env('KAFKA_ALARM_TOPIC'));

                $result = $consumer->fetch();
                foreach ($result as $topicName => $partition) {
                    foreach ($partition as $partId => $messageSet) {
                        // 积压10000条时，概率性报警
                        if ((($partition->getHighOffset()-10000) > $messageSet->messageOffset()) && (mt_rand(1,5) == 3)) {
                            LogUtil::error('Too many messages,something error???', ['offset' => $partition->getHighOffset()]);
                        }
                        foreach ($messageSet as $message) {
                            try {
                                $this->consumeFunc($message);
                            } catch (Exception $e) {
                                LogUtil::error($e->getMessage(), []);
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
        file_put_contents(storage_path('logs/rawdata.log'), $message ."\n", FILE_APPEND);

        $json    = json_decode($message, true);
        $payload = json_decode($json['body'], true);

        if ($payload == null) {
            file_put_contents(storage_path('logs/brokendata.log'), $message ."\n", FILE_APPEND);
            return true;
        }

        if (isset($payload['i']) && $payload['i'] == 'localhost') {
            $payload['i'] = $json['hostname'];
        }

        // heartbeat
        if (isset($payload['type']) && $payload['type'] == 'h') {
            Redis::hset(env('QALARM_HOST_HEARTBEAT_REDIS_LIST_KEY'), $payload['i'], $payload['t']);
            return true;
        }

        $handler = new AlarmHandler();
        return $handler->handle($payload); 
    }/*}}}*/
}
