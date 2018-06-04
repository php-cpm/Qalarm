<?php

namespace App\Components\Alarm;

class RedisCounter implements CounterInferface
{
    Const QALARM_REDIST_PREFIX  = 'Qalarm';

    private $key;

    private $alarmStrategys;

    private $counter;

    public function __construct($project, $item, $strategys)
    {/*{{{*/
        $this->key = self::QALARM_REDIST_PREFIX . '-' . $project . '-' . $item;
        $this->alarmStrategys = $strategys;
    }/*}}}*/

    /**
     * @brief getCount 返回limit二元组
     *
     * @param $project
     * @param $item
     *
     * @return 
     */
    public function get($inc) 
    {/*{{{*/

        $this->counter = new CounterModel($this->key, end($this->alarmStrategys));

        $this->counter = $this->counter->incAndLock($inc);

        if ($this->counter == null) {
            return [1, 1];
        }
        return $this->counter->getDouble();
    }/*}}}*/

    public function set($clear = false)
    {/*{{{*/
        if ($this->counter == null) {
            return true;
        }
        return $this->counter->unLock($clear);
    }/*}}}*/
}
