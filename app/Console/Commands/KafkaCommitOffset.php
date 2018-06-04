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

class KafkaCommitOffset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alarm:kafka-commit-offset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'kafka修改offset';

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
        $data = array(
            'group_id' => env('KAFKA_ALARM_GROUP'),
            'data' => array(
                array(
                    'topic_name' => env('KAFKA_ALARM_TOPIC'),
                    'partitions' => array(
                        array(
                            'partition_id' => 0,
                            'offset' => 758494898, 
                        ),
                    ),
                ),
            ),
        );


        $conn = new \Kafka\Socket('10.209.26.168', 10118);
        $conn->connect();
        $encoder = new \Kafka\Protocol\Encoder($conn);
        $encoder->commitOffsetRequest($data);

        $decoder = new \Kafka\Protocol\Decoder($conn);
        $result = $decoder->commitOffsetResponse();
        var_dump($result);
    }/*}}}*/
}
