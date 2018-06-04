<?php
namespace App\Jobs;

use Log;


use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use Illuminate\Support\Facades\Redis;

class PhoenixReportJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {
        $message = $this->object;

        $metricsJson = $message['metrics'];


        $redisListKey   = env('PHOENIX_METRIC_HASH_NAME');
        $redisMetricKey = env('PHOENIX_METRIC_QUEUE_NAME');
        $redisWaterfallKey = 'phoenix_waterfall_hashlist';

        $metrics = json_decode($metricsJson, true);

        foreach ($metrics as $metricJson) {
            $metric = json_decode($metricJson, true);
            if ($metric['type'] == 'speed') {
                $key = $redisMetricKey . '_' . $metric['project'];
                $field = Redis::hget($key, $metric['module']);

                $speed = $metric['data']['speed'];
                if (is_null($field)) {
                    Redis::hset($key, $metric['module'], $speed);
                } else {
                    // fixme 优化值
                    $v = ($speed * 3 / 20 + $field * 17 / 20);
                    Redis::hset($key, $metric['module'], $v);
                }

                $lkey = $redisMetricKey . '_' . $metric['project'] . '_' . $metric['module'];
                $metric['speed'] = $metric['data']['speed'];

                // waterfall 数据保存到一个hashlist中
                if (isset($metric['data']['waterfall'])) {
                    $uuid = MethodUtil::getUniqueId();
                    Redis::hset($redisWaterfallKey, $uuid, $metric['data']['waterfall']);
                    $metric['waterfall_id'] = $uuid;
                }
                $metric['time'] = Carbon::createFromTimestamp($metric['time'])->toDateTimeString();
                unset($metric['data']);

                Redis::lpush($lkey, json_encode($metric));
            }
            // add list
            Redis::hset($redisListKey, $metric['project'], 1);

        }
        return true;
    }
}

