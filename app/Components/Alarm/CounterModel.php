<?php

namespace App\Components\Alarm;

use Exception;

use Illuminate\Support\Facades\Redis;

class CounterModel
{
    private $counterName;   // 计数器名称
    private $strategy;      // 此计数器的报警策略

    private $zoneCounts;    // 每个时间区间内的报警次数（最小1次）[['s'=> , 'e' => , 'c' => ], ], s:starttime, e:endtime, c:count

    private $owner;         // 本次操作进程（用于)
    private $dataVersion;   // 递增的数据版本号

    private static $waitTimes = 0;

    public function __construct($counterName, $strategy)
    {/*{{{*/
        $this->counterName    = $counterName;
        $this->strategy       = $strategy;
        $this->repeat         = 0;
        $this->zoneCounts[]   = $this->getRawCount();
        $this->owner          = null;
        $this->dataVersion    = 1;
    }/*}}}*/

    /**
        * @brief getAndLock 如果owner不是自己，并且时间超过3s，则抢占counter的使用权限,防止死锁
        *
        * @param $counterName
        * @param $incCount
        *
        * @return 
     */
    public function incAndLock($incCount)
    {/*{{{*/
        try {
            $redis = Redis::connection('default');

            // $redis->multiExec($options, function($transaction) use ($counterName) {
                $raw     = $redis->get($this->counterName);

                $counter = unserialize($raw);
                if ($counter == null) {
                    $counter = $this;
                } else {
                    // 更改报警策略
                    $counter->strategy = $this->strategy;
                }

                // 如果当前有其他进程正在修改此key,则等待3秒，
                if ($counter->owner != null && $counter->owner != $counter->getSelfName()) {
                    if (self::$waitTimes <= 29) {
                        usleep(100000);   // sleep 100ms
                        self::$waitTimes ++;
                        return $counter->incAndLock($incCount);
                    }
                }

                $counter->owner = $counter->getSelfName();
                $counter->dataVersion ++;

                // 上锁
                $redis->set($counter->counterName, serialize($counter));

                // 统计新值，并写入redis，返回
                $counter->statistic($incCount);
                $redis->set($counter->counterName, serialize($counter));
                
                // var_dump([$counter->zoneCounts, $counter->dataVersion, $counter->owner]);

                return $counter;

            // });
        }catch (Exception $e) {
        }
    }/*}}}*/

    public function unLock($clear = false)
    {/*{{{*/
        if ($clear) {
            $last = array_pop($this->zoneCounts);
            $last['c'] = 0;

            $this->zoneCounts[] = $last;
        }

        return $this->update();
    }/*}}}*/

    public function getDouble()
    {/*{{{*/
        $repeat = count($this->zoneCounts) - 1;
        $last   = end($this->zoneCounts);
        
        list($x, $z, $threshold) = $this->strategy['limit'];

        if ($last['c'] >= $threshold) {
            $repeat += 1;
        }

        return [$repeat, $last['c']];
    }/*}}}*/
    
    private function getSelfName()
    {/*{{{*/
        return gethostname() . '-' . posix_getpid();
    }/*}}}*/
    
    /**
     * @brief update
     *
     * @return 
     */
    private function update()
    {/*{{{*/
        $redis = Redis::connection('default');
        $old   = unserialize($redis->get($this->counterName));
        
        if ($old->owner == $this->owner && $old->dataVersion == $this->dataVersion) {
            $this->owner = null;
            $redis->set($this->counterName, serialize($this));
            return true;
        }

        // Log 超时没有处理，丢弃

        return false;
    }/*}}}*/
    
    private function  statistic($incCount)
    {/*{{{*/
        list($repeat, $during, $threshold) = $this->strategy['limit'];
        $timestamp = time();

        // 删除一个大统计周期之前的数据
        foreach ($this->zoneCounts as $idx => $zoneCount) {
            if (($timestamp - $zoneCount['s']) > ((0.5+$repeat) * $during * 60)) {
                unset($this->zoneCounts[$idx]);
            }
        }

        $last = array_pop($this->zoneCounts);
        $last = $last == null ? $this->getRawCount() : $last;
        $newCount    = $last['c'] + $incCount;
        $newSumCount = $last['sc'] + $incCount;

        $diffTime = $timestamp - $last['s'];
        /* 小周期统计：
         * 1、如果统计周期结束，但是报警次数没有达到阈值以上，则删除往前的所有数据,并创建新的小周期数据; 
         *      如果报警次数大于等于阈值，则创建新的小周期数据
         * 2、如果统计周期没有结束，则累加报警计数
         */
        if ($diffTime >= ($during * 60)) {
            $zone = $this->getRawCount();
            $zone['s'] = $timestamp;
            $zone['e'] = $timestamp;
            $zone['c'] = $incCount;
            $zone['sc'] = $incCount;
            if ($newSumCount < $threshold) {
                $this->zoneCounts = [];      // 清空之前数据
                $this->zoneCounts[] = $zone;
            }

            $this->zoneCounts[] = $last;     // 把取出的小周期数据放回计数器
            $this->zoneCounts[] = $zone;
        } else {
            $last['e'] = $timestamp;
            $last['c'] = $newCount;
            $last['sc']=$newSumCount;

            $this->zoneCounts[] = $last;
        }
    }/*}}}*/

    private function getRawCount()
    {/*{{{*/
        return ['s' => time(), 'e' => time(), 'c' => 0, 'sc' => 0]; 
    }/*}}}*/
}
