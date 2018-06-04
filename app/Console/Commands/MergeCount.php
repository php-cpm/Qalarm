<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Components\Utils\LogUtil;

use Predis\Connection\ConnectionException;

class MergeCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alarm:merge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '合并计数值';

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
        $queueNmae = env('QUEUE_ALL_ERROR_NAME', 'allerr_detail');

        while (true) {
            $redis = Redis::connection('default');
            try {
                $errors = $redis->hgetall($queueNmae);

                $redis->del($queueNmae);

                $projects = [];
                if (count($errors) > 0 ) {
                    foreach ($errors as $key => $count) {
                        list($project, $module) = explode(':', $key);
                        if (isset($projects[$project])) {
                            $projects[$project][$module] = $count;
                            $projects[$project]['count'] += $count;
                        } else {
                            $projects[$project] = [$module => $count, 'count' => $count];
                        }
                    }
                }

                Redis::publish(env('QUEUE_COUNT_NAME'), json_encode($projects));

                $timestamp = time() * 1000;
                // reserve latest 10 minute's data
                // record project count history
                foreach ($projects as $projectName => $moduleCounts) {
                    $key = 'history_'.$projectName;
                    $redis->lpush($key, $timestamp .':'.json_encode($moduleCounts));
                    $redis->ltrim($key, 0, 200);

                    // record latest projects
                    Redis::hSet(env('QALARM_HISTORY_PROJECTS_KEY'), "$project", $timestamp);
                }

                var_dump($projects);

                // statistics history per 5 minites
                $interval = 5;
                if ($closeTime = $this->readyStatistics($interval)) {
                    $historyPointTime = strtotime(date("Y-m-d H:i", $closeTime));
                    echo 'Close time is ' . $closeTime . "\n";
                    $historyProjects = Redis::hGetAll(env('QALARM_HISTORY_PROJECTS_KEY'));
                    foreach ($historyProjects as $project => $time) {
                        $projectKey = 'history_'.$project;
                        $historys = Redis::lrange($projectKey, 0, 20*5 + 1);
                        $historyPointCount['count'] = 0;
                        foreach ($historys as $onePoint) {
                             list($pointTime, $pointContent) = explode(':{', $onePoint);
                             // 轮询到5分钟之外的点,退出轮询
                             if (($pointTime /1000 + $interval * 60)  < $historyPointTime) {
                                 break;
                             }

                             $countArray = json_decode('{' . $pointContent, true);
                             $historyPointCount['count'] += $countArray['count'];
                        }

                        $key = 'ex_history_'.$project;
                        $time = (string)($historyPointTime*1000);
                        $value = array($time => $historyPointCount);
                        $redis->lpush($key, json_encode($value));
                        $redis->ltrim($key, 0, 12*24*7);
                    }
                }

                sleep(3);
            } catch (ConnectionException $e) {
                LogUtil::error('Merge service error', [$e->getMessage()]);
            } catch (Exception $e) {
                LogUtil::error('Merge service error', [$e->getMessage()]);
            }

        }
    }

    // 判断是不是5分钟附近
    public function readyStatistics($interval)
    {
        $now = time();
        $nowMinite = (int)date('i', $now);
        $nowSecond = (int)date('s', $now);

        if ($nowMinite % 5 != 0) {
            return false;
        }

        if ($nowSecond > 3) {
            return false;
        }

        var_dump($nowMinite);
        var_dump($nowSecond);

        return $now;

    }
}
