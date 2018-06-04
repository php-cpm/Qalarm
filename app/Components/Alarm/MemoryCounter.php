<?php

namespace App\Components\Alarm;

class MemoryCounter
{
    Const QALARM_REDIST_PREFIX  = 'Qalarm';

    private static $key = null;
    public static $countCenter = [];

    public static function setKey($project, $item)
    {/*{{{*/
        static::$key = static::QALARM_REDIST_PREFIX . '-' . $project . '-' . $item;
    }/*}}}*/
    
    public static function get($inc)
    {
        $key = static::$key;
        $currentTime = time();
        if (!isset(static::$countCenter[$key])) {
            static::$countCenter[$key] = [$currentTime, 1];
            return 1;
        }
        list($time, $count) = static::$countCenter[$key];

        if (($currentTime - $time) > 60) {
            static::$countCenter[$key] = [$currentTime, 1];
            return 1;
        }

        $newCount = $count + $inc;
        static::$countCenter[$key] = [$time, $newCount];

        return $newCount;
    }

    public static function set($clear = false)
    {
        $key = static::$key;
        static::$countCenter[$key] = 0;
    }
}
